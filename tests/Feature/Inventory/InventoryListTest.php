<?php

use App\Livewire\Inventory\InventoryTable;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use App\Models\User;
use Livewire\Livewire;

test('a guest is redirected to the login page', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'inventory.index',
    'inventory.create',
]);

test('a user without the permission may not open the inventory list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('inventory.index'))
        ->assertForbidden();
});

test('a user without the permission may not search the inventory', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(InventoryTable::class)
        ->assertForbidden();
});

test('a staff member may open the inventory list', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertSee(__('Inventory'));
});

test('the list shows the inventory items', function () {
    $staff = User::factory()->staff()->create();

    Inventory::factory()->create(['item_name' => 'COTTON FABRIC']);
    Inventory::factory()->create(['item_name' => 'POLYESTER THREAD']);

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->assertSee('COTTON FABRIC')
        ->assertSee('POLYESTER THREAD');
});

test('the list can be searched by the name of the item', function () {
    $staff = User::factory()->staff()->create();

    Inventory::factory()->create(['item_name' => 'COTTON FABRIC']);
    Inventory::factory()->create(['item_name' => 'POLYESTER THREAD']);

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('search', 'COTTON')
        ->assertSee('COTTON FABRIC')
        ->assertDontSee('POLYESTER THREAD');
});

test('the list can be searched by the category of the item', function () {
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create(['name' => 'RAW MATERIALS']);

    Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'categories_id' => $category->id,
    ]);
    Inventory::factory()->create(['item_name' => 'POLYESTER THREAD']);

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('search', 'RAW MATERIALS')
        ->assertSee('COTTON FABRIC')
        ->assertDontSee('POLYESTER THREAD');
});

test('the list can be filtered by the category of the item', function () {
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create();

    Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'categories_id' => $category->id,
    ]);
    Inventory::factory()->create(['item_name' => 'POLYESTER THREAD']);

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('filterComponents.category', (string) $category->id)
        ->assertSee('COTTON FABRIC')
        ->assertDontSee('POLYESTER THREAD');
});

test('the list can be filtered by the subcategory of the item', function () {
    $staff = User::factory()->staff()->create();
    $subcategory = Subcategory::factory()->create();

    Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'subcategories_id' => $subcategory->id,
    ]);
    Inventory::factory()->create(['item_name' => 'POLYESTER THREAD']);

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('filterComponents.subcategory', (string) $subcategory->id)
        ->assertSee('COTTON FABRIC')
        ->assertDontSee('POLYESTER THREAD');
});

test('the list can be filtered by the stock on hand', function () {
    $staff = User::factory()->staff()->create();

    Inventory::factory()->create(['item_name' => 'COTTON FABRIC', 'current_quantity' => '12']);
    Inventory::factory()->outOfStock()->create(['item_name' => 'POLYESTER THREAD']);

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('filterComponents.stock', 'in')
        ->assertSee('COTTON FABRIC')
        ->assertDontSee('POLYESTER THREAD');

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('filterComponents.stock', 'out')
        ->assertSee('POLYESTER THREAD')
        ->assertDontSee('COTTON FABRIC');
});

test('the list can be filtered by the type of the item', function () {
    $staff = User::factory()->staff()->create();

    Inventory::factory()->create(['item_name' => 'COTTON FABRIC', 'type' => 'Raw Material']);
    Inventory::factory()->create(['item_name' => 'POLYESTER THREAD', 'type' => 'Finished Goods']);

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('filterComponents.type', 'Raw Material')
        ->assertSee('COTTON FABRIC')
        ->assertDontSee('POLYESTER THREAD');
});

test('archived inventory items are not listed', function () {
    $staff = User::factory()->staff()->create();

    Inventory::factory()->create(['item_name' => 'ARCHIVED FABRIC'])->delete();
    Inventory::factory()->create(['item_name' => 'COTTON FABRIC']);

    Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->assertSee('COTTON FABRIC')
        ->assertDontSee('ARCHIVED FABRIC');
});

test('the list is sorted by the name of the item by default', function () {
    $staff = User::factory()->staff()->create();

    Inventory::factory()->create(['item_name' => 'ZULU FABRIC']);
    Inventory::factory()->create(['item_name' => 'ALPHA FABRIC']);

    $rows = Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->instance()
        ->getRows();

    expect($rows->first()->item_name)->toBe('ALPHA FABRIC');
});

test('the list can be sorted by the name of the item', function () {
    $staff = User::factory()->staff()->create();

    Inventory::factory()->create(['item_name' => 'ZULU FABRIC']);
    Inventory::factory()->create(['item_name' => 'ALPHA FABRIC']);

    $rows = Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('sorts', ['item_name' => 'desc'])
        ->instance()
        ->getRows();

    expect($rows->first()->item_name)->toBe('ZULU FABRIC');
});

test('the list is paginated', function () {
    $staff = User::factory()->staff()->create();

    Inventory::factory()->count(12)->create();

    $component = Livewire::actingAs($staff)
        ->test(InventoryTable::class)
        ->set('perPage', 10);

    expect($component->instance()->getRows())->toHaveCount(10);

    $component->call('gotoPage', 2, $component->instance()->getComputedPageName());

    expect($component->instance()->getRows())->toHaveCount(2);
});
