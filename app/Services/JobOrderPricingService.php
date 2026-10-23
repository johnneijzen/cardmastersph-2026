<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Support\Money\MoneyContext;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

/**
 * Calculates the amounts of a job order.
 *
 * Every amount is a Money object of the application currency, so the line
 * totals, the billed amount, the balance and the payment status are always
 * calculated without a float. Rounding follows the business rule of half up
 * to the two decimal places of the currency.
 */
class JobOrderPricingService
{
    /**
     * The amount of a single line item.
     */
    public function lineAmount(string $unitPrice, int $quantity): Money
    {
        return MoneyContext::of($unitPrice)
            ->multipliedBy($quantity, RoundingMode::HalfUp);
    }

    /**
     * The billed amount of all line items.
     *
     * @param  array<int, array{unit_price: string, quantity: int}>  $lines
     */
    public function total(array $lines): Money
    {
        $total = MoneyContext::zero();

        foreach ($lines as $line) {
            $total = $total->plus($this->lineAmount($line['unit_price'], $line['quantity']));
        }

        return $total;
    }

    /**
     * The outstanding balance after the down payment and the payments.
     */
    public function balance(Money $total, Money $downPayment, Money $payments): Money
    {
        return $total->minus($downPayment)->minus($payments);
    }

    /**
     * The payment status that belongs to the balance.
     *
     * A balance of zero, or a credit that arose from a later price change, is
     * fully paid. A balance that still equals the billed amount is unpaid.
     * Everything in between is partially paid.
     */
    public function paymentStatus(Money $total, Money $balance): PaymentStatus
    {
        if ($balance->isNegativeOrZero()) {
            return PaymentStatus::FullyPaid;
        }

        if ($balance->isEqualTo($total)) {
            return PaymentStatus::Unpaid;
        }

        return PaymentStatus::PartiallyPaid;
    }

    /**
     * Add up amounts that were read from the database.
     *
     * @param  iterable<int, mixed>  $amounts
     */
    public function sum(iterable $amounts): Money
    {
        $sum = MoneyContext::zero();

        foreach ($amounts as $amount) {
            $sum = $sum->plus(MoneyContext::fromStorage($amount));
        }

        return $sum;
    }
}
