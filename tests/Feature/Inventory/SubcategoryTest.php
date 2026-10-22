<?php

use App\Livewire\Subcategories\SubcategoriesTable;
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
    'subcategories.index',
    'subcategories.create',
]);

test('a user without the permission may not open the subcategory list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('subcategories.index'))
        ->assertForbidden();
});

test('a user without the permission may not search the subcategories', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(SubcategoriesTable::class)
        ->assertForbidden();
});

test('a staff member may open the subcategory list', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('subcategories.index'))
        ->assertOk()
        ->assertSee(__('Subcategories'));
});

test('the list shows the subcategories with their category and number of items', function () {
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create(['name' => 'RAW MATERIALS']);
    $subcategory = Subcategory::factory()->create([
        'name' => 'COTTON',
        'categories_id' => $category->id,
    ]);

    Inventory::factory()->create(['subcategories_id' => $subcategory->id]);

    $row = Livewire::actingAs($staff)
        ->test(SubcategoriesTable::class)
        ->set('search', 'COTTON')
        ->instance()
        ->getRows()
        ->firstWhere('name', 'COTTON');

    expect($row->category->name)->toBe('RAW MATERIALS')
        ->and($row->inventory_count)->toBe(1);
});

test('the list can be searched by the name of the subcategory', function () {
    $staff = User::factory()->staff()->create();

    Subcategory::factory()->create(['name' => 'COTTON']);
    Subcategory::factory()->create(['name' => 'POLYESTER']);

    Livewire::actingAs($staff)
        ->test(SubcategoriesTable::class)
        ->set('search', 'COTTON')
        ->assertSee('COTTON')
        ->assertDontSee('POLYESTER');
});

test('the list can be searched by the name of the category', function () {
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create(['name' => 'RAW MATERIALS']);

    Subcategory::factory()->create([
        'name' => 'COTTON',
        'categories_id' => $category->id,
    ]);
    Subcategory::factory()->create(['name' => 'POLYESTER']);

    $rows = Livewire::actingAs($staff)
        ->test(SubcategoriesTable::class)
        ->set('search', 'RAW MATERIALS')
        ->instance()
        ->getRows();

    expect($rows->pluck('name')->all())
        ->toContain('COTTON')
        ->not->toContain('POLYESTER');
});

test('the list can be filtered by the category of the subcategory', function () {
    $staff = User::factory()->staff()->create();
    $category = Category::factory()->create();

    Subcategory::factory()->create([
        'name' => 'COTTON',
        'categories_id' => $category->id,
    ]);
    Subcategory::factory()->create(['name' => 'POLYESTER']);

    Livewire::actingAs($staff)
        ->test(SubcategoriesTable::class)
        ->set('filterComponents.category', (string) $category->id)
        ->assertSee('COTTON')
        ->assertDontSee('POLYESTER');
});

test('archived subcategories are not listed', function () {
    $staff = User::factory()->staff()->create();

    Subcategory::factory()->create(['name' => 'ARCHIVED GROUP'])->delete();
    Subcategory::factory()->create(['name' => 'COTTON']);

    Livewire::actingAs($staff)
        ->test(SubcategoriesTable::class)
        ->set('search', 'COTTON')
        ->assertSee('COTTON');

    Livewire::actingAs($staff)
        ->test(SubcategoriesTable::class)
        ->set('search', 'ARCHIVED GROUP')
        ->assertDontSee('ARCHIVED GROUP');
});

test('the list is sorted by the name of the subcategory by default', function () {
    $staff = User::factory()->staff()->create();

    Subcategory::factory()->create(['name' => 'ZULU GROUP']);
    Subcategory::factory()->create(['name' => 'ALPHA GROUP']);

    $rows = Livewire::actingAs($staff)
        ->test(SubcategoriesTable::class)
        ->set('search', 'GROUP')
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('ALPHA GROUP');
});

test('the list can be sorted by the name of the subcategory', function () {
    $staff = User::factory()->staff()->create();

    Subcategory::factory()->create(['name' => 'ZULU GROUP']);
    Subcategory::factory()->create(['name' => 'ALPHA GROUP']);

    $rows = Livewire::actingAs($staff)
        ->test(SubcategoriesTable::class)
        ->set('search', 'GROUP')
        ->set('sorts', ['name' => 'desc'])
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('ZULU GROUP');
});

test('a staff member may not open the create page', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('subcategories.create'))
        ->assertForbidden();
});

test('a manager may add a subcategory to a category', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Livewire::actingAs($manager)
        ->test('pages::subcategories.create')
        ->set('name', 'COTTON')
        ->set('categories_id', (string) $category->id)
        ->call('save')
        ->assertHasNoErrors();

    $subcategory = Subcategory::query()->where('name', 'COTTON')->firstOrFail();

    expect($subcategory->categories_id)->toBe($category->id);
});

