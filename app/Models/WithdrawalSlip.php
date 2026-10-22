<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\WithdrawalSlipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $date
 * @property int $created_at
 * @property int $updated_at
 */
class WithdrawalSlip extends Model
{
    /** @use HasFactory<WithdrawalSlipFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'withdrawal-slip';
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
        'date', 'client_id', 'job_order_id', 'created_by', 'updated_by', 'created_at', 'updated_at',
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
        'date' => 'date', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
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
     * @return BelongsTo<Client, $this>
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return BelongsToMany<Inventory, $this>
     * @return BelongsToMany<Inventory, $this>
     */
    public function inventory(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'inventory_withdrawal_slips', 'withdrawal_slips_id', 'inventory_id')->withPivot('qty', 'particulars');
    }

    /**
     * @return BelongsTo<User, $this>
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
