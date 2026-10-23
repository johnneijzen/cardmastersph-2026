<?php

use App\Livewire\ProductSubclasses\ProductSubclassesTable;
use App\Models\JobOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('a guest is redirected to the login page', function (string $route) {
    $this->get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'product-subclasses.index',
    'product-subclasses.create',
]);

test('a user without the permission may not open the subclass list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('product-subclasses.index'))
        ->assertForbidden();
});

test('a user without the permission may not search the subclasses', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(ProductSubclassesTable::class)
        ->assertForbidden();
});

test('a staff member may open the subclass list', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('product-subclasses.index'))
        ->assertOk()
        ->assertSee(__('Subclasses'));
});

test('the list shows the subclasses with their class and number of products', function () {
    $staff = User::factory()->staff()->create();
    $productClass = ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);
    $productSubclass = ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);

    Product::factory()->create(['subclass_id' => $productSubclass->id]);

    $row = Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('search', 'ZTEST ACRYLIC')
        ->instance()
        ->getRows()
        ->firstWhere('name', 'ZTEST ACRYLIC');

    expect($row->productClass->name)->toBe('ZTEST BALLOON')
        ->and($row->products_count)->toBe(1);
});

test('the list can be searched by the name of the subclass', function () {
    $staff = User::factory()->staff()->create();

    ProductSubclass::factory()->create(['name' => 'ZTEST ACRYLIC']);
    ProductSubclass::factory()->create(['name' => 'ZTEST POLYESTER']);

    Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('search', 'ZTEST ACRYLIC')
        ->assertSee('ZTEST ACRYLIC')
        ->assertDontSee('ZTEST POLYESTER');
});

test('the list can be searched by the name of the class', function () {
    $staff = User::factory()->staff()->create();
    $productClass = ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);

    ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);
    ProductSubclass::factory()->create(['name' => 'ZTEST POLYESTER']);

    $rows = Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('search', 'ZTEST BALLOON')
        ->instance()
        ->getRows();

    expect($rows->pluck('name')->all())
        ->toContain('ZTEST ACRYLIC')
        ->not->toContain('ZTEST POLYESTER');
});

test('the list can be filtered by the class of the subclass', function () {
    $staff = User::factory()->staff()->create();
    $productClass = ProductClass::factory()->create();

    ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);
    ProductSubclass::factory()->create(['name' => 'ZTEST POLYESTER']);

    Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('filterComponents.class', (string) $productClass->id)
        ->assertSee('ZTEST ACRYLIC')
        ->assertDontSee('ZTEST POLYESTER');
});

test('the list can be filtered to the unclassified subclasses', function () {
    $staff = User::factory()->staff()->create();
    $productClass = ProductClass::factory()->create();

    ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);
    ProductSubclass::factory()->create(['name' => 'ZTEST POLYESTER']);

    Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('search', 'ZTEST')
        ->set('filterComponents.class', 'unclassified')
        ->assertSee('ZTEST POLYESTER')
        ->assertDontSee('ZTEST ACRYLIC');
});

test('archived subclasses are not listed', function () {
    $staff = User::factory()->staff()->create();

    ProductSubclass::factory()->create(['name' => 'ZTEST ARCHIVED'])->delete();
    ProductSubclass::factory()->create(['name' => 'ZTEST ACRYLIC']);

    Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('search', 'ZTEST ACRYLIC')
        ->assertSee('ZTEST ACRYLIC');

    Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('search', 'ZTEST ARCHIVED')
        ->assertDontSee('ZTEST ARCHIVED');
});

test('the list is sorted by the name of the subclass by default', function () {
    $staff = User::factory()->staff()->create();

    ProductSubclass::factory()->create(['name' => 'ZTEST ZULU']);
    ProductSubclass::factory()->create(['name' => 'ZTEST ALPHA']);

    $rows = Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('search', 'ZTEST')
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('ZTEST ALPHA');
});

test('the list can be sorted by the name of the subclass', function () {
    $staff = User::factory()->staff()->create();

    ProductSubclass::factory()->create(['name' => 'ZTEST ZULU']);
    ProductSubclass::factory()->create(['name' => 'ZTEST ALPHA']);

    $rows = Livewire::actingAs($staff)
        ->test(ProductSubclassesTable::class)
        ->set('search', 'ZTEST')
        ->set('sorts', ['name' => 'desc'])
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('ZTEST ZULU');
});

test('a staff member may not open the create page', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('product-subclasses.create'))
        ->assertForbidden();
});

test('a manager may add a subclass to a class', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.create')
        ->set('name', 'ZTEST ACRYLIC')
        ->set('product_class_id', (string) $productClass->id)
        ->call('save')
        ->assertHasNoErrors();

    $productSubclass = ProductSubclass::query()->where('name', 'ZTEST ACRYLIC')->firstOrFail();

    expect($productSubclass->product_class_id)->toBe($productClass->id);
});

test('the subclass form rejects an incomplete subclass', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.create')
        ->call('save')
        ->assertHasErrors([
            'name',
            'product_class_id',
        ]);
});

test('a subclass has to belong to an existing class', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.create')
        ->set('name', 'ZTEST ACRYLIC')
        ->set('product_class_id', '999999')
        ->call('save')
        ->assertHasErrors('product_class_id');
});

