<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use App\Services\InventoryService;
use Illuminate\Support\Str;

/**
 * Imports the inventory items from a spreadsheet.
 *
 * The import never changes the quantity of an item that already exists: the
 * quantity of a stocked item is only changed through a stock adjustment that
 * records why it changed. A row that would change the quantity is reported as
 * a conflict instead. Duplicate rows inside the file are reported as well, so
 * a file can never create the same item twice.
 */
class InventoryImport extends SpreadsheetImport
{
    /**
     * The identities that were already read from the file.
     *
     * @var array<string, int>
     */
    protected array $seen = [];

    public function __construct(private readonly ?int $userId = null) {}

    /**
     * The headings the spreadsheet must contain.
     *
     * @return array<string, string>
     */
    protected function requiredHeadings(): array
    {
        return [
            'item_name' => 'Item Name',
            'category_name' => 'Category Name',
            'subcategory_name' => 'Subcategory Name',
            'current_quantity' => 'Current Quantity',
        ];
    }

    /**
     * The validation rules of a row.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'category_name' => ['required', 'string', 'max:255'],
            'subcategory_name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'size' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'current_quantity' => [
                'required',
                'numeric',
                'min:0',
                'max:2147483647',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (floor((float) $value) !== (float) $value) {
                        $fail(__('The current quantity has to be a whole number.'));
                    }
                },
            ],
        ];
    }

    /**
     * The validation messages of a row.
     *
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'category_name.required' => __('Every row needs a category.'),
            'subcategory_name.required' => __('Every row needs a subcategory.'),
            'current_quantity.required' => __('Every row needs a current quantity.'),
        ];
    }

    /**
     * The label of a row that is shown in the preview.
     *
     * @param  array<string, mixed>  $row
     */
    protected function previewLabel(array $row): ?string
    {
        return $this->value($row, 'item_name');
    }

    /**
     * Import a single row.
     *
     * @param  array<string, mixed>  $row
     */
    protected function importRow(array $row): void
    {
        $itemName = (string) $this->value($row, 'item_name');
        $category = $this->findCategory((string) $this->value($row, 'category_name'));

        if ($category === null) {
            $this->rejectRow(__('The category :name does not exist.', ['name' => $this->value($row, 'category_name')]));

            return;
        }

        $subcategory = $this->findSubcategory((string) $this->value($row, 'subcategory_name'), $category);

        if ($subcategory === null) {
            $this->rejectRow(__('The subcategory :name does not belong to the category :category.', [
                'name' => $this->value($row, 'subcategory_name'),
                'category' => $category->name,
            ]));

            return;
        }

        $identity = $this->identityKey($row, $category, $subcategory);

        if (isset($this->seen[$identity])) {
            $this->rejectRow(__('This item was already imported on row :row of the file.', [
                'row' => $this->seen[$identity],
            ]));

            return;
        }

        $this->seen[$identity] = $this->currentRowNumber;

        $quantity = (int) $this->value($row, 'current_quantity');
        $existing = $this->findExisting($row, $category, $subcategory);

        if ($existing instanceof Inventory) {
            $this->importExisting($existing, $row, $quantity);

            return;
        }

        $this->inventory()->create([
            'item_name' => $itemName,
            'description' => $this->value($row, 'description'),
            'categories_id' => (int) $category->getKey(),
            'subcategories_id' => (int) $subcategory->getKey(),
            'price' => $this->value($row, 'price'),
            'size' => $this->value($row, 'size'),
            'color' => $this->value($row, 'color'),
            'type' => $this->value($row, 'type'),
            'current_quantity' => $quantity,
        ], $this->userId);

        $this->countImported();
    }

