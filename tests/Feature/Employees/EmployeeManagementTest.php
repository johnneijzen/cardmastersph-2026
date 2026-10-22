<?php

use App\Enums\Role;
use App\Livewire\Employees\EmployeesTable;
use App\Models\Address;
use App\Models\City;
use App\Models\Employee;
use App\Models\JobOrder;
use App\Models\User;
use App\Services\EmployeeService;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

test('a guest is redirected to the login page', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'employees.create',
    'employees.index',
]);

test('a user without the permission may not open the create page', function () {
    $user = User::factory()->staff()->create();

    $this->actingAs($user)
        ->get(route('employees.create'))
        ->assertForbidden();
});

test('a manager may open the create page', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('employees.create'))
        ->assertOk()
        ->assertSee(__('Add employee'));
});

test('a manager may add an employee', function () {
    $manager = User::factory()->manager()->create();
    $city = City::factory()->create();

    Livewire::actingAs($manager)
        ->test('pages::employees.create')
        ->set('first_name', 'Juan')
        ->set('last_name', 'Dela Cruz')
        ->set('position', 'Artist')
        ->set('birthday', '1990-05-04')
        ->set('contact_number', '09171234567')
        ->set('emergency_contact_person_name', 'Maria Dela Cruz')
        ->set('emergency_contact_person_number', '09179876543')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->call('save')
        ->assertHasNoErrors();

    $employee = Employee::query()->where('first_name', 'Juan')->firstOrFail();

    expect($employee->last_name)->toBe('Dela Cruz')
        ->and($employee->birthday->format('Y-m-d'))->toBe('1990-05-04')
        ->and($employee->address->line_address)->toBe('12 San Jose Street')
        ->and($employee->address->city_id)->toBe($city->id)
        ->and($employee->user_id)->toBeNull();
});

test('the account of a new employee is created with a role and a temporary password', function () {
    $manager = User::factory()->manager()->create();
    $city = City::factory()->create();

    $component = Livewire::actingAs($manager)
        ->test('pages::employees.create')
        ->set('first_name', 'Juan')
        ->set('last_name', 'Dela Cruz')
        ->set('position', 'Artist')
        ->set('birthday', '1990-05-04')
        ->set('contact_number', '09171234567')
        ->set('emergency_contact_person_name', 'Maria Dela Cruz')
        ->set('emergency_contact_person_number', '09179876543')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->set('create_account', true)
        ->set('email', 'juan@example.com')
        ->set('role', Role::Manager->value)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'juan@example.com')->firstOrFail();
    $employee = Employee::query()->where('first_name', 'Juan')->firstOrFail();

    expect($user->must_change_password)->toBeTrue()
        ->and($user->hasRole(Role::Manager))->toBeTrue()
        ->and($employee->user_id)->toBe($user->id)
        ->and($component->get('issuedPassword'))->toBeString()->not->toBeEmpty();

    $component->call('finish')->assertRedirect(route('employees.show', $employee));
});

test('the employee form rejects an incomplete employee', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::employees.create')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name', 'birthday', 'contact_number', 'line_address', 'province_id', 'city_id']);
});

test('the e-mail address of the account has to be unique', function () {
    $manager = User::factory()->manager()->create();
    $city = City::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($manager)
        ->test('pages::employees.create')
        ->set('first_name', 'Juan')
        ->set('last_name', 'Dela Cruz')
        ->set('position', 'Artist')
        ->set('birthday', '1990-05-04')
        ->set('contact_number', '09171234567')
        ->set('emergency_contact_person_name', 'Maria Dela Cruz')
        ->set('emergency_contact_person_number', '09179876543')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->set('create_account', true)
        ->set('email', 'taken@example.com')
        ->set('role', Role::Staff->value)
        ->call('save')
        ->assertHasErrors('email');
});

test('a user without the permission may not open the details of an employee', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('employees.show', $employee))
        ->assertForbidden();
});

test('a manager may see the details of an employee', function () {
    $employee = Employee::factory()->create(['first_name' => 'Juan', 'last_name' => 'Dela Cruz']);

    $this->actingAs(User::factory()->manager()->create())
        ->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('Juan Dela Cruz');
});

