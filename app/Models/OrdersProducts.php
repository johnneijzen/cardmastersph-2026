<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $oty
 * @property int $created_at
 * @property int $updated_at
 */
class OrdersProducts extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'orders_products';

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
        'oty', 'order_id', 'product_id', 'created_at', 'updated_at',
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
        'oty' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];
    // Scopes...

    // Functions ...

    // Relations ...
}
