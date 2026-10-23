<?php

use App\Enums\InventoryMovementType;
use App\Livewire\WithdrawalSlips\WithdrawalSlipsTable;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\User;
use App\Models\WithdrawalSlip;
use App\Services\WithdrawalSlipService;
use App\Support\Navigation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Fill the withdrawal slip form with one item.
 */
function fillWithdrawalSlip(Testable $component, Inventory $inventory, int $quantity = 1): Testable
{
    $client = Client::factory()->create();
    $order = Order::factory()->create(['client_id' => $client->id]);
    $jobOrder = JobOrder::factory()->create(['order_id' => $order->id]);

    return $component
        ->set('date', now()->toDateString())
        ->set('client_id', (string) $client->id)
        ->set('job_order_id', (string) $jobOrder->id)
        ->set('employee_id', (string) Employee::factory()->create()->id)
        ->set('items', [[
            'inventory_id' => (string) $inventory->id,
            'quantity' => (string) $quantity,
            'particulars' => 'White cotton',
        ]]);
}

test('a guest is redirected to the login page for the withdrawal slips', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'withdrawal-slips.index',
    'withdrawal-slips.create',
]);

test('a user without the permission may not open the withdrawal slip list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('withdrawal-slips.index'))
        ->assertForbidden();

    Livewire::actingAs(User::factory()->create())
        ->test(WithdrawalSlipsTable::class)
        ->assertForbidden();
});

test('a staff member may issue a withdrawal slip', function () {
    $staff = User::factory()->staff()->create();
    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'current_quantity' => '10',
    ]);

    $component = fillWithdrawalSlip(
        Livewire::actingAs($staff)->test('pages::withdrawal-slips.create'),
        $inventory,
        3,
    );

    $component->call('save')->assertHasNoErrors();

    $slip = WithdrawalSlip::query()->sole();

    expect($slip->slip_number)->toBe('WS-'.now()->format('Y').'-000001')
        ->and($slip->client_id)->not->toBeNull()
        ->and($slip->employee_id)->not->toBeNull()
        ->and($slip->created_by)->toBe($staff->id);

    $inventory->refresh();

    expect($inventory->quantity())->toBe(7);

    $movement = $inventory->movements()->sole();

    expect($movement->type)->toBe(InventoryMovementType::Withdrawal)
        ->and($movement->quantity)->toBe(-3)
        ->and($movement->quantity_before)->toBe(10)
        ->and($movement->quantity_after)->toBe(7)
        ->and($movement->created_by)->toBe($staff->id);

    expect($slip->inventory()->sole()->pivot->qty)->toBe(3);
});

test('the stock of an item may not be taken below zero', function () {
    $staff = User::factory()->staff()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '2']);

    $component = fillWithdrawalSlip(
        Livewire::actingAs($staff)->test('pages::withdrawal-slips.create'),
        $inventory,
        5,
    );

    $component->call('save')->assertHasErrors('items.0.quantity');

    expect(WithdrawalSlip::query()->count())->toBe(0)
        ->and($inventory->fresh()->quantity())->toBe(2)
        ->and($inventory->movements()->count())->toBe(0);
});

test('the same item may not be withdrawn twice on one slip', function () {
    $staff = User::factory()->staff()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '10']);

    $client = Client::factory()->create();
    $order = Order::factory()->create(['client_id' => $client->id]);
    $jobOrder = JobOrder::factory()->create(['order_id' => $order->id]);
    $employee = Employee::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::withdrawal-slips.create')
        ->set('date', now()->toDateString())
        ->set('client_id', (string) $client->id)
        ->set('job_order_id', (string) $jobOrder->id)
        ->set('employee_id', (string) $employee->id)
        ->set('items', [
            ['inventory_id' => (string) $inventory->id, 'quantity' => '1', 'particulars' => 'White cotton'],
            ['inventory_id' => (string) $inventory->id, 'quantity' => '2', 'particulars' => 'White cotton'],
        ])
        ->call('save')
        ->assertHasErrors('items.1.inventory_id');

    expect(WithdrawalSlip::query()->count())->toBe(0)
        ->and($inventory->fresh()->quantity())->toBe(10);
});

