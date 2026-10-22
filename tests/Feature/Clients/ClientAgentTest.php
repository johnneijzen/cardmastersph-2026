<?php

use App\Livewire\ClientAgents\ClientAgentsTable;
use App\Models\Client;
use App\Models\ClientAgent;
use App\Models\User;
use Livewire\Livewire;

test('a guest is redirected to the login page', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'client-agents.create',
    'client-agents.index',
]);

test('a user without the permission may not open the agent list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('client-agents.index'))
        ->assertForbidden();
});

test('a staff member may open the agent list', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('client-agents.index'))
        ->assertOk()
        ->assertSee(__('Client agents'));
});

test('the list shows the agents with the number of clients', function () {
    $staff = User::factory()->staff()->create();
    $agent = ClientAgent::factory()->create(['name' => 'Trade Show']);

    Client::factory()->count(2)->create(['agent_id' => $agent->id]);

    $rows = Livewire::actingAs($staff)
        ->test(ClientAgentsTable::class)
        ->instance()
        ->getRows();

    expect($rows->firstWhere('name', 'Trade Show')?->clients_count)->toBe(2);
});

test('the list can be searched', function () {
    $staff = User::factory()->staff()->create();

    ClientAgent::factory()->create(['name' => 'Trade Show']);
    ClientAgent::factory()->create(['name' => 'Radio Advertisement']);

    Livewire::actingAs($staff)
        ->test(ClientAgentsTable::class)
        ->set('search', 'Trade Show')
        ->assertSee('Trade Show')
        ->assertDontSee('Radio Advertisement');
});

test('a staff member may not open the create page', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('client-agents.create'))
        ->assertForbidden();
});

test('a manager may add an agent', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::client-agents.create')
        ->set('name', 'Trade Show')
        ->call('save')
        ->assertHasNoErrors();

    expect(ClientAgent::query()->where('name', 'Trade Show')->exists())->toBeTrue();
});

test('the agent form rejects an incomplete agent', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::client-agents.create')
        ->call('save')
        ->assertHasErrors('name');
});

test('the name of an agent has to be unique', function () {
    $manager = User::factory()->manager()->create();
    ClientAgent::factory()->create(['name' => 'Trade Show']);

    Livewire::actingAs($manager)
        ->test('pages::client-agents.create')
        ->set('name', 'Trade Show')
        ->call('save')
        ->assertHasErrors('name');
});

test('a manager may change an agent', function () {
    $manager = User::factory()->manager()->create();
    $agent = ClientAgent::factory()->create(['name' => 'Trade Show']);

    Livewire::actingAs($manager)
        ->test('pages::client-agents.edit', ['clientAgent' => $agent])
        ->set('name', 'Social Media')
        ->call('save')
        ->assertHasNoErrors();

    expect($agent->fresh()->name)->toBe('Social Media');
});

test('a user without the permission may not change an agent', function () {
    $agent = ClientAgent::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('pages::client-agents.edit', ['clientAgent' => $agent])
        ->assertForbidden();
});

test('a manager may remove an agent that no client uses', function () {
    $manager = User::factory()->manager()->create();
    $agent = ClientAgent::factory()->create();

    Livewire::actingAs($manager)
        ->test(ClientAgentsTable::class)
        ->call('confirmDelete', $agent->id)
        ->call('deleteAgent')
        ->assertHasNoErrors();

    expect(ClientAgent::query()->whereKey($agent->id)->exists())->toBeFalse();
});

test('an agent that a client still uses cannot be removed', function () {
    $manager = User::factory()->manager()->create();
    $agent = ClientAgent::factory()->create();
    Client::factory()->create(['agent_id' => $agent->id]);

    Livewire::actingAs($manager)
        ->test(ClientAgentsTable::class)
        ->call('confirmDelete', $agent->id)
        ->call('deleteAgent')
        ->assertHasErrors('clientAgent');

    expect(ClientAgent::query()->whereKey($agent->id)->exists())->toBeTrue();
});

test('a staff member may not remove an agent', function () {
    $agent = ClientAgent::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test(ClientAgentsTable::class)
        ->call('confirmDelete', $agent->id)
        ->assertForbidden();
});
