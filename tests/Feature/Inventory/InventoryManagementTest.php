<?php

use App\Enums\InventoryMovementType;
use App\Livewire\Inventory\InventoryTable;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\WithdrawalSlip;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('a guest is redirected to the login page for the inventory pages', function () {
    $inventory = Inventory::factory()->create();

    $this->get(route('inventory.show', $inventory))
        ->assertRedirect(route('login'));

    $this->get(route('inventory.edit', $inventory))
        ->assertRedirect(route('login'));
});

test('a staff member may not open the create page', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('inventory.create'))
        ->assertForbidden();
});

test('a manager may add an inventory item with its opening quantity', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $subcategory = Subcategory::factory()->create(['categories_id' => $category->id]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.create')
        ->set('item_name', 'COTTON FABRIC')
        ->set('description', 'White cotton')
        ->set('categories_id', (string) $category->id)
        ->set('subcategories_id', (string) $subcategory->id)
        ->set('price', '125.50')
        ->set('size', 'Large')
        ->set('color', 'White')
        ->set('type', 'Raw Material')
        ->set('current_quantity', '12')
        ->call('save')
        ->assertHasNoErrors();

    $inventory = Inventory::query()->where('item_name', 'COTTON FABRIC')->firstOrFail();

    expect($inventory->quantity())->toBe(12)
        ->and($inventory->categories_id)->toBe($category->id)
        ->and($inventory->subcategories_id)->toBe($subcategory->id);

    $movement = $inventory->movements()->sole();

    expect($movement->type)->toBe(InventoryMovementType::Opening)
        ->and($movement->quantity)->toBe(12)
        ->and($movement->quantity_before)->toBe(0)
        ->and($movement->quantity_after)->toBe(12)
        ->and($movement->created_by)->toBe($manager->id);
});

test('the inventory form rejects an incomplete item', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::inventory.create')
        ->call('save')
        ->assertHasErrors([
            'item_name',
            'categories_id',
            'subcategories_id',
        ]);
});

test('the opening quantity may not be below zero', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $subcategory = Subcategory::factory()->create(['categories_id' => $category->id]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.create')
        ->set('item_name', 'COTTON FABRIC')
        ->set('categories_id', (string) $category->id)
        ->set('subcategories_id', (string) $subcategory->id)
        ->set('current_quantity', '-1')
        ->call('save')
        ->assertHasErrors('current_quantity');
});

test('the item rejects a subcategory that belongs to another category', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $subcategory = Subcategory::factory()->create(['categories_id' => $otherCategory->id]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.create')
        ->set('item_name', 'COTTON FABRIC')
        ->set('categories_id', (string) $category->id)
        ->set('subcategories_id', (string) $subcategory->id)
        ->call('save')
        ->assertHasErrors('subcategories_id');

    expect(Inventory::query()->where('item_name', 'COTTON FABRIC')->exists())->toBeFalse();
});

test('an unclassified legacy subcategory may still be chosen', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $legacySubcategory = Subcategory::factory()->create(['categories_id' => null]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.create')
        ->set('item_name', 'COTTON FABRIC')
        ->set('categories_id', (string) $category->id)
        ->assertSee($legacySubcategory->name)
        ->set('subcategories_id', (string) $legacySubcategory->id)
        ->set('current_quantity', '4')
        ->call('save')
        ->assertHasNoErrors();

    expect(Inventory::query()->where('item_name', 'COTTON FABRIC')->exists())->toBeTrue();
});

test('a manager may change an item without changing its quantity', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $subcategory = Subcategory::factory()->create(['categories_id' => $otherCategory->id]);
    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'categories_id' => $category->id,
        'subcategories_id' => $subcategory->id,
        'current_quantity' => '5',
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.edit', ['inventory' => $inventory])
        ->set('item_name', 'COTTON FABRIC (WHITE)')
        ->set('categories_id', (string) $otherCategory->id)
        ->set('subcategories_id', (string) $subcategory->id)
        ->call('save')
        ->assertHasNoErrors();

    $inventory->refresh();

    expect($inventory->item_name)->toBe('COTTON FABRIC (WHITE)')
        ->and($inventory->categories_id)->toBe($otherCategory->id)
        ->and($inventory->current_quantity)->toBe('5')
        ->and($inventory->movements()->count())->toBe(0);
});

test('a manager may not move an item under a subcategory of another category', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $subcategory = Subcategory::factory()->create(['categories_id' => $category->id]);
    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'categories_id' => $category->id,
        'subcategories_id' => $subcategory->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.edit', ['inventory' => $inventory])
        ->set('categories_id', (string) $otherCategory->id)
        ->set('subcategories_id', (string) $subcategory->id)
        ->call('save')
        ->assertHasErrors('subcategories_id');

    expect($inventory->fresh()->categories_id)->toBe($category->id);
});

