<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $item_name
 * @property string|null $description
 * @property int|null $categories_id
 * @property int|null $subcategories_id
 * @property string|null $price
 * @property string|null $size
 * @property string|null $color
 * @property string|null $type
 * @property string|null $current_quantity
 * @property int $created_at
 * @property int $updated_at
 * @property-read Pivot|null $pivot
 */
class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'inventory';
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'inventory';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_name', 'description', 'categories_id', 'subcategories_id', 'price', 'size', 'color', 'type', 'current_quantity', 'created_at', 'updated_at',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var list<string>
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'item_name' => 'string', 'description' => 'string', 'price' => 'string', 'size' => 'string', 'color' => 'string', 'type' => 'string', 'current_quantity' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'deleted_at' => 'timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var list<string>
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    // Scopes...

    // Functions ...
    /**
     * The quantity of the item as a whole number.
     *
     * The legacy application stored the quantity as text, so a quantity that
     * was never a whole number reads as the number it starts with.
     */
    public function quantity(): int
    {
        return (int) $this->current_quantity;
    }

    /**
     * Whether the stored quantity is a whole number.
     *
     * A quantity that is not a whole number is corrected through a stock
     * adjustment, which records why the quantity changed.
     */
    public function quantityIsNumeric(): bool
    {
        return $this->current_quantity === null || is_numeric($this->current_quantity);
    }

    // Relations ...
    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categories_id');
    }

    /**
     * @return BelongsTo<Subcategory, $this>
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'subcategories_id');
    }

    /**
     * @return BelongsToMany<WithdrawalSlip, $this>
     */
    public function withdrawalSlips(): BelongsToMany
    {
        return $this->belongsToMany(WithdrawalSlip::class, 'inventory_withdrawal_slips', 'inventory_id', 'withdrawal_slips_id')->withPivot('qty', 'particulars');
    }

    /**
     * The stock movements that explain the quantity of the item.
     *
     * @return HasMany<InventoryMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
