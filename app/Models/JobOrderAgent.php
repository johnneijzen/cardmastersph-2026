<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\JobOrderAgentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $name
 * @property int $created_at
 * @property int $updated_at
 */
class JobOrderAgent extends Model
{
    /** @use HasFactory<JobOrderAgentFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'job-order-agent';
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'job_order_agents';

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
        'name', 'created_at', 'updated_at',
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
        'name' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
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
     * @return HasOne<Order, $this>
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }
}
