<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property Date $order_date
 * @property Date $due_date
 * @property string $status
 * @property string $overall_remarks
 * @property string $payment_status
 * @property string $purchase_order_id
 * @property string $delivery_receipt_id
 * @property string $billing_statement_id
 * @property int $created_at
 * @property int $updated_at
 */
class Order extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'orders';

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
        'order_date', 'due_date', 'status', 'total_amount', 'client_id', 'created_at', 'updated_at', 'contact_person_id', 'down_payment', 'balance', 'overall_remarks', 'payment_status', 'purchase_order_id', 'delivery_receipt_id', 'billing_statement_id', 'agent_id',
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
        'order_date' => 'date', 'due_date' => 'date', 'status' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'overall_remarks' => 'string', 'payment_status' => 'string', 'purchase_order_id' => 'string', 'delivery_receipt_id' => 'string', 'billing_statement_id' => 'string', 'agent' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'order_date', 'due_date', 'created_at', 'updated_at',
    ];

    // Scopes...

    // Functions ...

    // Relations ...
    public function jobOrder()
    {
        return $this->hasOne('App\Models\JobOrder');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'client_id');
    }

    public function job_order_agent()
    {
        return $this->belongsTo('App\Models\JobOrderAgent', 'agent_id');
    }

    public function client_contact_persons()
    {
        return $this->belongsTo('App\Models\ClientContactPerson', 'contact_person_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'orders_products', 'order_id', 'product_id')->withPivot('qty');
    }
}
