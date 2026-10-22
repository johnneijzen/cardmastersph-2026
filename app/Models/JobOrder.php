<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $created_at
 * @property int $updated_at
 * @property int $deleted_at
 * @property int $job_order_rating
 */
class JobOrder extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'job_orders';

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
        'employee_id', 'order_id', 'created_at', 'updated_at', 'created_by', 'updated_by', 'deleted_at', 'job_order_rating',
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
        'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'deleted_at' => 'timestamp', 'job_order_rating' => 'int',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
    ];

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
    public function createdBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by');
    }

    public function employee()
    {
        return $this->belongsTo('App\Models\Employee', 'employee_id');
    }

    public function order()
    {
        return $this->belongsTo('App\Models\Order', 'order_id');
    }

    public function cash_receipt()
    {
        return $this->hasMany('App\Models\CashReceipt');
    }
}
