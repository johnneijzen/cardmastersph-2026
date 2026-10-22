<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $contact_person
 * @property string $contact_number
 * @property int $created_at
 * @property int $updated_at
 * @property string $contact_email
 */
class ClientContactPerson extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'client_contact_persons';

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
        'contact_person', 'contact_number', 'client_id', 'created_at', 'updated_at', 'contact_email',
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
        'contact_person' => 'string', 'contact_number' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'contact_email' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
    ];
    // Scopes...

    // Functions ...

    // Relations ...
    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'client_id');
    }
}