test('a staff member may not change an item', function () {
    $inventory = Inventory::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('pages::inventory.edit', ['inventory' => $inventory])
        ->assertForbidden();
});

test('the item page shows the item and its stock history', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $subcategory = Subcategory::factory()->create(['categories_id' => $category->id]);

    $inventory = app(InventoryService::class)->create([
        'item_name' => 'COTTON FABRIC',
        'categories_id' => $category->id,
        'subcategories_id' => $subcategory->id,
        'current_quantity' => 8,
    ], $manager->id);

    Livewire::actingAs($manager)
        ->test('pages::inventory.show', ['inventory' => $inventory])
        ->assertSee('COTTON FABRIC')
        ->assertSee('Opening balance')
        ->assertSee('8');
});

test('the item page warns about a legacy quantity that is not a whole number', function () {
    $staff = User::factory()->staff()->create();
    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'current_quantity' => '34.21M',
    ]);

    Livewire::actingAs($staff)
        ->test('pages::inventory.show', ['inventory' => $inventory])
        ->assertSee('not a whole number');
});

test('a manager may record a stock adjustment', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create([
        'item_name' => 'COTTON FABRIC',
        'current_quantity' => '5',
    ]);

    Livewire::actingAs($manager)
        ->test('pages::inventory.show', ['inventory' => $inventory])
        ->set('new_quantity', '3')
        ->set('adjustment_notes', 'Counted on the shelf')
        ->call('adjustQuantity')
        ->assertHasNoErrors();

    $inventory->refresh();

    expect($inventory->quantity())->toBe(3);

    $movement = $inventory->movements()->sole();

    expect($movement->type)->toBe(InventoryMovementType::Adjustment)
        ->and($movement->quantity)->toBe(-2)
        ->and($movement->quantity_before)->toBe(5)
        ->and($movement->quantity_after)->toBe(3)
        ->and($movement->notes)->toBe('Counted on the shelf')
        ->and($movement->created_by)->toBe($manager->id);
});

test('a stock adjustment requires a reason', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '5']);

    Livewire::actingAs($manager)
        ->test('pages::inventory.show', ['inventory' => $inventory])
        ->set('new_quantity', '3')
        ->set('adjustment_notes', '')
        ->call('adjustQuantity')
        ->assertHasErrors('adjustment_notes');

    expect($inventory->fresh()->quantity())->toBe(5)
        ->and($inventory->movements()->count())->toBe(0);
});

test('a stock adjustment has to change the quantity', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '5']);

    Livewire::actingAs($manager)
        ->test('pages::inventory.show', ['inventory' => $inventory])
        ->set('new_quantity', '5')
        ->set('adjustment_notes', 'No change found')
        ->call('adjustQuantity')
        ->assertHasErrors('new_quantity');

    expect($inventory->movements()->count())->toBe(0);
});

test('a stock adjustment may not go below zero', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create(['current_quantity' => '5']);

    Livewire::actingAs($manager)
        ->test('pages::inventory.show', ['inventory' => $inventory])
        ->set('new_quantity', '-1')
        ->set('adjustment_notes', 'Counted on the shelf')
        ->call('adjustQuantity')
        ->assertHasErrors('new_quantity');

    expect($inventory->fresh()->quantity())->toBe(5);
});

test('a manager may remove an item and keep its stock history', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create(['item_name' => 'COTTON FABRIC']);
    InventoryMovement::factory()->create(['inventory_id' => $inventory->id]);

    Livewire::actingAs($manager)
        ->test(InventoryTable::class)
        ->call('confirmDelete', $inventory->id)
        ->call('deleteInventory')
        ->assertHasNoErrors();

    expect(Inventory::withTrashed()->find($inventory->id)->trashed())->toBeTrue()
        ->and(InventoryMovement::query()->where('inventory_id', $inventory->id)->exists())->toBeTrue();
});

test('an item that a withdrawal slip names cannot be removed', function () {
    $manager = User::factory()->manager()->create();
    $inventory = Inventory::factory()->create(['item_name' => 'COTTON FABRIC']);
    $slip = WithdrawalSlip::factory()->create();

    $inventory->withdrawalSlips()->attach($slip->id, [
        'qty' => 1,
        'particulars' => 'COTTON FABRIC',
    ]);

    Livewire::actingAs($manager)
        ->test(InventoryTable::class)
        ->call('confirmDelete', $inventory->id)
        ->call('deleteInventory')
        ->assertHasErrors('inventory');

    expect($inventory->fresh())->not->toBeNull()
        ->and($inventory->fresh()->trashed())->toBeFalse();
});

test('a staff member may not remove an item', function () {
    $inventory = Inventory::factory()->create();

    expect(Gate::forUser(User::factory()->staff()->create())->allows('delete', $inventory))->toBeFalse();
});

test('a user without the permission may not view an item', function () {
    $inventory = Inventory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('inventory.show', $inventory))
        ->assertForbidden();
});