test('a new subclass may not belong to an archived class', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();
    $productClass->delete();

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.create')
        ->set('name', 'ZTEST ACRYLIC')
        ->set('product_class_id', (string) $productClass->id)
        ->call('save')
        ->assertHasErrors('product_class_id');

    expect(ProductSubclass::query()->where('name', 'ZTEST ACRYLIC')->exists())->toBeFalse();
});

test('the name of a subclass has to be unique inside its class', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();

    ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.create')
        ->set('name', 'ZTEST ACRYLIC')
        ->set('product_class_id', (string) $productClass->id)
        ->call('save')
        ->assertHasErrors('name');
});

test('two classes may hold a subclass with the same name', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();
    $otherClass = ProductClass::factory()->create();

    ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $otherClass->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.create')
        ->set('name', 'ZTEST ACRYLIC')
        ->set('product_class_id', (string) $productClass->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(ProductSubclass::query()->where('name', 'ZTEST ACRYLIC')->count())->toBe(2);
});

test('the name of an archived subclass may be used again', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();

    ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ])->delete();

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.create')
        ->set('name', 'ZTEST ACRYLIC')
        ->set('product_class_id', (string) $productClass->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(ProductSubclass::query()->where('name', 'ZTEST ACRYLIC')->exists())->toBeTrue();
});

test('a manager may change a subclass and the class it belongs to', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();
    $otherClass = ProductClass::factory()->create();
    $productSubclass = ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.edit', ['productSubclass' => $productSubclass])
        ->set('name', 'ZTEST ACRYLIC SHEET')
        ->set('product_class_id', (string) $otherClass->id)
        ->call('save')
        ->assertHasNoErrors();

    $productSubclass->refresh();

    expect($productSubclass->name)->toBe('ZTEST ACRYLIC SHEET')
        ->and($productSubclass->product_class_id)->toBe($otherClass->id);
});

test('a subclass may keep the archived class it already belongs to', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();
    $productSubclass = ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);

    $productClass->delete();

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.edit', ['productSubclass' => $productSubclass])
        ->set('name', 'ZTEST ACRYLIC SHEET')
        ->call('save')
        ->assertHasNoErrors();

    $productSubclass->refresh();

    expect($productSubclass->name)->toBe('ZTEST ACRYLIC SHEET')
        ->and($productSubclass->product_class_id)->toBe($productClass->id);
});

test('a subclass may not take the name of another subclass in its class', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();

    ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);
    $productSubclass = ProductSubclass::factory()->create([
        'name' => 'ZTEST POLYESTER',
        'product_class_id' => $productClass->id,
    ]);

    Livewire::actingAs($manager)
        ->test('pages::product-subclasses.edit', ['productSubclass' => $productSubclass])
        ->set('name', 'ZTEST ACRYLIC')
        ->call('save')
        ->assertHasErrors('name');

    expect($productSubclass->fresh()->name)->toBe('ZTEST POLYESTER');
});

test('a staff member may not change a subclass', function () {
    $productSubclass = ProductSubclass::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('pages::product-subclasses.edit', ['productSubclass' => $productSubclass])
        ->assertForbidden();
});

test('the subclass details show the name and the class it belongs to', function () {
    $staff = User::factory()->staff()->create();
    $productClass = ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);
    $productSubclass = ProductSubclass::factory()->create([
        'name' => 'ZTEST ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);

    Livewire::actingAs($staff)
        ->test('pages::product-subclasses.show', ['productSubclass' => $productSubclass])
        ->assertSee('ZTEST ACRYLIC')
        ->assertSee('ZTEST BALLOON')
        ->assertSee(__('Job order subclass'));
});

test('a user without the permission may not open the subclass details', function () {
    $productSubclass = ProductSubclass::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::product-subclasses.show', ['productSubclass' => $productSubclass])
        ->assertForbidden();
});

test('a manager may archive a subclass', function () {
    $manager = User::factory()->manager()->create();
    $productSubclass = ProductSubclass::factory()->create();

    Livewire::actingAs($manager)
        ->test(ProductSubclassesTable::class)
        ->call('confirmArchive', $productSubclass->id)
        ->call('archiveProductSubclass')
        ->assertHasNoErrors();

    expect(ProductSubclass::withTrashed()->find($productSubclass->id)->trashed())->toBeTrue();
});

test('archiving a subclass keeps the related products and job orders intact', function () {
    $manager = User::factory()->manager()->create();
    $productSubclass = ProductSubclass::factory()->create();

    $product = Product::factory()->create(['subclass_id' => $productSubclass->id]);
    $order = Order::factory()->create();
    $order->products()->attach($product->id, ['qty' => 2]);
    $jobOrder = JobOrder::factory()->create(['order_id' => $order->id]);

    Livewire::actingAs($manager)
        ->test(ProductSubclassesTable::class)
        ->call('confirmArchive', $productSubclass->id)
        ->call('archiveProductSubclass')
        ->assertHasNoErrors();

    expect($product->fresh()->subclass_id)->toBe($productSubclass->id)
        ->and($order->fresh()->products()->count())->toBe(1)
        ->and($jobOrder->fresh())->not->toBeNull();
});

test('a staff member may not archive a subclass', function () {
    $productSubclass = ProductSubclass::factory()->create();

    expect(Gate::forUser(User::factory()->staff()->create())->allows('delete', $productSubclass))->toBeFalse();
});
