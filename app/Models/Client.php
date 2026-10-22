<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $client_number
 * @property int $created_at
 * @property int $updated_at
 */
class Client extends Model
{
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
     * @var array
     */
    protected $fillable = [
        'name', 'client_number', 'address_id', 'created_at', 'updated_at', 'agent_id',
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
        'name' => 'string', 'client_number' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'agent' => 'string',
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
    public function address()
    {
        return $this->belongsTo('App\Models\Address', 'address_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order');
    }

    public function client_agent()
    {
        return $this->belongsTo('App\Models\ClientAgent', 'agent_id');
    }

    public function clientContactPersons()
    {
        return $this->hasMany('App\Models\ClientContactPerson');
    }
}
