<?php

use App\Enums\JobOrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\JobOrders\JobOrdersTable;
use App\Models\CashReceipt;
use App\Models\Client;
use App\Models\ClientContactPerson;
use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\JobOrderAgent;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Models\User;
use App\Services\JobOrderService;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

/**
 * Build the data of a valid job order.
 *
 * @return array<string, mixed>
 */
function jobOrderDraft(array $overrides = []): array
{
    $client = Client::factory()->create();
    $contactPerson = ClientContactPerson::factory()->create(['client_id' => $client->id]);
    $agent = JobOrderAgent::factory()->create();
    $employee = Employee::factory()->create();
    $productClass = ProductClass::factory()->create();
    $productSubclass = ProductSubclass::factory()->create(['product_class_id' => $productClass->id]);

    return array_merge([
        'client_id' => (string) $client->id,
        'contact_person_id' => (string) $contactPerson->id,
        'agent_id' => (string) $agent->id,
        'employee_id' => (string) $employee->id,
        'order_date' => '2026-01-15',
        'due_date' => '2026-01-30',
        'status' => JobOrderStatus::InProduction->value,
        'job_order_rating' => '5',
        'down_payment' => '0',
        'purchase_order_id' => '',
        'delivery_receipt_id' => '',
        'billing_statement_id' => '',
        'overall_remarks' => '',
        'orderProducts' => [
            [
                'name' => 'ZTEST Banner',
                'particulars' => 'Outdoor banner',
                'unit' => 'pc',
                'quantity' => '2',
                'unit_price' => '150.50',
                'product_class_id' => (string) $productClass->id,
                'subclass_id' => (string) $productSubclass->id,
            ],
        ],
        'client' => $client,
        'contactPerson' => $contactPerson,
        'agent' => $agent,
        'employee' => $employee,
        'productClass' => $productClass,
        'productSubclass' => $productSubclass,
    ], $overrides);
}

/**
 * Create a job order through the service.
 *
 * @param  array<string, mixed>  $overrides
 */
function createJobOrder(array $overrides = [], ?int $userId = null): JobOrder
{
    return app(JobOrderService::class)->create(jobOrderDraft($overrides), $userId);
}

test('a guest is redirected to the login page', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'job-orders.index',
    'job-orders.create',
]);

test('a user without the permission may not open the job order list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('job-orders.index'))
        ->assertForbidden();
});

test('a user without the permission may not search the job orders', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(JobOrdersTable::class)
        ->assertForbidden();
});

test('a staff member may add a job order', function () {
    $staff = User::factory()->staff()->create();
    $draft = jobOrderDraft();

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->set('client_id', $draft['client_id'])
        ->set('contact_person_id', $draft['contact_person_id'])
        ->set('agent_id', $draft['agent_id'])
        ->set('employee_id', $draft['employee_id'])
        ->set('order_date', $draft['order_date'])
        ->set('due_date', $draft['due_date'])
        ->set('status', $draft['status'])
        ->set('job_order_rating', $draft['job_order_rating'])
        ->set('down_payment', '100.00')
        ->set('orderProducts', $draft['orderProducts'])
        ->call('save')
        ->assertHasNoErrors();

    $jobOrder = JobOrder::query()->sole();
    $order = $jobOrder->order;
    $product = $order->products()->sole();

    expect($jobOrder->job_order_number)->toMatch('/^JO-2026-\d{6}$/')
        ->and($jobOrder->employee_id)->toBe((int) $draft['employee_id'])
        ->and($jobOrder->job_order_rating)->toBe(5)
        ->and($jobOrder->created_by)->toBe($staff->id)
        ->and($order->client_id)->toBe((int) $draft['client_id'])
        ->and($order->status)->toBe(JobOrderStatus::InProduction->value)
        ->and($order->total_amount)->toBe('301.00')
        ->and($order->down_payment)->toBe('100.00')
        ->and($order->balance)->toBe('201.00')
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyPaid->value)
        ->and($product->amount)->toBe('301.00')
        ->and($product->unit_price)->toBe('150.50')
        ->and((int) $product->pivot->qty)->toBe(2);
});

test('the job order form rejects an incomplete job order', function () {
    $staff = User::factory()->staff()->create();

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->call('save')
        ->assertHasErrors([
            'client_id',
            'contact_person_id',
            'agent_id',
            'employee_id',
            'orderProducts.0.name',
            'orderProducts.0.unit_price',
            'orderProducts.0.product_class_id',
            'orderProducts.0.subclass_id',
        ]);
});

