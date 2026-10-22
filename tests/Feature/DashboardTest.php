<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->staff()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows the job order summary to a user that may see job orders', function () {
    $user = User::factory()->staff()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Job orders'))
        ->assertSee(__('Pending tasks'));
});

test('the dashboard counts the job orders that are overdue', function () {
    $user = User::factory()->staff()->create();

    $overdue = Order::factory()->overdue()->create();
    JobOrder::factory()->create(['order_id' => $overdue->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Overdue'));
});

test('the dashboard lists the job orders that are due', function () {
    $user = User::factory()->staff()->create();

    $order = Order::factory()->create(['due_date' => now()->addDays(3)]);
    JobOrder::factory()->create(['order_id' => $order->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Upcoming activities'))
        ->assertSee('#'.$order->id);
});

test('the dashboard does not list a job order that is completed', function () {
    $user = User::factory()->staff()->create();

    $order = Order::factory()->completed()->create(['due_date' => now()->addDays(3)]);
    JobOrder::factory()->create(['order_id' => $order->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('#'.$order->id);
});

test('the dashboard shows the employee summary', function () {
    $user = User::factory()->staff()->create();

    Employee::factory()->create(['position' => 'Artist']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Employees'))
        ->assertSee(__('Artists'));
});

test('the dashboard shows the inventory summary', function () {
    $user = User::factory()->staff()->create();

    Inventory::factory()->create(['current_quantity' => '0']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Inventory'))
        ->assertSee(__('Out of stock'));
});

test('the dashboard shows the client summary', function () {
    $user = User::factory()->staff()->create();

    Client::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Clients'));
});

test('the dashboard shows the cash receipt summary to a manager', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Cash receipts'))
        ->assertSee(__('Outstanding balance'));
});

test('the dashboard hides the financial summary from a user without financial permissions', function () {
    $user = User::factory()->create([
        'name' => 'No Financial Access',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('Cash receipts'))
        ->assertDontSee(__('Outstanding balance'));
});

test('the dashboard hides the modules the user may not open', function () {
    $user = User::factory()->create([
        'name' => 'No Module Access',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('Employees'))
        ->assertDontSee(__('Inventory'))
        ->assertSee(__('Nothing to show yet'));
});

test('the navigation only shows the modules the user is allowed to open', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('Admin Tools'));
});
