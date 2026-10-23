<?php

use App\Enums\InventoryMovementType;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Build the contents of an inventory spreadsheet.
 *
 * @param  array<int, array<int, mixed>>  $rows
 */
function inventorySpreadsheet(array $rows, ?array $headings = null): string
{
    $headings ??= [
        'Item Name',
        'Category Name',
        'Subcategory Name',
        'Current Quantity',
        'Description',
        'Price',
        'Size',
        'Color',
        'Type',
    ];

    $handle = fopen('php://temp', 'r+');

    fputcsv($handle, $headings);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    rewind($handle);

    return (string) stream_get_contents($handle);
}

/**
 * Create a category with a subcategory inside it.
 *
 * @return array{category: Category, subcategory: Subcategory}
 */
function categoryWithSubcategory(string $category = 'RAW MATERIALS', string $subcategory = 'COTTON'): array
{
    $model = Category::factory()->create(['name' => $category]);
    $child = Subcategory::factory()->create([
        'name' => $subcategory,
        'categories_id' => $model->id,
    ]);

    return ['category' => $model, 'subcategory' => $child];
}

beforeEach(function () {
    Storage::fake('local');
});

test('a guest is redirected to the login page for the inventory import', function () {
    $this->get(route('inventory.import'))
        ->assertRedirect(route('login'));
});

test('a user without the permission may not import the inventory', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('inventory.import'))
        ->assertForbidden();

    Livewire::actingAs($staff)
        ->test('pages::inventory.import')
        ->assertForbidden();
});

test('a manager sees the import page with the required columns', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->assertOk()
        ->assertSee(__('Import inventory'))
        ->assertSee('Item Name')
        ->assertSee('Current Quantity');
});

test('the import rejects a file that is not a spreadsheet', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->create('inventory.pdf', 10, 'application/pdf'))
        ->call('previewImport')
        ->assertHasErrors('file');

    expect(Inventory::query()->count())->toBe(0);
});

test('the import rejects a file that is too large', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->create('inventory.csv', 11000, 'text/csv'))
        ->call('previewImport')
        ->assertHasErrors('file');
});

test('the import reports the columns that are missing', function () {
    $manager = User::factory()->manager()->create();

    $spreadsheet = inventorySpreadsheet(
        [['COTTON FABRIC', 'RAW MATERIALS']],
        ['Item Name', 'Category'],
    );
    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertHasErrors('file')
        ->assertSee('Current Quantity');
});

test('the preview shows what the import would do without writing anything', function () {
    $manager = User::factory()->manager()->create();
    categoryWithSubcategory();

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '12', 'White cotton', '125.50', 'Large', 'White', 'Raw Material'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertHasNoErrors()
        ->assertSet('previewing', true)
        ->assertSet('preview.imported', 1)
        ->assertSee('COTTON FABRIC');

    expect(Inventory::query()->count())->toBe(0)
        ->and(Inventory::withTrashed()->count())->toBe(0);
});

test('the confirmed import writes the item with its opening movement', function () {
    $manager = User::factory()->manager()->create();
    $models = categoryWithSubcategory();

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '12', 'White cotton', '125.50', 'Large', 'White', 'Raw Material'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.imported', 1)
        ->call('confirmImport')
        ->assertHasNoErrors()
        ->assertSet('result.imported', 1);

    $inventory = Inventory::query()->where('item_name', 'COTTON FABRIC')->sole();

    expect($inventory->quantity())->toBe(12)
        ->and($inventory->categories_id)->toBe($models['category']->id)
        ->and($inventory->subcategories_id)->toBe($models['subcategory']->id)
        ->and($inventory->price)->toBe('125.5')
        ->and($inventory->movements()->count())->toBe(1);

    $movement = $inventory->movements()->sole();

    expect($movement->type)->toBe(InventoryMovementType::Opening)
        ->and($movement->quantity_after)->toBe(12)
        ->and($movement->created_by)->toBe($manager->id);

    expect(Storage::disk('local')->allFiles('inventory-imports'))->toBe([]);
});

test('an xlsx spreadsheet can be previewed and imported', function () {
    $manager = User::factory()->manager()->create();
    categoryWithSubcategory();

    $export = new class implements FromArray
    {
        /**
         * @return array<int, array<int, string>>
         */
        public function array(): array
        {
            return [
                ['Item Name', 'Category Name', 'Subcategory Name', 'Current Quantity'],
                ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '12'],
            ];
        }
    };

    $contents = Excel::raw($export, ExcelWriter::XLSX);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.xlsx', $contents))
        ->call('previewImport')
        ->assertHasNoErrors()
        ->assertSet('preview.imported', 1)
        ->call('confirmImport')
        ->assertHasNoErrors()
        ->assertSet('result.imported', 1);

    expect(Inventory::query()->where('item_name', 'COTTON FABRIC')->exists())->toBeTrue();
});

