<?php

namespace App\Enums;

/**
 * The payment status of a job order.
 *
 * The status is derived from the outstanding balance of the order and is
 * never set by the user.
 */
enum PaymentStatus: string
{
    case Unpaid = 'Unpaid';
    case PartiallyPaid = 'Partially Paid';
    case FullyPaid = 'Fully Paid';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return $this->value;
    }
}
