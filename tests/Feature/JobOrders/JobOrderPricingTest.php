<?php

use App\Enums\PaymentStatus;
use App\Services\JobOrderPricingService;
use App\Support\Money\MoneyContext;
use Brick\Math\Exception\RoundingNecessaryException;

test('a line amount multiplies the exact unit price and quantity', function () {
    $amount = app(JobOrderPricingService::class)->lineAmount('150.50', 3);

    expect(MoneyContext::toStorage($amount))->toBe('451.50');
});

test('the billed amount is the exact sum of the line amounts', function () {
    $total = app(JobOrderPricingService::class)->total([
        ['unit_price' => '150.50', 'quantity' => 3],
        ['unit_price' => '0.10', 'quantity' => 7],
        ['unit_price' => '9999999.99', 'quantity' => 1],
    ]);

    expect(MoneyContext::toStorage($total))->toBe('10000452.19');
});

test('the balance subtracts the down payment and the payments', function () {
    $balance = app(JobOrderPricingService::class)->balance(
        MoneyContext::of('1000.00'),
        MoneyContext::of('250.00'),
        MoneyContext::of('300.00'),
    );

    expect(MoneyContext::toStorage($balance))->toBe('450.00');
});

test('the payment status follows the balance', function (string $total, string $balance, PaymentStatus $status) {
    $paymentStatus = app(JobOrderPricingService::class)->paymentStatus(
        MoneyContext::of($total),
        MoneyContext::of($balance),
    );

    expect($paymentStatus)->toBe($status);
})->with([
    'unpaid' => ['1000.00', '1000.00', PaymentStatus::Unpaid],
    'partially paid' => ['1000.00', '450.00', PaymentStatus::PartiallyPaid],
    'fully paid' => ['1000.00', '0.00', PaymentStatus::FullyPaid],
    'credit is fully paid' => ['1000.00', '-10.00', PaymentStatus::FullyPaid],
]);

test('an amount that was read from the database keeps its exact value', function () {
    expect(MoneyContext::toStorage(MoneyContext::fromStorage('12345678901.99')))->toBe('12345678901.99');
});

test('an amount can be built from integer minor units', function () {
    expect(MoneyContext::toStorage(MoneyContext::ofMinor(12345)))->toBe('123.45');
});

test('an amount with more precision than the currency throws instead of being rounded silently', function () {
    expect(fn () => MoneyContext::of('10.505'))
        ->toThrow(RoundingNecessaryException::class);
});
