<?php

use App\Livewire\ClientContactPersons\ClientContactPersonsTable;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientContactPerson;
use App\Models\Order;
use App\Models\Province;
use App\Models\User;
use Livewire\Livewire;

test('a guest is redirected to the login page', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'client-contact-persons.create',
    'client-contact-persons.index',
]);

test('a user without the permission may not open the contract person list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('client-contact-persons.index'))
        ->assertForbidden();
});

test('a staff member may open the contract person list', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('client-contact-persons.index'))
        ->assertOk()
        ->assertSee(__('Contract persons'));
});

test('the list shows the contract persons with their client', function () {
    $staff = User::factory()->staff()->create();
    $client = Client::factory()->create(['name' => 'Silver Studio']);

    ClientContactPerson::factory()->create([
        'client_id' => $client->id,
        'contact_person' => 'Maria Dela Cruz',
    ]);

    Livewire::actingAs($staff)
        ->test(ClientContactPersonsTable::class)
        ->assertSee('Maria Dela Cruz')
        ->assertSee('Silver Studio');
});

test('the list can be searched by the name of the client', function () {
    $staff = User::factory()->staff()->create();

    ClientContactPerson::factory()->create([
        'client_id' => Client::factory()->create(['name' => 'Silver Studio'])->id,
        'contact_person' => 'Maria Dela Cruz',
    ]);
    ClientContactPerson::factory()->create([
        'client_id' => Client::factory()->create(['name' => 'Golden Workshop'])->id,
        'contact_person' => 'Jose Rizal',
    ]);

    Livewire::actingAs($staff)
        ->test(ClientContactPersonsTable::class)
        ->set('search', 'Silver Studio')
        ->assertSee('Maria Dela Cruz')
        ->assertDontSee('Jose Rizal');
});

test('the list can be searched by the e-mail address', function () {
    $staff = User::factory()->staff()->create();

    ClientContactPerson::factory()->create([
        'contact_person' => 'Maria Dela Cruz',
        'contact_email' => 'maria@example.com',
    ]);
    ClientContactPerson::factory()->create([
        'contact_person' => 'Jose Rizal',
        'contact_email' => 'jose@example.com',
    ]);

    Livewire::actingAs($staff)
        ->test(ClientContactPersonsTable::class)
        ->set('search', 'maria@example.com')
        ->assertSee('Maria Dela Cruz')
        ->assertDontSee('Jose Rizal');
});

test('the list can be filtered by the province of the client', function () {
    $staff = User::factory()->staff()->create();
    $province = Province::factory()->create(['name' => 'Bulacan']);
    $city = City::factory()->for($province)->create();

    ClientContactPerson::factory()->create([
        'client_id' => Client::factory()->create([
            'address_id' => Address::factory()->for($city)->create()->id,
        ])->id,
        'contact_person' => 'Maria Dela Cruz',
    ]);
    ClientContactPerson::factory()->create([
        'contact_person' => 'Jose Rizal',
    ]);

    Livewire::actingAs($staff)
        ->test(ClientContactPersonsTable::class)
        ->set('filterComponents.province', (string) $province->id)
        ->assertSee('Maria Dela Cruz')
        ->assertDontSee('Jose Rizal');
});

test('a staff member may add a contract person', function () {
    $staff = User::factory()->staff()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::client-contact-persons.create')
        ->set('contact_person', 'Maria Dela Cruz')
        ->set('contact_number', '09171234567')
        ->set('contact_email', 'maria@example.com')
        ->set('client_id', (string) $client->id)
        ->call('save')
        ->assertHasNoErrors();

    $contactPerson = ClientContactPerson::query()->where('contact_person', 'Maria Dela Cruz')->firstOrFail();

    expect($contactPerson->contact_email)->toBe('maria@example.com')
        ->and($contactPerson->client_id)->toBe($client->id);
});

test('the contract person form rejects an incomplete contract person', function () {
    $staff = User::factory()->staff()->create();

    Livewire::actingAs($staff)
        ->test('pages::client-contact-persons.create')
        ->call('save')
        ->assertHasErrors(['contact_person', 'contact_number', 'contact_email', 'client_id']);
});

test('the e-mail address of a contract person has to be an e-mail address', function () {
    $staff = User::factory()->staff()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::client-contact-persons.create')
        ->set('contact_person', 'Maria Dela Cruz')
        ->set('contact_number', '09171234567')
        ->set('contact_email', 'not-an-email')
        ->set('client_id', (string) $client->id)
        ->call('save')
        ->assertHasErrors('contact_email');
});

test('a contract person may not be attached to an archived client', function () {
    $staff = User::factory()->staff()->create();
    $client = Client::factory()->archived()->create();

    Livewire::actingAs($staff)
        ->test('pages::client-contact-persons.create')
        ->set('contact_person', 'Maria Dela Cruz')
        ->set('contact_number', '09171234567')
        ->set('contact_email', 'maria@example.com')
        ->set('client_id', (string) $client->id)
        ->call('save')
        ->assertHasErrors('client_id');
});

test('a user without the permission may not open the create page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('client-contact-persons.create'))
        ->assertForbidden();
});

test('a staff member may change a contract person', function () {
    $staff = User::factory()->staff()->create();
    $contactPerson = ClientContactPerson::factory()->create(['contact_person' => 'Maria Dela Cruz']);

    Livewire::actingAs($staff)
        ->test('pages::client-contact-persons.edit', ['clientContactPerson' => $contactPerson])
        ->set('contact_person', 'Maria Santos')
        ->call('save')
        ->assertHasNoErrors();

    expect($contactPerson->fresh()->contact_person)->toBe('Maria Santos');
});

test('a user without the permission may not change a contract person', function () {
    $contactPerson = ClientContactPerson::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::client-contact-persons.edit', ['clientContactPerson' => $contactPerson])
        ->assertForbidden();
});

test('a manager may remove a contract person', function () {
    $manager = User::factory()->manager()->create();
    $contactPerson = ClientContactPerson::factory()->create();

    Livewire::actingAs($manager)
        ->test(ClientContactPersonsTable::class)
        ->call('confirmDelete', $contactPerson->id)
        ->call('deleteContactPerson')
        ->assertHasNoErrors();

    expect(ClientContactPerson::query()->whereKey($contactPerson->id)->exists())->toBeFalse();
});

test('a contract person that is named on a job order cannot be removed', function () {
    $manager = User::factory()->manager()->create();
    $contactPerson = ClientContactPerson::factory()->create();
    Order::factory()->create(['contact_person_id' => $contactPerson->id]);

    Livewire::actingAs($manager)
        ->test(ClientContactPersonsTable::class)
        ->call('confirmDelete', $contactPerson->id)
        ->call('deleteContactPerson')
        ->assertHasErrors('clientContactPerson');

    expect(ClientContactPerson::query()->whereKey($contactPerson->id)->exists())->toBeTrue();
});

test('a staff member may not remove a contract person', function () {
    $contactPerson = ClientContactPerson::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test(ClientContactPersonsTable::class)
        ->call('confirmDelete', $contactPerson->id)
        ->assertForbidden();
});
