<?php

use App\Livewire\ProductClasses\ProductClassesTable;
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
    'product-classes.index',
    'product-classes.create',
]);

test('a user without the permission may not open the class list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('product-classes.index'))
        ->assertForbidden();
});

test('a user without the permission may not search the classes', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(ProductClassesTable::class)
        ->assertForbidden();
});

test('a staff member may open the class list', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('product-classes.index'))
        ->assertOk()
        ->assertSee(__('Classes'));
});

test('the list shows the classes with their subclasses and products', function () {
    $staff = User::factory()->staff()->create();
    $productClass = ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);

    ProductSubclass::factory()->count(2)->create(['product_class_id' => $productClass->id]);
    Product::factory()->count(3)->create(['class_id' => $productClass->id]);

    $row = Livewire::actingAs($staff)
        ->test(ProductClassesTable::class)
        ->set('search', 'ZTEST BALLOON')
        ->instance()
        ->getRows()
        ->firstWhere('name', 'ZTEST BALLOON');

    expect($row->subclasses_count)->toBe(5)
        ->and($row->products_count)->toBe(3);
});

test('the list can be searched by the name of the class', function () {
    $staff = User::factory()->staff()->create();

    ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);
    ProductClass::factory()->create(['name' => 'ZTEST EMBROIDERY']);

    Livewire::actingAs($staff)
        ->test(ProductClassesTable::class)
        ->set('search', 'ZTEST BALLOON')
        ->assertSee('ZTEST BALLOON')
        ->assertDontSee('ZTEST EMBROIDERY');
});

test('archived classes are not listed', function () {
    $staff = User::factory()->staff()->create();

    ProductClass::factory()->create(['name' => 'ZTEST ARCHIVED'])->delete();
    ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);

    Livewire::actingAs($staff)
        ->test(ProductClassesTable::class)
        ->set('search', 'ZTEST BALLOON')
        ->assertSee('ZTEST BALLOON');

    Livewire::actingAs($staff)
        ->test(ProductClassesTable::class)
        ->set('search', 'ZTEST ARCHIVED')
        ->assertDontSee('ZTEST ARCHIVED');
});

test('the list is sorted by the name of the class by default', function () {
    $staff = User::factory()->staff()->create();

    ProductClass::factory()->create(['name' => 'ZTEST ZULU']);
    ProductClass::factory()->create(['name' => 'ZTEST ALPHA']);

    $rows = Livewire::actingAs($staff)
        ->test(ProductClassesTable::class)
        ->set('search', 'ZTEST')
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('ZTEST ALPHA');
});

test('the list can be sorted by the name of the class', function () {
    $staff = User::factory()->staff()->create();

    ProductClass::factory()->create(['name' => 'ZTEST ZULU']);
    ProductClass::factory()->create(['name' => 'ZTEST ALPHA']);

    $rows = Livewire::actingAs($staff)
        ->test(ProductClassesTable::class)
        ->set('search', 'ZTEST')
        ->set('sorts', ['name' => 'desc'])
        ->instance()
        ->getRows();

    expect($rows->first()->name)->toBe('ZTEST ZULU');
});

test('a staff member may not open the create page', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('product-classes.create'))
        ->assertForbidden();
});

test('a manager may add a class', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::product-classes.create')
        ->set('name', 'ZTEST BALLOON')
        ->call('save')
        ->assertHasNoErrors();

    expect(ProductClass::query()->where('name', 'ZTEST BALLOON')->exists())->toBeTrue();
});

test('the class form rejects an incomplete class', function () {
    $manager = User::factory()->manager()->create();

    Livewire::actingAs($manager)
        ->test('pages::product-classes.create')
        ->call('save')
        ->assertHasErrors('name');
});

test('the name of an active class has to be unique', function () {
    $manager = User::factory()->manager()->create();
    ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);

    Livewire::actingAs($manager)
        ->test('pages::product-classes.create')
        ->set('name', 'ZTEST BALLOON')
        ->call('save')
        ->assertHasErrors('name');
});

