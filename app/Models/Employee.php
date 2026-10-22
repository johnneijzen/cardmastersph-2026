<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $position
 * @property Date $birthday
 * @property string $contact_number
 * @property int $created_at
 * @property int $updated_at
 * @property string $emergency_contact_person_name
 * @property string $emergency_contact_person_number
 */
class Employee extends Model
{
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
     * @var array
     */
    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'position', 'birthday', 'contact_number', 'address_id', 'user_id', 'created_at', 'updated_at', 'emergency_contact_person_name', 'emergency_contact_person_number',
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
        'first_name' => 'string', 'middle_name' => 'string', 'last_name' => 'string', 'position' => 'string', 'birthday' => 'date', 'contact_number' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'emergency_contact_person_name' => 'string', 'emergency_contact_person_number' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
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
    public function address()
    {
        return $this->belongsTo('App\Models\Address', 'address_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function jobOrders()
    {
        return $this->hasMany('App\Models\JobOrder');
    }
}
