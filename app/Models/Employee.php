<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $position
 * @property Carbon $birthday
 * @property string $contact_number
 * @property int $created_at
 * @property int $updated_at
 * @property string $emergency_contact_person_name
 * @property string $emergency_contact_person_number
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'employee';
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'employees';

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
        'first_name', 'middle_name', 'last_name', 'position', 'birthday', 'contact_number', 'address_id', 'user_id', 'created_at', 'updated_at', 'emergency_contact_person_name', 'emergency_contact_person_number',
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
        'first_name' => 'string', 'middle_name' => 'string', 'last_name' => 'string', 'position' => 'string', 'birthday' => 'date', 'contact_number' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'emergency_contact_person_name' => 'string', 'emergency_contact_person_number' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var list<string>
     */
    protected $dates = [
        'birthday', 'created_at', 'updated_at',
    ];

    /**
     * Get the full name of the user.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Relations ...
    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<JobOrder, $this>
     */
    public function jobOrders(): HasMany
    {
        return $this->hasMany(JobOrder::class);
    }
}
