<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property Date $date
 * @property string $or_number
 * @property int $created_at
 * @property int $updated_at
 */
class CashReceipt extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cash_receipts';

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
        'date', 'or_number', 'client_id', 'job_order_id', 'amount', 'created_at', 'updated_at', 'balance', 'payment_option_id',
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
        'date' => 'date', 'or_number' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'date', 'created_at', 'updated_at',
    ];

    // Scopes...

    // Functions ...

    // Relations ...
    public function jobOrders()
    {
        return $this->belongsTo('App\Models\JobOrder', 'job_order_id');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'client_id');
    }

    public function payment_option()
    {
        return $this->belongsTo('App\Models\PaymentOption', 'payment_option_id');
    }
}
