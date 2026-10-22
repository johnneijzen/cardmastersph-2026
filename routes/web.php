<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('employees', 'employees.employees-table')->name('employees.index');
    Route::livewire('employees/create', 'pages::employees.create')->name('employees.create');
    Route::livewire('employees/{employee}', 'pages::employees.show')->name('employees.show');
    Route::livewire('employees/{employee}/edit', 'pages::employees.edit')->name('employees.edit');

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
