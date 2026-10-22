<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\PaymentOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property int $created_at
 * @property int $updated_at
 */
class PaymentOption extends Model
{
    /** @use HasFactory<PaymentOptionFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'payment-option';
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'payment_options';

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
}