test('the due date may not be before the order date', function () {
    $staff = User::factory()->staff()->create();
    $draft = jobOrderDraft(['due_date' => '2026-01-01']);

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->set('client_id', $draft['client_id'])
        ->set('contact_person_id', $draft['contact_person_id'])
        ->set('agent_id', $draft['agent_id'])
        ->set('employee_id', $draft['employee_id'])
        ->set('order_date', $draft['order_date'])
        ->set('due_date', $draft['due_date'])
        ->set('orderProducts', $draft['orderProducts'])
        ->call('save')
        ->assertHasErrors('due_date');
});

test('the unit price has to be an exact amount with at most two decimals', function () {
    $staff = User::factory()->staff()->create();
    $draft = jobOrderDraft();
    $line = $draft['orderProducts'][0];
    $line['unit_price'] = '150.555';

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->set('client_id', $draft['client_id'])
        ->set('contact_person_id', $draft['contact_person_id'])
        ->set('agent_id', $draft['agent_id'])
        ->set('employee_id', $draft['employee_id'])
        ->set('order_date', $draft['order_date'])
        ->set('due_date', $draft['due_date'])
        ->set('orderProducts', [$line])
        ->call('save')
        ->assertHasErrors('orderProducts.0.unit_price');
});

test('an amount larger than the job order column can store is rejected', function () {
    $staff = User::factory()->staff()->create();
    $draft = jobOrderDraft();
    $line = $draft['orderProducts'][0];
    $line['unit_price'] = '100000000000.00';

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->set('client_id', $draft['client_id'])
        ->set('contact_person_id', $draft['contact_person_id'])
        ->set('agent_id', $draft['agent_id'])
        ->set('employee_id', $draft['employee_id'])
        ->set('order_date', $draft['order_date'])
        ->set('due_date', $draft['due_date'])
        ->set('orderProducts', [$line])
        ->call('save')
        ->assertHasErrors('orderProducts.0.unit_price');
});

test('the subclass has to belong to the chosen class', function () {
    $staff = User::factory()->staff()->create();
    $draft = jobOrderDraft();
    $otherSubclass = ProductSubclass::factory()->create();
    $line = $draft['orderProducts'][0];
    $line['subclass_id'] = (string) $otherSubclass->id;

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->set('client_id', $draft['client_id'])
        ->set('contact_person_id', $draft['contact_person_id'])
        ->set('agent_id', $draft['agent_id'])
        ->set('employee_id', $draft['employee_id'])
        ->set('order_date', $draft['order_date'])
        ->set('due_date', $draft['due_date'])
        ->set('orderProducts', [$line])
        ->call('save')
        ->assertHasErrors('orderProducts.0');
});

test('the contract person has to belong to the chosen client', function () {
    $staff = User::factory()->staff()->create();
    $draft = jobOrderDraft();
    $otherContactPerson = ClientContactPerson::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->set('client_id', $draft['client_id'])
        ->set('contact_person_id', (string) $otherContactPerson->id)
        ->set('agent_id', $draft['agent_id'])
        ->set('employee_id', $draft['employee_id'])
        ->set('order_date', $draft['order_date'])
        ->set('due_date', $draft['due_date'])
        ->set('orderProducts', $draft['orderProducts'])
        ->call('save')
        ->assertHasErrors('contact_person_id');
});

test('an archived client cannot be chosen for a new job order', function () {
    $staff = User::factory()->staff()->create();
    $draft = jobOrderDraft();
    $draft['client']->delete();

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->set('client_id', $draft['client_id'])
        ->set('contact_person_id', $draft['contact_person_id'])
        ->set('agent_id', $draft['agent_id'])
        ->set('employee_id', $draft['employee_id'])
        ->set('order_date', $draft['order_date'])
        ->set('due_date', $draft['due_date'])
        ->set('orderProducts', $draft['orderProducts'])
        ->call('save')
        ->assertHasErrors('client_id');

    expect(JobOrder::query()->count())->toBe(0);
});

test('the down payment may not be more than the total amount', function () {
    $staff = User::factory()->staff()->create();
    $draft = jobOrderDraft(['down_payment' => '1000.00']);

    Livewire::actingAs($staff)
        ->test('pages::job-orders.create')
        ->set('client_id', $draft['client_id'])
        ->set('contact_person_id', $draft['contact_person_id'])
        ->set('agent_id', $draft['agent_id'])
        ->set('employee_id', $draft['employee_id'])
        ->set('order_date', $draft['order_date'])
        ->set('due_date', $draft['due_date'])
        ->set('down_payment', $draft['down_payment'])
        ->set('orderProducts', $draft['orderProducts'])
        ->call('save')
        ->assertHasErrors('down_payment');

    expect(JobOrder::query()->count())->toBe(0);
});

