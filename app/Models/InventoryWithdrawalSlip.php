<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $qty
 * @property int $created_at
 * @property int $updated_at
 * @property string $particulars
 */
class InventoryWithdrawalSlip extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'inventory_withdrawal_slips';

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
        'qty', 'particulars', 'inventory_id', 'withdrawal_slips_id', 'created_at', 'updated_at',
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
        'qty' => 'int', 'particulars' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
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
}
