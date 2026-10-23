<?php

namespace App\Support\Money;

use Brick\Money\Currency;
use Brick\Money\Money;

/**
 * The single currency of the application.
 *
 * Every monetary amount of a job order is expressed in Philippine pesos. The
 * currency is declared once, so a calculation, a stored amount and a formatted
 * label can never disagree about the currency or its minor units.
 *
 * Amounts are only ever created from exact decimal strings, integer minor
 * units or integers. A float is never accepted, because a float cannot
 * represent every cent exactly.
 */
final class MoneyContext
{
    /**
     * The ISO 4217 code of the currency of the application.
     */
    public const CURRENCY_CODE = 'PHP';

    /**
     * The locale that is used to format a monetary amount for display.
     */
    public const LOCALE = 'en_PH';

    /**
     * The currency of the application.
     */
    public static function currency(): Currency
    {
        return Currency::of(self::CURRENCY_CODE);
    }

    /**
     * Build an amount from an exact decimal string or a whole number.
     */
    public static function of(string|int $amount): Money
    {
        return Money::of($amount, self::currency());
    }

    /**
     * Build an amount from a number of minor units, for example 1234 cents.
     */
    public static function ofMinor(int|string $minorAmount): Money
    {
        return Money::ofMinor($minorAmount, self::currency());
    }

    /**
     * Build an amount from a value that was read from the database.
     *
     * A `null` or empty value is the zero amount. The value is always read as
     * a decimal string, so it never passes through a float.
     */
    public static function fromStorage(mixed $amount): Money
    {
        if ($amount === null || $amount === '') {
            return self::zero();
        }

        return self::of((string) $amount);
    }

    /**
     * The zero amount of the currency.
     */
    public static function zero(): Money
    {
        return Money::zero(self::currency());
    }

    /**
     * The exact decimal string that is written to a `DECIMAL(13,2)` column.
     */
    public static function toStorage(Money $money): string
    {
        return (string) $money->getAmount();
    }

    /**
     * Format an amount for a form, a table, a detail page or an export.
     */
    public static function format(Money $money): string
    {
        return $money->formatToLocale(self::LOCALE);
    }

    /**
     * Format an amount that was read from the database.
     */
    public static function formatStorage(mixed $amount): string
    {
        return self::format(self::fromStorage($amount));
    }
}
