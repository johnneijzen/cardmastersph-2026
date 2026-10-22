<?php

use App\Livewire\Clients\ClientsTable;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientAgent;
use App\Models\Order;
use App\Models\Province;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

test('a guest is redirected to the login page', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'clients.create',
    'clients.index',
]);

test('a user without the permission may not open the create page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.create'))
        ->assertForbidden();
});

test('a staff member may open the create page', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('clients.create'))
        ->assertOk()
        ->assertSee(__('Add client'));
});

test('a staff member may add a client', function () {
    $staff = User::factory()->staff()->create();
    $city = City::factory()->create();
    $agent = ClientAgent::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::clients.create')
        ->set('name', 'Silver Studio')
        ->set('client_number', '09171234567')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->set('agent_id', (string) $agent->id)
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::query()->where('name', 'Silver Studio')->firstOrFail();

    expect($client->client_number)->toBe('09171234567')
        ->and($client->agent_id)->toBe($agent->id)
        ->and($client->address->line_address)->toBe('12 San Jose Street')
        ->and($client->address->city_id)->toBe($city->id);
});

test('a client may be added without an agent', function () {
    $staff = User::factory()->staff()->create();
    $city = City::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::clients.create')
        ->set('name', 'Silver Studio')
        ->set('client_number', '09171234567')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->set('agent_id', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Client::query()->where('name', 'Silver Studio')->firstOrFail()->agent_id)->toBeNull();
});

test('the client form rejects an incomplete client', function () {
    $staff = User::factory()->staff()->create();

    Livewire::actingAs($staff)
        ->test('pages::clients.create')
        ->call('save')
        ->assertHasErrors(['name', 'client_number', 'line_address', 'province_id', 'city_id']);
});

test('the name of a client has to be unique', function () {
    $staff = User::factory()->staff()->create();
    $city = City::factory()->create();
    Client::factory()->create(['name' => 'Silver Studio']);

    Livewire::actingAs($staff)
        ->test('pages::clients.create')
        ->set('name', 'Silver Studio')
        ->set('client_number', '09171234567')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->call('save')
        ->assertHasErrors('name');
});

test('the name of an archived client stays taken', function () {
    $staff = User::factory()->staff()->create();
    $city = City::factory()->create();
    Client::factory()->archived()->create(['name' => 'Silver Studio']);

    Livewire::actingAs($staff)
        ->test('pages::clients.create')
        ->set('name', 'Silver Studio')
        ->set('client_number', '09171234567')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->call('save')
        ->assertHasErrors('name');
});

test('the city of a client has to belong to the chosen province', function () {
    $staff = User::factory()->staff()->create();
    $province = Province::factory()->create();
    $otherCity = City::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::clients.create')
        ->set('name', 'Silver Studio')
        ->set('client_number', '09171234567')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $province->id)
        ->set('city_id', (string) $otherCity->id)
        ->call('save')
        ->assertHasErrors('city_id');
});

test('a user without the permission may not open the details of a client', function () {
    $client = Client::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('clients.show', $client))
        ->assertForbidden();
});

test('a staff member may see the details of a client', function () {
    $client = Client::factory()->create(['name' => 'Silver Studio']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertSee('Silver Studio');
});

test('a staff member may change a client', function () {
    $staff = User::factory()->staff()->create();
    $client = Client::factory()->create(['name' => 'Silver Studio']);
    $city = City::factory()->create();

    Livewire::actingAs($staff)
        ->test('pages::clients.edit', ['client' => $client])
        ->set('name', 'Silver Atelier')
        ->set('client_number', '09179876543')
        ->set('line_address', '34 Colon Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($client->fresh()->name)->toBe('Silver Atelier')
        ->and($client->fresh()->address->line_address)->toBe('34 Colon Street');
});

test('a user without the permission may not change a client', function () {
    $client = Client::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::clients.edit', ['client' => $client])
        ->assertForbidden();
});

test('a manager may archive a client', function () {
    $manager = User::factory()->manager()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($manager)
        ->test(ClientsTable::class)
        ->call('confirmArchive', $client->id)
        ->call('archiveClient')
        ->assertHasNoErrors();

    expect(Client::query()->whereKey($client->id)->exists())->toBeFalse()
        ->and(Client::withTrashed()->whereKey($client->id)->exists())->toBeTrue();
});

test('an archived client keeps the job orders that were placed for it', function () {
    $manager = User::factory()->manager()->create();
    $client = Client::factory()->create();
    $order = Order::factory()->create(['client_id' => $client->id]);

    Livewire::actingAs($manager)
        ->test(ClientsTable::class)
        ->call('confirmArchive', $client->id)
        ->call('archiveClient');

    expect($order->fresh()->client_id)->toBe($client->id)
        ->and($order->fresh()->client->trashed())->toBeTrue();
});

test('the archive of a client is written to the activity log', function () {
    $manager = User::factory()->manager()->create();
    $client = Client::factory()->create();

    Livewire::actingAs($manager)
        ->test(ClientsTable::class)
        ->call('confirmArchive', $client->id)
        ->call('archiveClient');

    $activity = Activity::query()
        ->where('subject_type', $client->getMorphClass())
        ->where('subject_id', $client->getKey())
        ->where('event', 'deleted')
        ->firstOrFail();

    expect($activity->description)->toBe('Client Archived')
        ->and($activity->log_name)->toBe('client');
});

test('a staff member may not archive a client', function () {
    $client = Client::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test(ClientsTable::class)
        ->call('confirmArchive', $client->id)
        ->assertForbidden();
});

test('a manager may restore an archived client', function () {
    $manager = User::factory()->manager()->create();
    $client = Client::factory()->archived()->create();

    Livewire::actingAs($manager)
        ->test(ClientsTable::class)
        ->call('restoreClient', $client->id)
        ->assertHasNoErrors();

    expect(Client::query()->whereKey($client->id)->exists())->toBeTrue();
});

test('a staff member may not restore an archived client', function () {
    $client = Client::factory()->archived()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test(ClientsTable::class)
        ->call('restoreClient', $client->id)
        ->assertForbidden();
});

test('the restore of a client is written to the activity log', function () {
    $manager = User::factory()->manager()->create();
    $client = Client::factory()->archived()->create();

    Livewire::actingAs($manager)
        ->test(ClientsTable::class)
        ->call('restoreClient', $client->id);

    $activity = Activity::query()
        ->where('subject_type', $client->getMorphClass())
        ->where('subject_id', $client->getKey())
        ->where('event', 'restored')
        ->firstOrFail();

    expect($activity->description)->toBe('Client Restored');
});

test('the details of an archived client stay reachable', function () {
    $client = Client::factory()->archived()->create(['name' => 'Archived Studio']);

    $this->actingAs(User::factory()->staff()->create())
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertSee('Archived Studio');
});

test('a client without an address receives one when it is changed', function () {
    $staff = User::factory()->staff()->create();
    $client = Client::factory()->create();
    $city = City::factory()->create();

    $client->forceFill(['address_id' => null])->save();

    Livewire::actingAs($staff)
        ->test('pages::clients.edit', ['client' => $client])
        ->set('name', 'Silver Studio')
        ->set('client_number', '09171234567')
        ->set('line_address', '12 San Jose Street')
        ->set('province_id', (string) $city->province_id)
        ->set('city_id', (string) $city->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($client->fresh()->address)->toBeInstanceOf(Address::class)
        ->and($client->fresh()->address->line_address)->toBe('12 San Jose Street');
});
