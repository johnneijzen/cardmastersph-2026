<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $name
 * @property int $created_at
 * @property int $updated_at
 * @property string $particulars
 * @property string $unit
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'product';
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'products';

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
        'name', 'amount', 'created_at', 'updated_at', 'particulars', 'unit_price', 'class_id', 'subclass_id', 'unit',
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
        'name' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'particulars' => 'string', 'unit' => 'string', 'amount' => 'decimal:2', 'unit_price' => 'decimal:2',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var list<string>
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];

    // Relations ...
    /**
     * @return BelongsToMany<Order, $this>
     * @return BelongsToMany<Order, $this>
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'orders_products', 'product_id', 'order_id')->withPivot('qty');
    }

    /**
     * @return BelongsTo<ProductClass, $this>
     * @return BelongsTo<ProductClass, $this>
     */
    public function product_class(): BelongsTo
    {
        return $this->belongsTo(ProductClass::class, 'class_id')->withTrashed();
    }

    /**
     * @return BelongsTo<ProductSubclass, $this>
     * @return BelongsTo<ProductSubclass, $this>
     */
    public function product_subclass(): BelongsTo
    {
        return $this->belongsTo(ProductSubclass::class, 'subclass_id')->withTrashed();
    }
}
