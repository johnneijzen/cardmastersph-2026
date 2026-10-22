<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $line_address
 * @property int $created_at
 * @property int $updated_at
 */
class Address extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'addresses';

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
        'line_address', 'city_id', 'created_at', 'updated_at',
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
        'line_address' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
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
        return $this->hasOne('App\Models\Client');
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City', 'city_id');
    }
}
