<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property Date $date
 * @property int $created_at
 * @property int $updated_at
 */
class WithdrawalSlip extends Model
{
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
     * @var array
     */
    protected $fillable = [
        'date', 'client_id', 'job_order_id', 'created_by', 'updated_by', 'created_at', 'updated_at',
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
        'date' => 'date', 'created_at' => 'timestamp', 'updated_at' => 'timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'date', 'created_at', 'updated_at',
    ];

    // Scopes...

    // Functions ...
    // Functions ...
    public function getCreatedByNameAttribute()
    {
        return User::where('id', $this->created_by)->pluck('name')->first();
    }

    public function getUpdatedByNameAttribute()
    {
        return User::where('id', $this->updated_by)->pluck('name')->first();
    }

    // Relations ...
    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'client_id');
    }

    public function inventory()
    {
        return $this->belongsToMany(Inventory::class, 'inventory_withdrawal_slips', 'withdrawal_slips_id', 'inventory_id')->withPivot('qty', 'particulars');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by');
    }
}