test('two job orders receive consecutive numbers', function () {
    $first = createJobOrder();
    $second = createJobOrder();

    expect($first->job_order_number)->toBe('JO-2026-000001')
        ->and($second->job_order_number)->toBe('JO-2026-000002');
});

test('a manager may change a job order and its line items', function () {
    $manager = User::factory()->manager()->create();
    $jobOrder = createJobOrder([], $manager->id);
    $number = $jobOrder->job_order_number;
    $product = $jobOrder->order->products()->sole();

    Livewire::actingAs($manager)
        ->test('pages::job-orders.edit', ['jobOrder' => $jobOrder])
        ->set('down_payment', '50.00')
        ->set('orderProducts', [[
            'id' => (string) $product->id,
            'name' => 'ZTEST Banner Updated',
            'particulars' => 'Indoor banner',
            'unit' => 'pc',
            'quantity' => '3',
            'unit_price' => '100.00',
            'product_class_id' => (string) $jobOrder->order->products->first()->class_id,
            'subclass_id' => (string) $jobOrder->order->products->first()->subclass_id,
        ]])
        ->call('save')
        ->assertHasNoErrors();

    $jobOrder->refresh();
    $order = $jobOrder->order;
    $product->refresh();

    expect($jobOrder->job_order_number)->toBe($number)
        ->and($order->total_amount)->toBe('300.00')
        ->and($order->down_payment)->toBe('50.00')
        ->and($order->balance)->toBe('250.00')
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyPaid->value)
        ->and($product->name)->toBe('ZTEST Banner Updated')
        ->and($product->amount)->toBe('300.00');
});

