<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\JobOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $created_at
 * @property int $updated_at
 * @property int $deleted_at
 * @property int $job_order_rating
 * @property string|null $job_order_number
 */
class JobOrder extends Model
{
    /** @use HasFactory<JobOrderFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'job-order';
    }

    /**
     * The human readable description recorded for an event.
     *
     * A job order that leaves the active list is archived, not deleted.
     */
    protected function activityLogDescription(string $eventName): string
    {
        return match ($eventName) {
            'deleted' => 'Job Order Archived',
            'restored' => 'Job Order Restored',
            default => str(class_basename($this).' '.$eventName)->headline(),
        };
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'job_orders';

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
        'job_order_number', 'employee_id', 'order_id', 'created_at', 'updated_at', 'created_by', 'updated_by', 'deleted_at', 'job_order_rating',
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
        'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'deleted_at' => 'timestamp', 'job_order_rating' => 'int',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var list<string>
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    // Functions ...
    public function getCreatedByNameAttribute(): ?string
    {
        return $this->createdBy?->name;
    }

    public function getUpdatedByNameAttribute(): ?string
    {
        return $this->updatedBy?->name;
    }

    // Relations ...
    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * @return HasMany<CashReceipt, $this>
     */
    public function cash_receipt(): HasMany
    {
        return $this->hasMany(CashReceipt::class);
    }
}
