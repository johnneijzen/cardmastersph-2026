<?php

use App\Enums\JobOrderStatus;
use App\Models\CashReceipt;
use App\Models\Client;
use App\Models\ClientContactPerson;
use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\JobOrderAgent;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Models\User;
use App\Services\JobOrderService;

/**
 * Create a job order that can be printed.
 */
function createPrintableJobOrder(?int $userId = null): JobOrder
{
    $client = Client::factory()->create();
    $contactPerson = ClientContactPerson::factory()->create(['client_id' => $client->id]);
    $agent = JobOrderAgent::factory()->create();
    $employee = Employee::factory()->create();
    $productClass = ProductClass::factory()->create();
    $productSubclass = ProductSubclass::factory()->create(['product_class_id' => $productClass->id]);

    return app(JobOrderService::class)->create([
        'client_id' => (string) $client->id,
        'contact_person_id' => (string) $contactPerson->id,
        'agent_id' => (string) $agent->id,
        'employee_id' => (string) $employee->id,
        'order_date' => '2026-01-15',
        'due_date' => '2026-01-30',
        'status' => JobOrderStatus::InProduction->value,
        'job_order_rating' => '5',
        'down_payment' => '100.00',
        'orderProducts' => [[
            'name' => 'ZTEST Printed Banner',
            'particulars' => 'Outdoor banner',
            'unit' => 'pc',
            'quantity' => '2',
            'unit_price' => '150.50',
            'product_class_id' => (string) $productClass->id,
            'subclass_id' => (string) $productSubclass->id,
        ]],
    ], $userId);
}

test('a guest is redirected to the login page for the printed documents', function (string $route) {
    $jobOrder = createPrintableJobOrder();

    $this->get(route($route, $jobOrder))
        ->assertRedirect(route('login'));
})->with([
    'job-orders.print',
    'job-orders.routing-slip',
]);

test('a user without the permission may not print the job order documents', function (string $route) {
    $jobOrder = createPrintableJobOrder();

    $this->actingAs(User::factory()->create())
        ->get(route($route, $jobOrder))
        ->assertForbidden();
})->with([
    'job-orders.print',
    'job-orders.routing-slip',
]);

test('the printed invoice shows the client, the line items and the amounts', function () {
    $staff = User::factory()->staff()->create();
    $jobOrder = createPrintableJobOrder($staff->id);

    $this->actingAs($staff)
        ->get(route('job-orders.print', $jobOrder))
        ->assertOk()
        ->assertSee($jobOrder->job_order_number)
        ->assertSee($jobOrder->order->client->name)
        ->assertSee('ZTEST Printed Banner')
        ->assertSee($jobOrder->order->products->first()->product_class->name)
        ->assertSee($jobOrder->order->products->first()->product_subclass->name)
        ->assertSee('301.00')
        ->assertSee(__('Total amount'))
        ->assertSee(__('Balance'))
        ->assertSee(__('Payment status'));
});

test('the printed invoice shows the payments that were received', function () {
    $staff = User::factory()->staff()->create();
    $jobOrder = createPrintableJobOrder($staff->id);

    CashReceipt::factory()->create([
        'client_id' => $jobOrder->order->client_id,
        'job_order_id' => $jobOrder->id,
        'amount' => '50.00',
        'or_number' => 'OR-PRINT-1',
    ]);

    $this->actingAs($staff)
        ->get(route('job-orders.print', $jobOrder))
        ->assertOk()
        ->assertSee('OR-PRINT-1')
        ->assertSee('50.00');
});

test('the printed routing slip shows the line items and the department sign-off table', function () {
    $staff = User::factory()->staff()->create();
    $jobOrder = createPrintableJobOrder($staff->id);

    $this->actingAs($staff)
        ->get(route('job-orders.routing-slip', $jobOrder))
        ->assertOk()
        ->assertSee($jobOrder->job_order_number)
        ->assertSee($jobOrder->order->client->name)
        ->assertSee('ZTEST Printed Banner')
        ->assertSee($jobOrder->order->products->first()->product_subclass->name)
        ->assertSee(__('Routing Slip'))
        ->assertSee(__('Artist'))
        ->assertSee(__('Printer'))
        ->assertSee(__('Heatpress'))
        ->assertSee(__('Fabrication'))
        ->assertSee(__('Stickering'))
        ->assertSee(__('Packing'))
        ->assertSee(__('Embroidery'))
        ->assertSee(__('Completed'));
});

test('an archived job order can still be printed for its history', function () {
    $staff = User::factory()->staff()->create();
    $jobOrder = createPrintableJobOrder($staff->id);
    $jobOrder->delete();

    $this->actingAs($staff)
        ->get(route('job-orders.print', $jobOrder))
        ->assertOk()
        ->assertSee($jobOrder->job_order_number);
});

test('the job order list offers the invoice and the routing slip', function () {
    $staff = User::factory()->staff()->create();
    $jobOrder = createPrintableJobOrder($staff->id);

    $this->actingAs($staff)
        ->get(route('job-orders.index'))
        ->assertOk()
        ->assertSee(route('job-orders.print', $jobOrder), false)
        ->assertSee(route('job-orders.routing-slip', $jobOrder), false);
});
