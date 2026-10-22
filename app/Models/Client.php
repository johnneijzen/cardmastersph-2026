<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $client_number
 * @property int $created_at
 * @property int $updated_at
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'client';
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'clients';

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
        'name', 'client_number', 'address_id', 'created_at', 'updated_at', 'agent_id',
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
        'name' => 'string', 'client_number' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'agent' => 'string',
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
    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return BelongsTo<ClientAgent, $this>
     */
    public function client_agent(): BelongsTo
    {
        return $this->belongsTo(ClientAgent::class, 'agent_id');
    }

    /**
     * @return HasMany<ClientContactPerson, $this>
     */
    public function clientContactPersons(): HasMany
    {
        return $this->hasMany(ClientContactPerson::class);
    }
}