test('the job order has to belong to the chosen client', function () {
    $staff = User::factory()->staff()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '10']);

    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $order = Order::factory()->create(['client_id' => $otherClient->id]);
    $jobOrder = JobOrder::factory()->create(['order_id' => $order->id]);
    $employee = Employee::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::withdrawal-slips.create')
        ->set('date', now()->toDateString())
        ->set('client_id', (string) $client->id)
        ->set('job_order_id', (string) $jobOrder->id)
        ->set('employee_id', (string) $employee->id)
        ->set('items', [[
            'inventory_id' => (string) $inventory->id,
            'quantity' => '1',
            'particulars' => 'White cotton',
        ]])
        ->call('save')
        ->assertHasErrors('job_order_id');

    expect(WithdrawalSlip::query()->count())->toBe(0);
});

test('a withdrawal slip needs at least one item and an employee', function () {
    $staff = User::factory()->staff()->create();

    Livewire::actingAs($staff)
        ->test('pages::withdrawal-slips.create')
        ->set('date', now()->toDateString())
        ->set('items', [])
        ->call('save')
        ->assertHasErrors(['items', 'employee_id']);
});

test('an archived inventory item may not be withdrawn', function () {
    $staff = User::factory()->staff()->create();
    $inventory = Inventory::factory()->create();
    $inventory->delete();

    $component = fillWithdrawalSlip(
        Livewire::actingAs($staff)->test('pages::withdrawal-slips.create'),
        $inventory,
        1,
    );

    $component->call('save')->assertHasErrors('items.0.inventory_id');
});

test('the slip page shows the slip and its withdrawn items', function () {
    $staff = User::factory()->staff()->create();
    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'current_quantity' => '10',
    ]);

    $client = Client::factory()->create();
    $order = Order::factory()->create(['client_id' => $client->id]);
    $jobOrder = JobOrder::factory()->create(['order_id' => $order->id]);
    $employee = Employee::factory()->create();

    $slip = app(WithdrawalSlipService::class)->create([
        'date' => now()->toDateString(),
        'client_id' => $client->id,
        'job_order_id' => $jobOrder->id,
        'employee_id' => $employee->id,
        'items' => [[
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'particulars' => 'White cotton',
        ]],
    ], $staff->id);

    Livewire::actingAs($staff)
        ->test('pages::withdrawal-slips.show', ['withdrawalSlip' => $slip])
        ->assertSee($slip->slip_number)
        ->assertSee('COTTON FABRIC')
        ->assertSee('White cotton');
});

test('the slip can be printed', function () {
    $staff = User::factory()->staff()->create();
    $slip = WithdrawalSlip::factory()->create();

    $this->actingAs($staff)
        ->get(route('withdrawal-slips.print', $slip))
        ->assertOk()
        ->assertSee($slip->slip_number)
        ->assertSee(__('Withdrawal Slip'));
});

test('a manager may reverse a slip and the stock is put back once', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '10']);
    $slip = WithdrawalSlip::factory()->create();

    $slip->inventory()->attach($inventory->id, ['qty' => 4, 'particulars' => 'White cotton']);

    Livewire::actingAs($manager)
        ->test(WithdrawalSlipsTable::class)
        ->call('confirmReverse', $slip->id)
        ->call('reverseSlip')
        ->assertHasNoErrors();

    expect($inventory->fresh()->quantity())->toBe(14)
        ->and($slip->fresh()->trashed())->toBeTrue();

    $movement = $inventory->movements()->sole();

    expect($movement->type)->toBe(InventoryMovementType::Return)
        ->and($movement->quantity)->toBe(4)
        ->and($movement->created_by)->toBe($manager->id);
});

test('a slip may not be reversed twice', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '10']);
    $slip = WithdrawalSlip::factory()->create();

    $slip->inventory()->attach($inventory->id, ['qty' => 4, 'particulars' => 'White cotton']);

    $slips = app(WithdrawalSlipService::class);
    $slips->reverse($slip, $manager->id);

    expect(fn () => $slips->reverse($slip->fresh(), $manager->id))
        ->toThrow(ModelNotFoundException::class);

    expect($inventory->fresh()->quantity())->toBe(14);
});

test('a staff member may not reverse a slip', function () {
    $staff = User::factory()->staff()->create();
    $slip = WithdrawalSlip::factory()->create();

    Livewire::actingAs($staff)
        ->test(WithdrawalSlipsTable::class)
        ->call('confirmReverse', $slip->id)
        ->assertForbidden();
});

