<?php

namespace App\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity as SpatieLogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Shared activity logging convention for the application's business models.
 *
 * Every model that uses this trait records create, update, delete and restore
 * events. Only the attributes that carry business meaning for that model are
 * tracked, so the audit trail stays readable and never stores credentials.
 */
trait LogsActivity
{
    use SpatieLogsActivity;

    /**
     * Attributes that are never written to the audit trail.
     *
     * Credentials must never end up in the activity log, so any model that
     * stores secrets has to list them here.
     *
     * @var array<int, string>
     */
    protected array $activityLogExcludedAttributes = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The log name the activity is recorded under.
     */
    protected function activityLogName(): string
    {
        return 'default';
    }

    /**
     * Configure the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->activityLogAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName($this->activityLogName())
            ->setDescriptionForEvent(fn (string $eventName): string => $this->activityLogDescription($eventName));
    }

    /**
     * The attributes that are tracked for this model.
     *
     * @return array<int, string>
     */
    protected function activityLogAttributes(): array
    {
        return array_values(array_diff(
            $this->getFillable(),
            $this->activityLogExcludedAttributes,
        ));
    }

    /**
     * The human readable description recorded for an event.
     */
    protected function activityLogDescription(string $eventName): string
    {
        return str(class_basename($this).' '.$eventName)->headline();
    }
}