    /**
     * Handle a row that matches an item that is already in the inventory.
     *
     * The quantity is never written here. A row that would change the quantity
     * is a conflict, so the file can never silently change the stock on hand.
     *
     * @param  array<string, mixed>  $row
     */
    protected function importExisting(Inventory $existing, array $row, int $quantity): void
    {
        if ($existing->trashed()) {
            $this->rejectRow(__('An archived item with this name and classification already exists. Restore it instead of importing it again.'));

            return;
        }

        if ($existing->quantity() !== $quantity) {
            $this->rejectRow(__('The import would change the quantity from :current to :imported. Change the quantity with a stock adjustment instead.', [
                'current' => $existing->quantity(),
                'imported' => $quantity,
            ]));

            return;
        }

        $description = $this->value($row, 'description');
        $price = $this->value($row, 'price');

        if ($this->sameText($existing->description, $description) && $this->samePrice($existing->price, $price)) {
            $this->countSkipped();

            return;
        }

        $this->inventory()->update($existing, [
            'item_name' => (string) $this->value($row, 'item_name'),
            'description' => $description,
            'categories_id' => (int) $existing->categories_id,
            'subcategories_id' => (int) $existing->subcategories_id,
            'price' => $price,
            'size' => $this->value($row, 'size'),
            'color' => $this->value($row, 'color'),
            'type' => $this->value($row, 'type'),
        ]);

        $this->countUpdated();
    }

    /**
     * Find the category the row names.
     */
    protected function findCategory(string $name): ?Category
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        return Category::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Find the subcategory the row names inside its category.
     *
     * A subcategory that was carried over from the legacy application does not
     * belong to a category yet, so it is accepted until it is classified.
     */
    protected function findSubcategory(string $name, Category $category): ?Subcategory
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        return Subcategory::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->whereNull('deleted_at')
            ->where(function ($query) use ($category): void {
                $query->where('categories_id', $category->getKey())
                    ->orWhereNull('categories_id');
            })
            ->orderByRaw('CASE WHEN categories_id IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    /**
     * Find the item the row describes, including an archived item.
     *
     * @param  array<string, mixed>  $row
     */
    protected function findExisting(array $row, Category $category, Subcategory $subcategory): ?Inventory
    {
        return Inventory::withTrashed()
            ->whereRaw('LOWER(item_name) = ?', [Str::lower((string) $this->value($row, 'item_name'))])
            ->where('categories_id', $category->getKey())
            ->where('subcategories_id', $subcategory->getKey())
            ->whereRaw("LOWER(COALESCE(size, '')) = ?", [Str::lower((string) $this->value($row, 'size'))])
            ->whereRaw("LOWER(COALESCE(color, '')) = ?", [Str::lower((string) $this->value($row, 'color'))])
            ->whereRaw("LOWER(COALESCE(type, '')) = ?", [Str::lower((string) $this->value($row, 'type'))])
            ->first();
    }

    /**
     * The identity of an item inside the inventory and inside the file.
     *
     * @param  array<string, mixed>  $row
     */
    protected function identityKey(array $row, Category $category, Subcategory $subcategory): string
    {
        return implode('|', [
            Str::lower(trim((string) $this->value($row, 'item_name'))),
            $category->getKey(),
            $subcategory->getKey(),
            Str::lower(trim((string) $this->value($row, 'size'))),
            Str::lower(trim((string) $this->value($row, 'color'))),
            Str::lower(trim((string) $this->value($row, 'type'))),
        ]);
    }

    /**
     * Read a value of the row as text, or nothing when the column is missing.
     *
     * @param  array<string, mixed>  $row
     */
    protected function value(array $row, string $key): ?string
    {
        if (! array_key_exists($key, $row)) {
            return null;
        }

        $value = trim((string) $row[$key]);

        return $value === '' ? null : $value;
    }

    /**
     * Whether two texts describe the same value.
     */
    protected function sameText(?string $current, ?string $imported): bool
    {
        return Str::lower((string) $current) === Str::lower((string) $imported);
    }

    /**
     * Whether two prices are the same amount.
     */
    protected function samePrice(mixed $current, ?string $imported): bool
    {
        $currentPrice = $current === null || trim((string) $current) === '' ? null : (float) $current;
        $importedPrice = $imported === null ? null : (float) $imported;

        if ($currentPrice === null || $importedPrice === null) {
            return $currentPrice === $importedPrice;
        }

        return abs($currentPrice - $importedPrice) < 0.005;
    }

    /**
     * The service that writes the inventory and the opening movements.
     */
    protected function inventory(): InventoryService
    {
        return app(InventoryService::class);
    }
}