test('the subcategory form rejects an incomplete subcategory', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::subcategories.create')
        ->call('save')
        ->assertHasErrors([
            'name',
            'categories_id',
        ]);
});

test('a subcategory has to belong to an existing category', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::subcategories.create')
        ->set('name', 'COTTON')
        ->set('categories_id', '999999')
        ->call('save')
        ->assertHasErrors('categories_id');
});

test('the name of a subcategory has to be unique inside its category', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Subcategory::factory()->create([
        'name' => 'COTTON',
        'categories_id' => $category->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::subcategories.create')
        ->set('name', 'COTTON')
        ->set('categories_id', (string) $category->id)
        ->call('save')
        ->assertHasErrors('name');
});

test('two categories may hold a subcategory with the same name', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();

    Subcategory::factory()->create([
        'name' => 'COTTON',
        'categories_id' => $otherCategory->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::subcategories.create')
        ->set('name', 'COTTON')
        ->set('categories_id', (string) $category->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Subcategory::query()->where('name', 'COTTON')->count())->toBe(2);
});

test('the name of an archived subcategory may be used again', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Subcategory::factory()->create([
        'name' => 'COTTON',
        'categories_id' => $category->id,
    ])->delete();

    Livewire::actingAs($manager)
        ->test('pages::subcategories.create')
        ->set('name', 'COTTON')
        ->set('categories_id', (string) $category->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Subcategory::query()->where('name', 'COTTON')->exists())->toBeTrue();
});

test('a manager may change a subcategory and the category it belongs to', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $subcategory = Subcategory::factory()->create([
        'name' => 'COTTON',
        'categories_id' => $category->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::subcategories.edit', ['subcategory' => $subcategory])
        ->set('name', 'COTTON FABRIC')
        ->set('categories_id', (string) $otherCategory->id)
        ->call('save')
        ->assertHasNoErrors();

    $subcategory->refresh();

    expect($subcategory->name)->toBe('COTTON FABRIC')
        ->and($subcategory->categories_id)->toBe($otherCategory->id);
});

test('a subcategory may not take the name of another subcategory in its category', function () {
    $manager = User::factory()->manager()->create();
    $category = Category::factory()->create();

    Subcategory::factory()->create([
        'name' => 'COTTON',
        'categories_id' => $category->id,
    ]);
    $subcategory = Subcategory::factory()->create([
        'name' => 'POLYESTER',
        'categories_id' => $category->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::subcategories.edit', ['subcategory' => $subcategory])
        ->set('name', 'COTTON')
        ->call('save')
        ->assertHasErrors('name');

    expect($subcategory->fresh()->name)->toBe('POLYESTER');
});

test('a staff member may not change a subcategory', function () {
    $subcategory = Subcategory::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('pages::subcategories.edit', ['subcategory' => $subcategory])
        ->assertForbidden();
});

test('a manager may remove a subcategory that nothing uses', function () {
    $manager = User::factory()->manager()->create();
    $subcategory = Subcategory::factory()->create();

    Livewire::actingAs($manager)
        ->test(SubcategoriesTable::class)
        ->call('confirmDelete', $subcategory->id)
        ->call('deleteSubcategory')
        ->assertHasNoErrors();

    expect(Subcategory::withTrashed()->find($subcategory->id)->trashed())->toBeTrue();
});

test('a subcategory that still holds inventory items cannot be removed', function () {
    $manager = User::factory()->manager()->create();
    $subcategory = Subcategory::factory()->create();

    Inventory::factory()->create(['subcategories_id' => $subcategory->id]);

    Livewire::actingAs($manager)
        ->test(SubcategoriesTable::class)
        ->call('confirmDelete', $subcategory->id)
        ->call('deleteSubcategory')
        ->assertHasErrors('subcategory');

    expect($subcategory->fresh())->not->toBeNull();
});

test('an archived inventory item still keeps its subcategory from being removed', function () {
    $manager = User::factory()->manager()->create();
    $subcategory = Subcategory::factory()->create();

    Inventory::factory()->create(['subcategories_id' => $subcategory->id])->delete();

    Livewire::actingAs($manager)
        ->test(SubcategoriesTable::class)
        ->call('confirmDelete', $subcategory->id)
        ->call('deleteSubcategory')
        ->assertHasErrors('subcategory');

    expect($subcategory->fresh())->not->toBeNull();
});

test('a staff member may not remove a subcategory', function () {
    $subcategory = Subcategory::factory()->create();

    expect(Gate::forUser(User::factory()->staff()->create())->allows('delete', $subcategory))->toBeFalse();
});
