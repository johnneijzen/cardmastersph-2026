<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use App\Enums\InventoryMovementType;
use Carbon\CarbonInterface;
use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single change to the quantity of an inventory item.
 *
 * The movement keeps the quantity before the change, the change itself and
 * the quantity after it, so a quantity can always be reconstructed.
 *
 * @property InventoryMovementType $type
 * @property int $quantity
 * @property int $quantity_before
 * @property int $quantity_after
 * @property string|null $notes
 * @property CarbonInterface|null $created_at
 */
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'inventory-movement';
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'inventory_movements';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'inventory_id', 'type', 'quantity', 'quantity_before', 'quantity_after', 'notes', 'created_by',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => InventoryMovementType::class,
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'notes' => 'string',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The item the movement belongs to.
     *
     * @return BelongsTo<Inventory, $this>
     */
    public function inventory(): BelongsTo
    {
        // An archived item still owns its movements, so the history of the
        // item can be read after it was removed from the active list.
        return $this->belongsTo(Inventory::class, 'inventory_id')->withTrashed();
    }

    /**
     * The account that recorded the movement.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The name of the account that recorded the movement.
     */
    public function getCreatedByNameAttribute(): ?string
    {
        return $this->createdBy?->name;
    }
}
