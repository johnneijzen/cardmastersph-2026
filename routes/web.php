<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('employees', 'employees.employees-table')->name('employees.index');
    Route::livewire('employees/create', 'pages::employees.create')->name('employees.create');
    Route::livewire('employees/{employee}', 'pages::employees.show')->name('employees.show');
    Route::livewire('employees/{employee}/edit', 'pages::employees.edit')->name('employees.edit');

    Route::livewire('inventory', 'inventory.inventory-table')->name('inventory.index');
    Route::livewire('inventory/create', 'pages::inventory.create')->name('inventory.create');
    Route::livewire('inventory/import', 'pages::inventory.import')->name('inventory.import');
    Route::livewire('inventory/{inventory}', 'pages::inventory.show')->name('inventory.show');
    Route::livewire('inventory/{inventory}/edit', 'pages::inventory.edit')->name('inventory.edit');

    Route::livewire('categories', 'categories.categories-table')->name('categories.index');
    Route::livewire('categories/create', 'pages::categories.create')->name('categories.create');
    Route::livewire('categories/{category}/edit', 'pages::categories.edit')->name('categories.edit');

    Route::livewire('subcategories', 'subcategories.subcategories-table')->name('subcategories.index');
    Route::livewire('subcategories/create', 'pages::subcategories.create')->name('subcategories.create');
    Route::livewire('subcategories/{subcategory}/edit', 'pages::subcategories.edit')->name('subcategories.edit');

    Route::livewire('product-classes', 'product-classes.product-classes-table')->name('product-classes.index');
    Route::livewire('product-classes/create', 'pages::product-classes.create')->name('product-classes.create');
    Route::livewire('product-classes/{productClass}', 'pages::product-classes.show')
        ->name('product-classes.show')
        ->withTrashed();
    Route::livewire('product-classes/{productClass}/edit', 'pages::product-classes.edit')->name('product-classes.edit');

    Route::livewire('product-subclasses', 'product-subclasses.product-subclasses-table')->name('product-subclasses.index');
    Route::livewire('product-subclasses/create', 'pages::product-subclasses.create')->name('product-subclasses.create');
    Route::livewire('product-subclasses/{productSubclass}', 'pages::product-subclasses.show')
        ->name('product-subclasses.show')
        ->withTrashed();
    Route::livewire('product-subclasses/{productSubclass}/edit', 'pages::product-subclasses.edit')->name('product-subclasses.edit');

    Route::livewire('withdrawal-slips', 'withdrawal-slips.withdrawal-slips-table')->name('withdrawal-slips.index');
    Route::livewire('withdrawal-slips/create', 'pages::withdrawal-slips.create')->name('withdrawal-slips.create');
    Route::livewire('withdrawal-slips/{withdrawalSlip}/print', 'pages::withdrawal-slips.print')
        ->name('withdrawal-slips.print')
        ->withTrashed();
    Route::livewire('withdrawal-slips/{withdrawalSlip}', 'pages::withdrawal-slips.show')
        ->name('withdrawal-slips.show')
        ->withTrashed();

    Route::livewire('job-orders', 'job-orders.job-orders-table')->name('job-orders.index');
    Route::livewire('job-orders/create', 'pages::job-orders.create')->name('job-orders.create');
    Route::livewire('job-orders/{jobOrder}', 'pages::job-orders.show')
        ->name('job-orders.show')
        ->withTrashed();
    Route::livewire('job-orders/{jobOrder}/print', 'pages::job-orders.print')
        ->name('job-orders.print')
        ->withTrashed();
    Route::livewire('job-orders/{jobOrder}/routing-slip', 'pages::job-orders.routing-slip')
        ->name('job-orders.routing-slip')
        ->withTrashed();
    Route::livewire('job-orders/{jobOrder}/edit', 'pages::job-orders.edit')->name('job-orders.edit');

    Route::livewire('clients', 'clients.clients-table')->name('clients.index');
    Route::livewire('clients/create', 'pages::clients.create')->name('clients.create');
    Route::livewire('clients/{client}', 'pages::clients.show')->name('clients.show')->withTrashed();
    Route::livewire('clients/{client}/edit', 'pages::clients.edit')->name('clients.edit');

    Route::livewire('client-contact-persons', 'client-contact-persons.client-contact-persons-table')
        ->name('client-contact-persons.index');
    Route::livewire('client-contact-persons/create', 'pages::client-contact-persons.create')
        ->name('client-contact-persons.create');
    Route::livewire('client-contact-persons/{clientContactPerson}', 'pages::client-contact-persons.show')
        ->name('client-contact-persons.show');
    Route::livewire('client-contact-persons/{clientContactPerson}/edit', 'pages::client-contact-persons.edit')
        ->name('client-contact-persons.edit');

    Route::livewire('client-agents', 'client-agents.client-agents-table')->name('client-agents.index');
    Route::livewire('client-agents/create', 'pages::client-agents.create')->name('client-agents.create');
    Route::livewire('client-agents/{clientAgent}', 'pages::client-agents.show')->name('client-agents.show');
    Route::livewire('client-agents/{clientAgent}/edit', 'pages::client-agents.edit')->name('client-agents.edit');

    Route::livewire('admin/users', 'pages::admin.users')
        ->name('admin.users.index');

    Route::livewire('admin/users/{user}/password', 'pages::admin.user-password')
        ->name('admin.users.password');
});

require __DIR__.'/settings.php';
