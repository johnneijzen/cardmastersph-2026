<?php

use App\Livewire\Employees\EmployeesTable;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

test('a guest is redirected to the login page', function () {
    $this->get(route('employees.index'))
        ->assertRedirect(route('login'));
});

test('a user without the permission may not open the employee list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('employees.index'))
        ->assertForbidden();
});

test('a manager may open the employee list', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertSee(__('Employees'));
});

test('the list shows the employees', function () {
    $manager = User::factory()->manager()->create();

    Employee::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->assertSee('Juan')
        ->assertSee('Dela Cruz');
});

test('the list can be searched', function () {
    $manager = User::factory()->manager()->create();

    Employee::factory()->create(['first_name' => 'Juan']);
    Employee::factory()->create(['first_name' => 'Maria']);

    Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->set('search', 'Juan')
        ->assertSee('Juan')
        ->assertDontSee('Maria');
});

test('the list can be searched by the e-mail address of the account', function () {
    $manager = User::factory()->manager()->create();
    $user = User::factory()->create(['email' => 'juan@example.com']);

    Employee::factory()->forUser($user)->create(['first_name' => 'Juan']);
    Employee::factory()->create(['first_name' => 'Maria']);

    Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->set('search', 'juan@example.com')
        ->assertSee('Juan')
        ->assertDontSee('Maria');
});

test('the list can be filtered by position', function () {
    $manager = User::factory()->manager()->create();

    Employee::factory()->create(['first_name' => 'ArtistWorker', 'position' => 'Artist']);
    Employee::factory()->create(['first_name' => 'SupervisorWorker', 'position' => 'Supervisor']);

    Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->set('filterComponents.position', 'Supervisor')
        ->assertSee('SupervisorWorker')
        ->assertDontSee('ArtistWorker');
});

test('the list can be filtered by the status of the account', function () {
    $manager = User::factory()->manager()->create();

    Employee::factory()->forUser(User::factory()->create())->create(['first_name' => 'WithAccount']);
    Employee::factory()->create(['first_name' => 'WithoutAccount']);

    Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->set('filterComponents.account', 'without')
        ->assertSee('WithoutAccount')
        ->assertDontSee('WithAccount');
});

test('the list is paginated', function () {
    $manager = User::factory()->manager()->create();

    Employee::factory()->count(12)->create();

    $component = Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->set('perPage', 10);

    expect($component->instance()->getRows())->toHaveCount(10);

    $component->call('gotoPage', 2, $component->instance()->getComputedPageName());

    expect($component->instance()->getRows())->toHaveCount(2);
});

test('a user without the permission may not search the employees', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EmployeesTable::class)
        ->assertForbidden();
});
