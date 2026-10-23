<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\WithdrawalSlipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $slip_number
 * @property Carbon $date
 * @property int|null $employee_id
 * @property int $created_at
 * @property int $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Client|null $client
 * @property-read JobOrder|null $jobOrder
 * @property-read Employee|null $employee
 * @property-read User|null $createdBy
 * @property-read User|null $updatedBy
 * @property-read int|null $inventory_count
 * @property-read int|null $withdrawn_quantity
 */
class WithdrawalSlip extends Model
{
    /** @use HasFactory<WithdrawalSlipFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'withdrawal-slip';
    }

    /**
     * The human readable description recorded for an event.
     *
     * A slip is never hard deleted, because the stock movements and the
     * withdrawal history have to stay readable. The delete event is therefore
     * described as a reversal.
     */
    protected function activityLogDescription(string $eventName): string
    {
        return match ($eventName) {
            'deleted' => 'Withdrawal Slip Reversed',
            'restored' => 'Withdrawal Slip Restored',
            default => str(class_basename($this).' '.$eventName)->headline(),
        };
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'withdrawal_slips';

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
        'slip_number', 'date', 'client_id', 'job_order_id', 'employee_id', 'created_by', 'updated_by', 'created_at', 'updated_at',
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
        'date' => 'date', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'deleted_at' => 'timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var list<string>
     */
    protected $dates = [
        'date', 'created_at', 'updated_at', 'deleted_at',
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

    /**
     * Whether the slip was reversed and left the active list.
     */
    public function isArchived(): bool
    {
        return $this->trashed();
    }

    /**
     * The name of the employee that is responsible for the withdrawal.
     */
    public function employeeName(): ?string
    {
        $employee = $this->getRelationValue('employee');

        return $employee instanceof Employee ? $employee->full_name : null;
    }

    /**
     * The number of items that were withdrawn on the slip.
     */
    public function itemCount(): int
    {
        return $this->inventory->count();
    }

    /**
     * The total quantity that was withdrawn on the slip.
     */
    public function totalQuantity(): int
    {
        return (int) $this->inventory->sum(fn (Inventory $item): int => (int) $item->pivot?->getAttribute('qty'));
    }

    // Relations ...
    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        // An archived client still belongs to the slip, so the slip keeps
        // showing the name of the client it was withdrawn for.
        return $this->belongsTo(Client::class, 'client_id')->withTrashed();
    }

    /**
     * @return BelongsTo<JobOrder, $this>
     */
    public function jobOrder(): BelongsTo
    {
        // An archived job order still belongs to the slip, so the history of
        // the slip stays complete.
        return $this->belongsTo(JobOrder::class, 'job_order_id')->withTrashed();
    }

    /**
     * The employee that is responsible for the withdrawal.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * The items that were withdrawn on the slip.
     *
     * An archived item stays on the slip, so the withdrawal history is never
     * shortened by archiving the item.
     *
     * @return BelongsToMany<Inventory, $this>
     */
    public function inventory(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'inventory_withdrawal_slips', 'withdrawal_slips_id', 'inventory_id')
            ->withPivot('qty', 'particulars')
            ->withTrashed();
    }

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
}
