<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('employees', 'employees.employees-table')->name('employees.index');
    Route::livewire('employees/create', 'pages::employees.create')->name('employees.create');
    Route::livewire('employees/{employee}', 'pages::employees.show')->name('employees.show');
    Route::livewire('employees/{employee}/edit', 'pages::employees.edit')->name('employees.edit');

    Route::livewire('admin/users', 'pages::admin.users')
        ->name('admin.users.index');

    Route::livewire('admin/users/{user}/password', 'pages::admin.user-password')
        ->name('admin.users.password');
});

require __DIR__.'/settings.php';
