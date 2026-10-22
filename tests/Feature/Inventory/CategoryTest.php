<?php

use App\Livewire\Categories\CategoriesTable;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('a guest is redirected to the login page', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'categories.index',
    'categories.create',
]);

test('a user without the permission may not open the category list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('categories.index'))
        ->assertForbidden();
});

test('a user without the permission may not search the categories', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(CategoriesTable::class)
        ->assertForbidden();
});

test('a staff member may open the category list', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('categories.index'))
        ->assertOk()
        ->assertSee(__('Categories'));
});

test('the list shows the categories with their subcategories and items', function () {
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create(['name' => 'RAW MATERIALS']);

    Subcategory::factory()->count(2)->create(['categories_id' => $category->id]);
    Inventory::factory()->create(['categories_id' => $category->id]);

    $row = Livewire::actingAs($staff)
        ->test(CategoriesTable::class)
        ->set('search', 'RAW MATERIALS')
        ->instance()
        ->getRows()
        ->firstWhere('name', 'RAW MATERIALS');

    expect($row->subcategories_count)->toBe(2)
        ->and($row->inventory_count)->toBe(1);
});

test('the list can be searched', function () {
    $staff = User::factory()->staff()->create();

    Category::factory()->create(['name' => 'RAW MATERIALS']);
    Category::factory()->create(['name' => 'FINISHED GOODS']);

    Livewire::actingAs($staff)
        ->test(CategoriesTable::class)
        ->set('search', 'RAW')
        ->assertSee('RAW MATERIALS')
        ->assertDontSee('FINISHED GOODS');
});

test('archived categories are not listed', function () {
    $staff = User::factory()->staff()->create();

    Category::factory()->create(['name' => 'ARCHIVED GROUP'])->delete();
    Category::factory()->create(['name' => 'RAW MATERIALS']);

    Livewire::actingAs($staff)
        ->test(CategoriesTable::class)
        ->set('search', 'RAW MATERIALS')
        ->assertSee('RAW MATERIALS');

    Livewire::actingAs($staff)
        ->test(CategoriesTable::class)
        ->set('search', 'ARCHIVED GROUP')
        ->assertDontSee('ARCHIVED GROUP');
});

test('the list is sorted by the name of the category by default', function () {
    $staff = User::factory()->staff()->create();

    Category::factory()->create(['name' => 'ZULU GROUP']);
    Category::factory()->create(['name' => 'ALPHA GROUP']);

    $rows = Livewire::actingAs($staff)
        ->test(CategoriesTable::class)
        ->set('search', 'GROUP')
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('ALPHA GROUP');
});

test('the list can be sorted by the name of the category', function () {
    $staff = User::factory()->staff()->create();

    Category::factory()->create(['name' => 'ZULU GROUP']);
    Category::factory()->create(['name' => 'ALPHA GROUP']);

    $rows = Livewire::actingAs($staff)
        ->test(CategoriesTable::class)
        ->set('search', 'GROUP')
        ->set('sorts', ['name' => 'desc'])
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('ZULU GROUP');
});

test('a staff member may not open the create page', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('categories.create'))
        ->assertForbidden();
});

test('a manager may add a category', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::categories.create')
        ->set('name', 'RAW MATERIALS')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::query()->where('name', 'RAW MATERIALS')->exists())->toBeTrue();
});

test('the category form rejects an incomplete category', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::categories.create')
        ->call('save')
        ->assertHasErrors('name');
});

test('the name of an active category has to be unique', function () {
    $manager = User::factory()->manager()->create();
    Category::factory()->create(['name' => 'RAW MATERIALS']);

    Livewire::actingAs($manager)
        ->test('pages::categories.create')
        ->set('name', 'RAW MATERIALS')
        ->call('save')
        ->assertHasErrors('name');
});

test('the name of an archived category may be used again', function () {
    $manager = User::factory()->manager()->create();
    Category::factory()->create(['name' => 'RAW MATERIALS'])->delete();

    Livewire::actingAs($manager)
        ->test('pages::categories.create')
        ->set('name', 'RAW MATERIALS')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::query()->where('name', 'RAW MATERIALS')->exists())->toBeTrue();
});

test('a manager may change a category', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create(['name' => 'RAW MATERIALS']);

    Livewire::actingAs($manager)
        ->test('pages::categories.edit', ['category' => $category])
        ->set('name', 'MATERIALS')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh()->name)->toBe('MATERIALS');
});

test('a category may keep its own name when it is changed', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create(['name' => 'RAW MATERIALS']);

    Livewire::actingAs($manager)
        ->test('pages::categories.edit', ['category' => $category])
        ->set('name', 'RAW MATERIALS')
        ->call('save')
        ->assertHasNoErrors();
});

test('the name of a category may not become the name of another category', function () {
    $manager = User::factory()->manager()->create();
    Category::factory()->create(['name' => 'RAW MATERIALS']);
    $category = Category::factory()->create(['name' => 'FINISHED GOODS']);

    Livewire::actingAs($manager)
        ->test('pages::categories.edit', ['category' => $category])
        ->set('name', 'RAW MATERIALS')
        ->call('save')
        ->assertHasErrors('name');

    expect($category->fresh()->name)->toBe('FINISHED GOODS');
});

test('a staff member may not change a category', function () {
    $category = Category::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('pages::categories.edit', ['category' => $category])
        ->assertForbidden();
});

test('a manager may remove a category that nothing uses', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Livewire::actingAs($manager)
        ->test(CategoriesTable::class)
        ->call('confirmDelete', $category->id)
        ->call('deleteCategory')
        ->assertHasNoErrors();

    expect(Category::withTrashed()->find($category->id)->trashed())->toBeTrue();
});

test('a category that still holds subcategories cannot be removed', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Subcategory::factory()->create(['categories_id' => $category->id]);

    Livewire::actingAs($manager)
        ->test(CategoriesTable::class)
        ->call('confirmDelete', $category->id)
        ->call('deleteCategory')
        ->assertHasErrors('category');

    expect($category->fresh())->not->toBeNull();
});

test('an archived subcategory still keeps its category from being removed', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Subcategory::factory()->create(['categories_id' => $category->id])->delete();

    Livewire::actingAs($manager)
        ->test(CategoriesTable::class)
        ->call('confirmDelete', $category->id)
        ->call('deleteCategory')
        ->assertHasErrors('category');

    expect($category->fresh())->not->toBeNull();
});

test('a category that still holds inventory items cannot be removed', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Inventory::factory()->create(['categories_id' => $category->id]);

    Livewire::actingAs($manager)
        ->test(CategoriesTable::class)
        ->call('confirmDelete', $category->id)
        ->call('deleteCategory')
        ->assertHasErrors('category');

    expect($category->fresh())->not->toBeNull();
});

test('an archived inventory item still keeps its category from being removed', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Inventory::factory()->create(['categories_id' => $category->id])->delete();

    Livewire::actingAs($manager)
        ->test(CategoriesTable::class)
        ->call('confirmDelete', $category->id)
        ->call('deleteCategory')
        ->assertHasErrors('category');

    expect($category->fresh())->not->toBeNull();
});

test('a staff member may not remove a category', function () {
    $category = Category::factory()->create();

    expect(Gate::forUser(User::factory()->staff()->create())->allows('delete', $category))->toBeFalse();
});
