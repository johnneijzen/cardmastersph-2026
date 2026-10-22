<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\CashReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $date
 * @property string $or_number
 * @property int $created_at
 * @property int $updated_at
 */
class CashReceipt extends Model
{
    /** @use HasFactory<CashReceiptFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'cash-receipt';
    }

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
     * @var list<string>
     */
    protected $fillable = [
        'date', 'or_number', 'client_id', 'job_order_id', 'amount', 'created_at', 'updated_at', 'balance', 'payment_option_id',
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
        'date' => 'date', 'or_number' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var list<string>
     */
    protected $dates = [
        'date', 'created_at', 'updated_at',
    ];

    // Scopes...

    // Functions ...

    // Relations ...
    /**
     * @return BelongsTo<JobOrder, $this>
     */
    public function jobOrders(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return BelongsTo<PaymentOption, $this>
     */
    public function payment_option(): BelongsTo
    {
        return $this->belongsTo(PaymentOption::class, 'payment_option_id');
    }
}
