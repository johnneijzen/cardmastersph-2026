<?php

namespace App\Enums;

/**
 * The kind of change that was made to the quantity of an inventory item.
 *
 * The type is stored with the movement, so the history of a quantity can be
 * explained afterwards. The labels are the words shown to the user.
 */
enum InventoryMovementType: string
{
    case Opening = 'opening';
    case Adjustment = 'adjustment';
    case Withdrawal = 'withdrawal';
    case Return = 'return';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening balance',
            self::Adjustment => 'Adjustment',
            self::Withdrawal => 'Withdrawal',
            self::Return => 'Stock returned',
        };
    }
}