test('a line item can be added and removed on the edit form', function () {
    $manager = User::factory()->manager()->create();
    $jobOrder = createJobOrder([], $manager->id);
    $firstProduct = $jobOrder->order->products()->sole();
    $draft = jobOrderDraft();
    $secondLine = $draft['orderProducts'][0];
    $secondLine['name'] = 'ZTEST Sticker';

    Livewire::actingAs($manager)
        ->test('pages::job-orders.edit', ['jobOrder' => $jobOrder])
        ->set('orderProducts', [
            [
                'id' => (string) $firstProduct->id,
                'name' => $firstProduct->name,
                'particulars' => (string) $firstProduct->particulars,
                'unit' => (string) $firstProduct->unit,
                'quantity' => '2',
                'unit_price' => (string) $firstProduct->unit_price,
                'product_class_id' => (string) $firstProduct->class_id,
                'subclass_id' => (string) $firstProduct->subclass_id,
            ],
            [
                'id' => '',
                'name' => $secondLine['name'],
                'particulars' => $secondLine['particulars'],
                'unit' => $secondLine['unit'],
                'quantity' => '1',
                'unit_price' => '25.00',
                'product_class_id' => $secondLine['product_class_id'],
                'subclass_id' => $secondLine['subclass_id'],
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect($jobOrder->fresh()->order->products()->count())->toBe(2);
});

test('the balance of an edited job order includes the payments already received', function () {
    $manager = User::factory()->manager()->create();
    $jobOrder = createJobOrder([], $manager->id);
    $product = $jobOrder->order->products()->sole();

    CashReceipt::factory()->create([
        'client_id' => $jobOrder->order->client_id,
        'job_order_id' => $jobOrder->id,
        'amount' => '50.00',
    ]);

    Livewire::actingAs($manager)
        ->test('pages::job-orders.edit', ['jobOrder' => $jobOrder])
        ->set('orderProducts', [[
            'id' => (string) $product->id,
            'name' => $product->name,
            'particulars' => (string) $product->particulars,
            'unit' => (string) $product->unit,
            'quantity' => '2',
            'unit_price' => '150.50',
            'product_class_id' => (string) $product->class_id,
            'subclass_id' => (string) $product->subclass_id,
        ]])
        ->call('save')
        ->assertHasNoErrors();

    expect($jobOrder->fresh()->order->balance)->toBe('251.00')
        ->and($jobOrder->fresh()->order->payment_status)->toBe(PaymentStatus::PartiallyPaid->value);
});

test('a removed line item is detached from the invoice', function () {
    $manager = User::factory()->manager()->create();
    $jobOrder = createJobOrder([], $manager->id);
    $product = $jobOrder->order->products()->sole();
    $draft = jobOrderDraft();
    $secondLine = $draft['orderProducts'][0];
    $secondLine['name'] = 'ZTEST Sticker';

    $secondProduct = Product::create([
        'name' => $secondLine['name'],
        'amount' => '25.00',
        'particulars' => $secondLine['particulars'],
        'unit' => $secondLine['unit'],
        'unit_price' => '25.00',
        'class_id' => $secondLine['product_class_id'],
        'subclass_id' => $secondLine['subclass_id'],
    ]);
    $jobOrder->order->products()->attach($secondProduct->id, ['qty' => 1]);

    Livewire::actingAs($manager)
        ->test('pages::job-orders.edit', ['jobOrder' => $jobOrder])
        ->set('orderProducts', [[
            'id' => (string) $product->id,
            'name' => $product->name,
            'particulars' => (string) $product->particulars,
            'unit' => (string) $product->unit,
            'quantity' => '2',
            'unit_price' => (string) $product->unit_price,
            'product_class_id' => (string) $product->class_id,
            'subclass_id' => (string) $product->subclass_id,
        ]])
        ->call('save')
        ->assertHasNoErrors();

    expect($jobOrder->fresh()->order->products()->count())->toBe(1)
        ->and($jobOrder->fresh()->order->products()->first()->getKey())->toBe($product->id)
        ->and(Product::query()->find($secondProduct->id))->toBeNull();
});

test('a job order of an archived client can still be edited', function () {
    $manager = User::factory()->manager()->create();
    $jobOrder = createJobOrder([], $manager->id);
    $product = $jobOrder->order->products()->sole();
    $clientId = $jobOrder->order->client_id;

    $jobOrder->order->client->delete();

    Livewire::actingAs($manager)
        ->test('pages::job-orders.edit', ['jobOrder' => $jobOrder])
        ->set('orderProducts', [[
            'id' => (string) $product->id,
            'name' => $product->name,
            'particulars' => (string) $product->particulars,
            'unit' => (string) $product->unit,
            'quantity' => '2',
            'unit_price' => (string) $product->unit_price,
            'product_class_id' => (string) $product->class_id,
            'subclass_id' => (string) $product->subclass_id,
        ]])
        ->call('save')
        ->assertHasNoErrors();

    expect($jobOrder->fresh()->order->client_id)->toBe($clientId);
});

test('a manager may archive a job order without payments', function () {
    $manager = User::factory()->manager()->create();
    $jobOrder = createJobOrder([], $manager->id);
    $orderId = $jobOrder->order_id;

    Livewire::actingAs($manager)
        ->test(JobOrdersTable::class)
        ->call('confirmArchive', $jobOrder->id)
        ->call('archiveJobOrder')
        ->assertHasNoErrors();

    expect(JobOrder::withTrashed()->find($jobOrder->id)->trashed())->toBeTrue()
        ->and(Order::query()->find($orderId))->not->toBeNull();
});

test('a job order with a payment cannot be archived', function () {
    $manager = User::factory()->manager()->create();
    $jobOrder = createJobOrder([], $manager->id);

    CashReceipt::factory()->create([
        'client_id' => $jobOrder->order->client_id,
        'job_order_id' => $jobOrder->id,
        'amount' => '10.00',
    ]);

    Livewire::actingAs($manager)
        ->test(JobOrdersTable::class)
        ->call('confirmArchive', $jobOrder->id)
        ->call('archiveJobOrder')
        ->assertHasErrors('jobOrder');

    expect($jobOrder->fresh())->not->toBeNull();
});

test('a staff member may not archive a job order', function () {
    $jobOrder = createJobOrder();

    expect(Gate::forUser(User::factory()->staff()->create())->allows('archive', $jobOrder))->toBeFalse();
});

test('the job order details show its client, line items and amounts', function () {
    $staff = User::factory()->staff()->create();
    $jobOrder = createJobOrder([], $staff->id);

    Livewire::actingAs($staff)
        ->test('pages::job-orders.show', ['jobOrder' => $jobOrder])
        ->assertSee($jobOrder->job_order_number)
        ->assertSee($jobOrder->order->client->name)
        ->assertSee('ZTEST Banner')
        ->assertSee(JobOrderStatus::InProduction->value);
});

test('an archived job order can still be opened for its history', function () {
    $staff = User::factory()->staff()->create();
    $jobOrder = createJobOrder();
    $jobOrder->delete();

    $this->actingAs($staff)
        ->get(route('job-orders.show', $jobOrder))
        ->assertOk()
        ->assertSee($jobOrder->job_order_number);
});

test('a user without the permission may not open the job order details', function () {
    $jobOrder = createJobOrder();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::job-orders.show', ['jobOrder' => $jobOrder])
        ->assertForbidden();
});

test('a user without the permission may not open the edit page', function () {
    $jobOrder = createJobOrder();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::job-orders.edit', ['jobOrder' => $jobOrder])
        ->assertForbidden();
});
