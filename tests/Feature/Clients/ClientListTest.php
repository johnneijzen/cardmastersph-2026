<?php

use App\Livewire\Clients\ClientsTable;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientAgent;
use App\Models\Province;
use App\Models\User;
use Livewire\Livewire;

test('a guest is redirected to the login page', function () {
    $this->get(route('clients.index'))
        ->assertRedirect(route('login'));
});

test('a user without the permission may not open the client list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertForbidden();
});

test('a staff member may open the client list', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertSee(__('Clients'));
});

test('the list shows the clients', function () {
    $staff = User::factory()->staff()->create();

    Client::factory()->create(['name' => 'Silver Studio']);
    Client::factory()->create(['name' => 'Golden Workshop']);

    Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->assertSee('Silver Studio')
        ->assertSee('Golden Workshop');
});

test('the list can be searched', function () {
    $staff = User::factory()->staff()->create();

    Client::factory()->create(['name' => 'Silver Studio']);
    Client::factory()->create(['name' => 'Golden Workshop']);

    Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->set('search', 'Silver')
        ->assertSee('Silver Studio')
        ->assertDontSee('Golden Workshop');
});

test('the list can be searched by the phone number', function () {
    $staff = User::factory()->staff()->create();

    Client::factory()->create(['name' => 'Silver Studio', 'client_number' => '09171234567']);
    Client::factory()->create(['name' => 'Golden Workshop', 'client_number' => '09179876543']);

    Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->set('search', '09179876543')
        ->assertSee('Golden Workshop')
        ->assertDontSee('Silver Studio');
});

test('the list can be searched by the city of the client', function () {
    $staff = User::factory()->staff()->create();
    $city = City::factory()->create(['name' => 'Quezon City']);

    Client::factory()->create([
        'name' => 'Silver Studio',
        'address_id' => Address::factory()->for($city)->create()->id,
    ]);
    Client::factory()->create(['name' => 'Golden Workshop']);

    Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->set('search', 'Quezon City')
        ->assertSee('Silver Studio')
        ->assertDontSee('Golden Workshop');
});

test('the list can be filtered by the province of the client', function () {
    $staff = User::factory()->staff()->create();
    $province = Province::factory()->create(['name' => 'Bulacan']);
    $city = City::factory()->for($province)->create();

    Client::factory()->create([
        'name' => 'Silver Studio',
        'address_id' => Address::factory()->for($city)->create()->id,
    ]);
    Client::factory()->create(['name' => 'Golden Workshop']);

    Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->set('filterComponents.province', (string) $province->id)
        ->assertSee('Silver Studio')
        ->assertDontSee('Golden Workshop');
});

test('the list can be filtered by the agent of the client', function () {
    $staff = User::factory()->staff()->create();

    $agent = ClientAgent::factory()->create(['name' => 'Facebook']);
    $otherAgent = ClientAgent::factory()->create(['name' => 'Walk-in']);

    Client::factory()->create(['name' => 'Silver Studio', 'agent_id' => $agent->id]);
    Client::factory()->create(['name' => 'Golden Workshop', 'agent_id' => $otherAgent->id]);

    Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->set('filterComponents.agent', (string) $agent->id)
        ->assertSee('Silver Studio')
        ->assertDontSee('Golden Workshop');
});

test('archived clients are not listed', function () {
    $staff = User::factory()->staff()->create();

    Client::factory()->archived()->create(['name' => 'Archived Studio']);
    Client::factory()->create(['name' => 'Silver Studio']);

    Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->assertSee('Silver Studio')
        ->assertDontSee('Archived Studio');
});

test('the list can be filtered to show the archived clients', function () {
    $staff = User::factory()->staff()->create();

    Client::factory()->archived()->create(['name' => 'Archived Studio']);
    Client::factory()->create(['name' => 'Silver Studio']);

    Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->set('filterComponents.status', 'archived')
        ->assertSee('Archived Studio')
        ->assertDontSee('Silver Studio');
});

test('the list is sorted by the name of the client by default', function () {
    $staff = User::factory()->staff()->create();

    Client::factory()->create(['name' => 'Zulu Studio']);
    Client::factory()->create(['name' => 'Alpha Studio']);

    $rows = Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('Alpha Studio');
});

test('the list can be sorted by the name of the client', function () {
    $staff = User::factory()->staff()->create();

    Client::factory()->create(['name' => 'Zulu Studio']);
    Client::factory()->create(['name' => 'Alpha Studio']);

    $rows = Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->set('sorts', ['name' => 'desc'])
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('Zulu Studio');
});

test('the list is paginated', function () {
    $staff = User::factory()->staff()->create();

    Client::factory()->count(12)->create();

    $component = Livewire::actingAs($staff)
        ->test(ClientsTable::class)
        ->set('perPage', 10);

    expect($component->instance()->getRows())->toHaveCount(10);

    $component->call('gotoPage', 2, $component->instance()->getComputedPageName());

    expect($component->instance()->getRows())->toHaveCount(2);
});

test('a user without the permission may not search the clients', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ClientsTable::class)
        ->assertForbidden();
});