test('the slip list can be searched and filtered', function () {
    $staff = User::factory()->staff()->create();
    $client = Client::factory()->create(['name' => 'Silver Studio']);
    $otherClient = Client::factory()->create(['name' => 'Golden Workshop']);

    WithdrawalSlip::factory()->create([
        'slip_number' => 'WS-2026-000001',
        'client_id' => $client->id,
        'date' => '2026-01-10',
    ]);
    WithdrawalSlip::factory()->create([
        'slip_number' => 'WS-2026-000002',
        'client_id' => $otherClient->id,
        'date' => '2026-02-10',
    ]);

    Livewire::actingAs($staff)
        ->test(WithdrawalSlipsTable::class)
        ->set('search', 'WS-2026-000001')
        ->assertSee('Silver Studio')
        ->assertDontSee('WS-2026-000002');

    Livewire::actingAs($staff)
        ->test(WithdrawalSlipsTable::class)
        ->set('filterComponents.client', (string) $otherClient->id)
        ->assertSee('Golden Workshop')
        ->assertDontSee('WS-2026-000001');

    Livewire::actingAs($staff)
        ->test(WithdrawalSlipsTable::class)
        ->set('filterComponents.from', '2026-02-01')
        ->assertSee('WS-2026-000002')
        ->assertDontSee('WS-2026-000001');
});

test('an archived item stays on the withdrawal history', function () {
    $staff = User::factory()->staff()->create();
    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'current_quantity' => '10',
    ]);

    $client = Client::factory()->create();
    $order = Order::factory()->create(['client_id' => $client->id]);
    $jobOrder = JobOrder::factory()->create(['order_id' => $order->id]);

    $slip = app(WithdrawalSlipService::class)->create([
        'date' => now()->toDateString(),
        'client_id' => $client->id,
        'job_order_id' => $jobOrder->id,
        'employee_id' => Employee::factory()->create()->id,
        'items' => [[
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'particulars' => 'White cotton',
        ]],
    ], $staff->id);

    $inventory->delete();

    Livewire::actingAs($staff)
        ->test('pages::withdrawal-slips.show', ['withdrawalSlip' => $slip])
        ->assertSee('COTTON FABRIC')
        ->assertSee(__('Archived'));
});

test('the slip list shows the number of items and the withdrawn quantity', function () {
    $staff = User::factory()->staff()->create();
    $slip = WithdrawalSlip::factory()->create(['slip_number' => 'WS-2026-000020']);

    $first = Inventory::factory()->create();
    $second = Inventory::factory()->create();

    $slip->inventory()->attach($first->id, ['qty' => 2, 'particulars' => 'White cotton']);
    $slip->inventory()->attach($second->id, ['qty' => 3, 'particulars' => 'Blue thread']);

    $row = Livewire::actingAs($staff)
        ->test(WithdrawalSlipsTable::class)
        ->instance()
        ->getRows()
        ->sole();

    expect((int) $row->inventory_count)->toBe(2)
        ->and((int) $row->withdrawn_quantity)->toBe(5);
});

test('the withdrawal slip entry of the navigation is shown to a staff member', function () {
    $staff = User::factory()->staff()->create();

    $routes = collect(Navigation::groups())
        ->flatMap(fn ($group) => $group->items)
        ->filter(fn ($item) => $item->isVisibleTo($staff))
        ->map(fn ($item) => $item->route)
        ->all();

    expect($routes)->toContain('withdrawal-slips.index');
});

test('reversed slips leave the active list and can be found again', function () {
    $staff = User::factory()->staff()->create();

    $reversed = WithdrawalSlip::factory()->create(['slip_number' => 'WS-2026-000009']);
    $reversed->delete();

    WithdrawalSlip::factory()->create(['slip_number' => 'WS-2026-000010']);

    Livewire::actingAs($staff)
        ->test(WithdrawalSlipsTable::class)
        ->assertSee('WS-2026-000010')
        ->assertDontSee('WS-2026-000009');

    Livewire::actingAs($staff)
        ->test(WithdrawalSlipsTable::class)
        ->set('filterComponents.status', 'archived')
        ->assertSee('WS-2026-000009')
        ->assertDontSee('WS-2026-000010');
});
