<?php

use App\Enums\JobOrderStatus;
use App\Livewire\JobOrders\JobOrdersTable;
use App\Models\Client;
use App\Models\ClientContactPerson;
use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\JobOrderAgent;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Models\User;
use App\Services\JobOrderService;
use Livewire\Livewire;

/**
 * Create a job order for the list tests.
 *
 * @param  array<string, mixed>  $overrides
 */
function createListedJobOrder(array $overrides = []): JobOrder
{
    $client = Client::factory()->create();
    $contactPerson = ClientContactPerson::factory()->create(['client_id' => $client->id]);
    $agent = JobOrderAgent::factory()->create();
    $employee = Employee::factory()->create();
    $productClass = ProductClass::factory()->create();
    $productSubclass = ProductSubclass::factory()->create(['product_class_id' => $productClass->id]);

    $data = array_merge([
        'client_id' => (string) $client->id,
        'contact_person_id' => (string) $contactPerson->id,
        'agent_id' => (string) $agent->id,
        'employee_id' => (string) $employee->id,
        'order_date' => '2026-01-15',
        'due_date' => '2027-01-30',
        'status' => JobOrderStatus::InProduction->value,
        'job_order_rating' => '5',
        'down_payment' => '0',
        'orderProducts' => [[
            'name' => 'ZTEST List Product',
            'particulars' => 'List product',
            'unit' => 'pc',
            'quantity' => '2',
            'unit_price' => '150.50',
            'product_class_id' => (string) $productClass->id,
            'subclass_id' => (string) $productSubclass->id,
        ]],
    ], $overrides);

    return app(JobOrderService::class)->create($data);
}

test('a staff member may open the job order list', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('job-orders.index'))
        ->assertOk()
        ->assertSee(__('Job orders'));
});

test('the list shows the number, client and amounts of a job order', function () {
    $staff = User::factory()->staff()->create();
    $jobOrder = createListedJobOrder(['down_payment' => '100.00']);

    $row = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('search', $jobOrder->job_order_number)
        ->instance()
        ->getRows()
        ->firstWhere('job_order_number', $jobOrder->job_order_number);

    expect($row->order->client->name)->toBe($jobOrder->order->client->name)
        ->and($row->order->total_amount)->toBe('301.00')
        ->and($row->order->balance)->toBe('201.00');
});

test('the list can be searched by the client name', function () {
    $staff = User::factory()->staff()->create();
    $first = createListedJobOrder();
    $second = createListedJobOrder();
    $second->order->client->update(['name' => 'ZTEST DISTINCT CLIENT']);

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('search', 'ZTEST DISTINCT CLIENT')
        ->instance()
        ->getRows();

    expect($rows->pluck('id')->all())->toContain($second->id)
        ->not->toContain($first->id);
});

test('the list can be filtered by status', function () {
    $staff = User::factory()->staff()->create();
    $open = createListedJobOrder();
    $completed = createListedJobOrder(['status' => JobOrderStatus::CompletedClaimed->value]);

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('filterComponents.status', JobOrderStatus::CompletedClaimed->value)
        ->instance()
        ->getRows();

    expect($rows->pluck('id')->all())->toContain($completed->id)
        ->not->toContain($open->id);
});

test('the list can be filtered by payment status', function () {
    $staff = User::factory()->staff()->create();
    $unpaid = createListedJobOrder();
    $paid = createListedJobOrder(['down_payment' => '301.00']);

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('filterComponents.payment', 'Fully Paid')
        ->instance()
        ->getRows();

    expect($rows->pluck('id')->all())->toContain($paid->id)
        ->not->toContain($unpaid->id);
});

test('the list can be filtered by the artist in charge', function () {
    $staff = User::factory()->staff()->create();
    $employee = Employee::factory()->create();
    $otherEmployee = Employee::factory()->create();
    $assigned = createListedJobOrder(['employee_id' => (string) $employee->id]);
    $other = createListedJobOrder(['employee_id' => (string) $otherEmployee->id]);

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('filterComponents.artist', (string) $employee->id)
        ->instance()
        ->getRows();

    expect($rows->pluck('id')->all())->toContain($assigned->id)
        ->not->toContain($other->id);
});

test('the list can be filtered by the agent', function () {
    $staff = User::factory()->staff()->create();
    $agent = JobOrderAgent::factory()->create();
    $otherAgent = JobOrderAgent::factory()->create();
    $throughAgent = createListedJobOrder(['agent_id' => (string) $agent->id]);
    $throughOther = createListedJobOrder(['agent_id' => (string) $otherAgent->id]);

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('filterComponents.agent', (string) $agent->id)
        ->instance()
        ->getRows();

    expect($rows->pluck('id')->all())->toContain($throughAgent->id)
        ->not->toContain($throughOther->id);
});

test('the list can be filtered to the overdue job orders', function () {
    $staff = User::factory()->staff()->create();
    $overdue = createListedJobOrder(['order_date' => '2026-01-01', 'due_date' => '2026-01-05']);
    $upcoming = createListedJobOrder(['order_date' => '2026-01-01', 'due_date' => '2027-01-05']);
    $completed = createListedJobOrder([
        'order_date' => '2026-01-01',
        'due_date' => '2026-01-05',
        'status' => JobOrderStatus::CompletedClaimed->value,
    ]);

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('filterComponents.due', 'overdue')
        ->instance()
        ->getRows();

    expect($rows->pluck('id')->all())->toContain($overdue->id)
        ->not->toContain($upcoming->id)
        ->not->toContain($completed->id);
});

test('archived job orders are not listed', function () {
    $staff = User::factory()->staff()->create();
    $archived = createListedJobOrder();
    $archived->delete();
    $active = createListedJobOrder();

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('search', $archived->job_order_number)
        ->instance()
        ->getRows();

    expect($rows)->toBeEmpty();

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('search', $active->job_order_number)
        ->instance()
        ->getRows();

    expect($rows->pluck('id')->all())->toContain($active->id);
});

test('the list can be sorted by the job order number', function () {
    $staff = User::factory()->staff()->create();
    $first = createListedJobOrder();
    $second = createListedJobOrder();

    $rows = Livewire::actingAs($staff)
        ->test(JobOrdersTable::class)
        ->set('search', 'JO-2026')
        ->set('sorts', ['job_order_number' => 'desc'])
        ->instance()
        ->getRows();

    expect($rows->first()->job_order_number)->toBe($second->job_order_number)
        ->and($rows->last()->job_order_number)->toBe($first->job_order_number);
});
