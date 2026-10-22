<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property int $created_at
 * @property int $updated_at
 * @property string $particulars
 * @property string $unit
 */
class Product extends Model
{
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
     * @var array
     */
    protected $fillable = [
        'name', 'amount', 'created_at', 'updated_at', 'particulars', 'unit_price', 'class_id', 'subclass_id', 'unit',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'particulars' => 'string', 'unit' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];

    // Relations ...
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'orders_products', 'product_id', 'order_id')->withPivot('qty');
    }

    public function product_class()
    {
        return $this->belongsTo('App\Models\ProductClass', 'class_id')->withTrashed();
    }

    public function product_subclass()
    {
        return $this->belongsTo('App\Models\ProductSubclass', 'subclass_id')->withTrashed();
    }
}
