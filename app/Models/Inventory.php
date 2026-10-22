<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $item_name
 * @property string $description
 * @property string $price
 * @property string $size
 * @property string $color
 * @property string $type
 * @property string $current_quantity
 * @property int $created_at
 * @property int $updated_at
 */
class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory, LogsActivity;

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
        'item_name' => 'string', 'description' => 'string', 'price' => 'string', 'size' => 'string', 'color' => 'string', 'type' => 'string', 'current_quantity' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var list<string>
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];
    // Scopes...

    // Functions ...

    // Relations ...
    /**
     * @return BelongsTo<Category, $this>
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categories_id');
    }

    /**
     * @return BelongsTo<Subcategory, $this>
     * @return BelongsTo<Subcategory, $this>
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'subcategories_id');
    }

    /**
     * @return BelongsToMany<WithdrawalSlip, $this>
     * @return BelongsToMany<WithdrawalSlip, $this>
     */
    public function withdrawalSlip(): BelongsToMany
    {
        return $this->belongsToMany(WithdrawalSlip::class, 'inventory_withdrawal_slips', 'inventory_id', 'withdrawal_slips_id')->withPivot('qty', 'particulars');
    }
}
