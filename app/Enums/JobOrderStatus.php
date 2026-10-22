<?php

namespace App\Enums;

/**
 * The operational status of a job order.
 *
 * The legacy application stored the status as free text on the `orders`
 * table. The values below are the ones the legacy filter and form used.
 */
enum JobOrderStatus: string
{
    case InArtist = 'In Artist';
    case InProduction = 'In Production';
    case AssignedInProgress = 'Assigned: In Progress';
    case AssignedOnHold = 'Assigned: On Hold';
    case CompletedNotYetClaimed = 'Completed: Not Yet Claimed';
    case CompletedClaimed = 'Completed: Claimed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The statuses that mean the job order is finished.
     *
     * @return array<int, string>
     */
    public static function completedValues(): array
    {
        return [
            self::CompletedNotYetClaimed->value,
            self::CompletedClaimed->value,
        ];
    }

    /**
     * Whether the job order is considered finished.
     */
    public function isCompleted(): bool
    {
        return in_array($this->value, self::completedValues(), true);
    }

    public function label(): string
    {
        return $this->value;
    }
}
