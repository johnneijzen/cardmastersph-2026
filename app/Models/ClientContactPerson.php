<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\ClientContactPersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $contact_person
 * @property string $contact_number
 * @property int $created_at
 * @property int $updated_at
 * @property string $contact_email
 */
class ClientContactPerson extends Model
{
    /** @use HasFactory<ClientContactPersonFactory> */
    use HasFactory, LogsActivity;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'client-contact-person';
    }

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
     * @var list<string>
     */
    protected $fillable = [
        'contact_person', 'contact_number', 'client_id', 'created_at', 'updated_at', 'contact_email',
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
        'contact_person' => 'string', 'contact_number' => 'string', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'contact_email' => 'string',
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
