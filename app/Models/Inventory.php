<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
     * @var array
     */
    protected $fillable = [
        'item_name', 'description', 'categories_id', 'subcategories_id', 'price', 'size', 'color', 'type', 'current_quantity', 'created_at', 'updated_at',
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
        'item_name' => 'string', 'description' => 'string', 'price' => 'string', 'size' => 'string', 'color' => 'string', 'type' => 'string', 'current_quantity' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
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
    public function category()
    {
        return $this->belongsTo('App\Models\Category', 'categories_id');
    }

    public function subcategory()
    {
        return $this->belongsTo('App\Models\Subcategory', 'subcategories_id');
    }

    public function withdrawalSlip()
    {
        return $this->belongsToMany(WithdrawalSlip::class, 'inventory_withdrawal_slips', 'inventory_id', 'withdrawal_slips_id')->withPivot('qty', 'particulars');
    }
}
