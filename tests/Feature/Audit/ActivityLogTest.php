<?php

use App\Models\CashReceipt;
use App\Models\Category;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\JobOrder;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\InventoryService;
use Spatie\Activitylog\Models\Activity;

test('creating a business record is written to the activity log', function () {
    $client = Client::factory()->create(['name' => 'Activity Test Client']);

    $activity = Activity::query()
        ->where('subject_type', $client->getMorphClass())
        ->where('subject_id', $client->getKey())
        ->where('event', 'created')
        ->firstOrFail();

    expect($activity->log_name)->toBe('client')
        ->and($activity->description)->toBe('Client Created')
        ->and($activity->attribute_changes['attributes']['name'] ?? null)->toBe('Activity Test Client');
});

test('updating a business record only records the changed attributes', function () {
    $client = Client::factory()->create(['name' => 'Before']);

    $client->update(['name' => 'After']);

    $activity = Activity::query()
        ->where('subject_type', $client->getMorphClass())
        ->where('subject_id', $client->getKey())
        ->where('event', 'updated')
        ->firstOrFail();

    expect($activity->attribute_changes['attributes'])->toBe(['name' => 'After'])
        ->and($activity->attribute_changes['old'])->toBe(['name' => 'Before']);
});

test('the activity log records the user that performed the change', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    $client = Client::factory()->create();

    $activity = Activity::query()
        ->where('subject_type', $client->getMorphClass())
        ->where('subject_id', $client->getKey())
        ->where('event', 'created')
        ->firstOrFail();

    expect($activity->causer_type)->toBe($user->getMorphClass())
        ->and($activity->causer_id)->toBe($user->getKey());
});

test('deleting a business record is written to the activity log', function () {
    $client = Client::factory()->create();

    $client->delete();

    expect(Activity::query()
        ->where('subject_type', $client->getMorphClass())
        ->where('subject_id', $client->getKey())
        ->where('event', 'deleted')
        ->exists())->toBeTrue();
});

test('archiving and restoring a soft deletable record is written to the activity log', function () {
    $jobOrder = JobOrder::factory()->create();

    $jobOrder->delete();
    $jobOrder->restore();

    $events = Activity::query()
        ->where('subject_type', $jobOrder->getMorphClass())
        ->where('subject_id', $jobOrder->getKey())
        ->pluck('event')
        ->all();

    expect($events)->toContain('deleted', 'restored');
});

test('credentials are never written to the activity log', function () {
    $user = User::factory()->create();

    $user->update(['password' => 'super-secret-password']);

    $activities = Activity::query()
        ->where('subject_type', $user->getMorphClass())
        ->where('subject_id', $user->getKey())
        ->get();

    expect($activities)->not->toBeEmpty();

    foreach ($activities as $activity) {
        $recorded = array_merge(
            $activity->attribute_changes['attributes'] ?? [],
            $activity->attribute_changes['old'] ?? [],
        );

        expect($recorded)->not->toHaveKey('password')
            ->and($recorded)->not->toHaveKey('two_factor_secret')
            ->and($recorded)->not->toHaveKey('two_factor_recovery_codes');
    }
});

test('every business model records its activity under its own log name', function (string $model, string $logName) {
    $model::factory()->create();

    expect(Activity::query()->where('log_name', $logName)->exists())->toBeTrue();
})->with([
    [Client::class, 'client'],
    [Employee::class, 'employee'],
    [Inventory::class, 'inventory'],
    [Category::class, 'category'],
    [Subcategory::class, 'subcategory'],
    [InventoryMovement::class, 'inventory-movement'],
    [JobOrder::class, 'job-order'],
    [ProductClass::class, 'product-class'],
    [ProductSubclass::class, 'product-subclass'],
    [CashReceipt::class, 'cash-receipt'],
]);

test('a stock adjustment is written to the activity log with the user that recorded it', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '5']);

    $this->actingAs($manager);

    app(InventoryService::class)->adjustQuantity($inventory, 3, 'Counted on the shelf');

    $change = Activity::query()
        ->where('subject_type', $inventory->getMorphClass())
        ->where('subject_id', $inventory->getKey())
        ->where('event', 'updated')
        ->firstOrFail();

    $movement = Activity::query()
        ->where('log_name', 'inventory-movement')
        ->where('event', 'created')
        ->latest('id')
        ->firstOrFail();

    expect($change->causer_id)->toBe($manager->getKey())
        ->and($change->attribute_changes['attributes']['current_quantity'] ?? null)->toBe('3')
        ->and($movement->causer_id)->toBe($manager->getKey());
});