test('a row with a category that does not exist is rejected', function () {
    $manager = User::factory()->manager()->create();
    categoryWithSubcategory();

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'UNKNOWN CATEGORY', 'COTTON', '12'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.failed', 1)
        ->assertSee('does not exist')
        ->call('confirmImport')
        ->assertSet('result.failed', 1);

    expect(Inventory::query()->count())->toBe(0);
});

test('a subcategory that belongs to another category is rejected', function () {
    $manager = User::factory()->manager()->create();
    categoryWithSubcategory();

    $other = Category::factory()->create(['name' => 'FINISHED GOODS']);
    Subcategory::factory()->create(['name' => 'SILK', 'categories_id' => $other->id]);

    $spreadsheet = inventorySpreadsheet([
        ['SILK FABRIC', 'RAW MATERIALS', 'SILK', '12'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.failed', 1)
        ->assertSee('does not belong to the category');
});

test('the same item twice in one file is reported as a duplicate', function () {
    $manager = User::factory()->manager()->create();
    categoryWithSubcategory();

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '12'],
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '12'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.imported', 1)
        ->assertSet('preview.failed', 1)
        ->assertSee('already imported on row')
        ->call('confirmImport');

    expect(Inventory::query()->where('item_name', 'COTTON FABRIC')->count())->toBe(1);
});

test('an item that is already up to date is left untouched', function () {
    $manager = User::factory()->manager()->create();
    $models = categoryWithSubcategory();

    Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'description' => null,
        'categories_id' => $models['category']->id,
        'subcategories_id' => $models['subcategory']->id,
        'price' => null,
        'size' => null,
        'color' => null,
        'type' => null,
        'current_quantity' => '12',
    ]);

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '12'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.skipped', 1)
        ->assertSet('preview.imported', 0)
        ->assertSet('preview.failed', 0);

    expect(Inventory::query()->count())->toBe(1);
});

test('an import may never change the quantity of an existing item', function () {
    $manager = User::factory()->manager()->create();
    $models = categoryWithSubcategory();

    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'description' => null,
        'categories_id' => $models['category']->id,
        'subcategories_id' => $models['subcategory']->id,
        'price' => null,
        'size' => null,
        'color' => null,
        'type' => null,
        'current_quantity' => '5',
    ]);

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '9'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.failed', 1)
        ->assertSee('stock adjustment')
        ->call('confirmImport');

    expect($inventory->fresh()->quantity())->toBe(5)
        ->and($inventory->movements()->count())->toBe(0);
});

test('an import updates the details of an existing item without its quantity', function () {
    $manager = User::factory()->manager()->create();
    $models = categoryWithSubcategory();

    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'description' => null,
        'categories_id' => $models['category']->id,
        'subcategories_id' => $models['subcategory']->id,
        'price' => '100.00',
        'size' => null,
        'color' => null,
        'type' => null,
        'current_quantity' => '5',
    ]);

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '5', 'White cotton', '200.00'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.updated', 1)
        ->assertSet('preview.failed', 0)
        ->call('confirmImport');

    $inventory->refresh();

    expect($inventory->quantity())->toBe(5)
        ->and($inventory->price)->toBe('200')
        ->and($inventory->description)->toBe('White cotton')
        ->and($inventory->movements()->count())->toBe(0);
});

test('an archived item is not imported again', function () {
    $manager = User::factory()->manager()->create();
    $models = categoryWithSubcategory();

    Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'description' => null,
        'categories_id' => $models['category']->id,
        'subcategories_id' => $models['subcategory']->id,
        'price' => null,
        'size' => null,
        'color' => null,
        'type' => null,
        'current_quantity' => '5',
    ])->delete();

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '5'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.failed', 1)
        ->assertSee('archived item');

    expect(Inventory::withTrashed()->count())->toBe(1);
});

test('a quantity that is not a whole number is rejected', function () {
    $manager = User::factory()->manager()->create();
    categoryWithSubcategory();

    $spreadsheet = inventorySpreadsheet([
        ['COTTON FABRIC', 'RAW MATERIALS', 'COTTON', '12.5'],
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->set('file', UploadedFile::fake()->createWithContent('inventory.csv', $spreadsheet))
        ->call('previewImport')
        ->assertSet('preview.failed', 1)
        ->assertSee('whole number');

    expect(Inventory::query()->count())->toBe(0);
});

test('the import cannot be confirmed without a previewed file', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::inventory.import')
        ->call('confirmImport')
        ->assertHasErrors('file');
});