test('a manager may change an employee', function () {
    $manager = User::factory()->manager()->create();
    $employee = Employee::factory()->create(['first_name' => 'Juan']);
    $city = City::factory()->create();

    Livewire::actingAs($manager)
        ->test('pages::employees.edit', ['employee' => $employee])
        ->set('first_name', 'Jose')
        ->set('last_name', 'Dela Cruz')
        ->set('position', 'Artist')
        ->set('birthday', '1990-05-04')
        ->set('contact_number', '09171234567')
        ->set('emergency_contact_person_name', 'Maria Dela Cruz')
        ->set('emergency_contact_person_number', '09179876543')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($employee->fresh()->first_name)->toBe('Jose')
        ->and($employee->fresh()->address->line_address)->toBe('12 San Jose Street');
});

test('a user without the permission may not change an employee', function () {
    $employee = Employee::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('pages::employees.edit', ['employee' => $employee])
        ->assertForbidden();
});

test('an employee without job orders is removed with the account and the address', function () {
    $manager = User::factory()->manager()->create();
    $user = User::factory()->create();
    $employee = Employee::factory()->forUser($user)->create();
    $addressId = $employee->address_id;

    Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->call('confirmDelete', $employee->id)
        ->call('deleteEmployee')
        ->assertHasNoErrors();

    expect(Employee::query()->whereKey($employee->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(Address::query()->whereKey($addressId)->exists())->toBeFalse();
});

test('an employee that is the artist in charge of a job order cannot be removed', function () {
    $manager = User::factory()->manager()->create();
    $employee = Employee::factory()->create();
    JobOrder::factory()->create(['employee_id' => $employee->id]);

    Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->call('confirmDelete', $employee->id)
        ->call('deleteEmployee')
        ->assertHasErrors('employee');

    expect(Employee::query()->whereKey($employee->id)->exists())->toBeTrue();
});

test('the account of a removed employee is deactivated when a job order points at it', function () {
    $manager = User::factory()->manager()->create();
    $user = User::factory()->create();
    $employee = Employee::factory()->forUser($user)->create();

    JobOrder::factory()->create([
        'employee_id' => Employee::factory()->create()->id,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($manager)
        ->test(EmployeesTable::class)
        ->call('confirmDelete', $employee->id)
        ->call('deleteEmployee')
        ->assertHasNoErrors();

    expect(Employee::query()->whereKey($employee->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($user->id)->exists())->toBeTrue()
        ->and($user->fresh()->is_active)->toBeFalse();
});

test('a user without the permission may not remove an employee', function () {
    $employee = Employee::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test(EmployeesTable::class)
        ->call('confirmDelete', $employee->id)
        ->assertForbidden();
});

test('a manager may deactivate the account of an employee', function () {
    $manager = User::factory()->manager()->create();
    $user = User::factory()->create();
    $employee = Employee::factory()->forUser($user)->create();

    Livewire::actingAs($manager)
        ->test('pages::employees.show', ['employee' => $employee])
        ->call('toggleAccountStatus')
        ->assertHasNoErrors();

    expect($user->fresh()->is_active)->toBeFalse();
});

test('a deactivated account may no longer sign in', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->forUser($user)->create();

    app(EmployeeService::class)->setAccountStatus($employee, false);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('a manager may give the account of an employee another role', function () {
    $manager = User::factory()->manager()->create();
    $user = User::factory()->staff()->create();
    $employee = Employee::factory()->forUser($user)->create();

    Livewire::actingAs($manager)
        ->test('pages::employees.show', ['employee' => $employee])
        ->set('role', Role::Manager->value)
        ->call('updateRole')
        ->assertHasNoErrors();

    expect($user->fresh()->hasRole(Role::Manager))->toBeTrue();
});

test('a user without the permission may not change the account of an employee', function () {
    $user = User::factory()->staff()->create();
    $employee = Employee::factory()->forUser($user)->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('pages::employees.show', ['employee' => $employee])
        ->call('toggleAccountStatus')
        ->assertForbidden();
});

test('the change of the account of an employee is written to the activity log', function () {
    $manager = User::factory()->manager()->create();
    $user = User::factory()->create();
    $employee = Employee::factory()->forUser($user)->create();

    Livewire::actingAs($manager)
        ->test('pages::employees.show', ['employee' => $employee])
        ->call('toggleAccountStatus');

    expect(Activity::query()
        ->where('subject_type', $user->getMorphClass())
        ->where('subject_id', $user->getKey())
        ->where('event', EmployeeService::EVENT_STATUS_CHANGED)
        ->exists())->toBeTrue();
});