test('the name of an archived class may be used again', function () {
    $manager = User::factory()->manager()->create();
    ProductClass::factory()->create(['name' => 'ZTEST BALLOON'])->delete();

    Livewire::actingAs($manager)
        ->test('pages::product-classes.create')
        ->set('name', 'ZTEST BALLOON')
        ->call('save')
        ->assertHasNoErrors();

    expect(ProductClass::query()->where('name', 'ZTEST BALLOON')->exists())->toBeTrue();
});

test('a manager may change a class', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);

    Livewire::actingAs($manager)
        ->test('pages::product-classes.edit', ['productClass' => $productClass])
        ->set('name', 'ZTEST BALLOONS')
        ->call('save')
        ->assertHasNoErrors();

    expect($productClass->fresh()->name)->toBe('ZTEST BALLOONS');
});

test('a class may keep its own name when it is changed', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);

    Livewire::actingAs($manager)
        ->test('pages::product-classes.edit', ['productClass' => $productClass])
        ->set('name', 'ZTEST BALLOON')
        ->call('save')
        ->assertHasNoErrors();
});

test('the name of a class may not become the name of another class', function () {
    $manager = User::factory()->manager()->create();
    ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);
    $productClass = ProductClass::factory()->create(['name' => 'ZTEST EMBROIDERY']);

    Livewire::actingAs($manager)
        ->test('pages::product-classes.edit', ['productClass' => $productClass])
        ->set('name', 'ZTEST BALLOON')
        ->call('save')
        ->assertHasErrors('name');

    expect($productClass->fresh()->name)->toBe('ZTEST EMBROIDERY');
});

test('a staff member may not change a class', function () {
    $productClass = ProductClass::factory()->create();

    Livewire::actingAs(User::factory()->staff()->create())
        ->test('pages::product-classes.edit', ['productClass' => $productClass])
        ->assertForbidden();
});

test('the class details show the name and the related records', function () {
    $staff = User::factory()->staff()->create();
    $productClass = ProductClass::factory()->create(['name' => 'ZTEST BALLOON']);

    ProductSubclass::factory()->create([
        'name' => 'ACRYLIC',
        'product_class_id' => $productClass->id,
    ]);
    Product::factory()->create(['class_id' => $productClass->id]);

    Livewire::actingAs($staff)
        ->test('pages::product-classes.show', ['productClass' => $productClass])
        ->assertSee('ZTEST BALLOON')
        ->assertSee('ACRYLIC')
        ->assertSee(__('Job order class'));
});

test('a user without the permission may not open the class details', function () {
    $productClass = ProductClass::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::product-classes.show', ['productClass' => $productClass])
        ->assertForbidden();
});

test('a manager may archive a class', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();

    Livewire::actingAs($manager)
        ->test(ProductClassesTable::class)
        ->call('confirmArchive', $productClass->id)
        ->call('archiveProductClass')
        ->assertHasNoErrors();

    expect(ProductClass::withTrashed()->find($productClass->id)->trashed())->toBeTrue();
});

test('archiving a class keeps the related products and job orders intact', function () {
    $manager = User::factory()->manager()->create();
    $productClass = ProductClass::factory()->create();

    $product = Product::factory()->create(['class_id' => $productClass->id]);
    $order = Order::factory()->create();
    $order->products()->attach($product->id, ['qty' => 2]);
    $jobOrder = JobOrder::factory()->create(['order_id' => $order->id]);

    Livewire::actingAs($manager)
        ->test(ProductClassesTable::class)
        ->call('confirmArchive', $productClass->id)
        ->call('archiveProductClass')
        ->assertHasNoErrors();

    expect($product->fresh()->class_id)->toBe($productClass->id)
        ->and($order->fresh()->products()->count())->toBe(1)
        ->and($jobOrder->fresh())->not->toBeNull();
});

test('a staff member may not archive a class', function () {
    $productClass = ProductClass::factory()->create();

    expect(Gate::forUser(User::factory()->staff()->create())->allows('delete', $productClass))->toBeFalse();
});
