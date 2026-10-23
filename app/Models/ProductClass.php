<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Database\Factories\ProductClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 * @property int $created_at
 * @property int $updated_at
 */
class ProductClass extends Model
{
    /** @use HasFactory<ProductClassFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'product-class';
    }

    /**
     * The human readable description recorded for an event.
     */
    protected function activityLogDescription(string $eventName): string
    {
        return match ($eventName) {
            'deleted' => 'Product Class Archived',
            'restored' => 'Product Class Restored',
            default => str(class_basename($this).' '.$eventName)->headline(),
        };
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'product_classes';

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
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'class_id');
    }

    /**
     * The active subclasses that belong to the class.
     *
     * @return HasMany<ProductSubclass, $this>
     */
    public function subclasses(): HasMany
    {
        return $this->hasMany(ProductSubclass::class, 'product_class_id');
    }
}
