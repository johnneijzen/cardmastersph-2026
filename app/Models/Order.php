<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $order_date
 * @property Carbon $due_date
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
    /** @use HasFactory<OrderFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'order';
    }

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
     * @var list<string>
     */
    protected $fillable = [
        'order_date', 'due_date', 'status', 'total_amount', 'client_id', 'created_at', 'updated_at', 'contact_person_id', 'down_payment', 'balance', 'overall_remarks', 'payment_status', 'purchase_order_id', 'delivery_receipt_id', 'billing_statement_id', 'agent_id',
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
        'order_date' => 'date', 'due_date' => 'date', 'status' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'overall_remarks' => 'string', 'payment_status' => 'string', 'purchase_order_id' => 'string', 'delivery_receipt_id' => 'string', 'billing_statement_id' => 'string', 'agent' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var list<string>
     */
    protected $dates = [
        'order_date', 'due_date', 'created_at', 'updated_at',
    ];

    // Scopes...

    // Functions ...

    // Relations ...
    /**
     * @return HasOne<JobOrder, $this>
     * @return HasOne<JobOrder, $this>
     */
    public function jobOrder(): HasOne
    {
        return $this->hasOne(JobOrder::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return BelongsTo<JobOrderAgent, $this>
     * @return BelongsTo<JobOrderAgent, $this>
     */
    public function job_order_agent(): BelongsTo
    {
        return $this->belongsTo(JobOrderAgent::class, 'agent_id');
    }

    /**
     * @return BelongsTo<ClientContactPerson, $this>
     * @return BelongsTo<ClientContactPerson, $this>
     */
    public function client_contact_persons(): BelongsTo
    {
        return $this->belongsTo(ClientContactPerson::class, 'contact_person_id');
    }

    /**
     * @return BelongsToMany<Product, $this>
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'orders_products', 'order_id', 'product_id')->withPivot('qty');
    }
}
