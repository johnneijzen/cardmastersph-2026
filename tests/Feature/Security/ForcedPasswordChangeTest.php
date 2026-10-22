<?php

use App\Actions\ResetUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('the holder clears the flag after choosing their own password', function () {
    $user = User::factory()->staff()->create();

    app(ResetUserPassword::class)->handle($user, 'the-temporary-password');

    expect($user->fresh()->must_change_password)->toBeTrue();

    Livewire::actingAs($user)
        ->test('pages::settings.security')
        ->set('current_password', 'the-temporary-password')
        ->set('password', 'a-brand-new-password')
        ->set('password_confirmation', 'a-brand-new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $fresh = $user->fresh();

    expect($fresh->must_change_password)->toBeFalse()
        ->and(Hash::check('a-brand-new-password', $fresh->password))->toBeTrue();
});

test('the wrong temporary password does not change anything', function () {
    $user = User::factory()->staff()->create();

    app(ResetUserPassword::class)->handle($user, 'the-temporary-password');

    Livewire::actingAs($user)
        ->test('pages::settings.security')
        ->set('current_password', 'not-the-temporary-password')
        ->set('password', 'a-brand-new-password')
        ->set('password_confirmation', 'a-brand-new-password')
        ->call('updatePassword')
        ->assertHasErrors('current_password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});
