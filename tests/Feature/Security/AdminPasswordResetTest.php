<?php

use App\Actions\ResetUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

test('an administrator may open the user accounts page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('a user without the permission may not open the user accounts page', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('a guest is redirected to the login page', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

test('an administrator may reset the password of an account', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->staff()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.user-password', ['user' => $user])
        ->assertOk()
        ->call('resetPassword')
        ->assertHasNoErrors();

    expect($user->fresh()->must_change_password)->toBeTrue()
        ->and($user->fresh()->password)->not->toBe($user->password);
});

test('the generated password is shown once and the account is flagged', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->staff()->create();

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.user-password', ['user' => $user])
        ->call('resetPassword');

    $issued = $component->get('issuedPassword');

    expect($issued)->toBeString()->not->toBeEmpty()
        ->and(Hash::check($issued, $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->must_change_password)->toBeTrue();
});

test('an administrator may choose the password and skip the forced change', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->staff()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.user-password', ['user' => $user])
        ->set('generatePassword', false)
        ->set('password', 'a-chosen-password')
        ->set('mustChangePassword', false)
        ->call('resetPassword')
        ->assertHasNoErrors();

    expect(Hash::check('a-chosen-password', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->must_change_password)->toBeFalse();
});

test('a chosen password that is too short is rejected', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->staff()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.user-password', ['user' => $user])
        ->set('generatePassword', false)
        ->set('password', 'short')
        ->call('resetPassword')
        ->assertHasErrors('password');
});

test('a user without the permission may not reset a password', function () {
    $staff = User::factory()->staff()->create();
    $user = User::factory()->staff()->create();

    Livewire::actingAs($staff)
        ->test('pages::admin.user-password', ['user' => $user])
        ->assertForbidden();
});

test('the reset is recorded in the activity log without the password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->staff()->create();

    app(ResetUserPassword::class)->handle($user, 'a-temporary-password');

    $activity = Activity::query()
        ->where('subject_type', $user->getMorphClass())
        ->where('subject_id', $user->getKey())
        ->where('event', ResetUserPassword::EVENT)
        ->firstOrFail();

    expect($activity->log_name)->toBe('user')
        ->and($activity->description)->toBe('Password Reset')
        ->and(json_encode($activity->attribute_changes))->not->toContain('a-temporary-password')
        ->and(json_encode($activity->properties))->not->toContain('a-temporary-password');
});

test('an account that was reset is sent to the password settings', function () {
    $user = User::factory()->staff()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('security.edit'));
});

test('an account that was not reset reaches the dashboard', function () {
    $user = User::factory()->staff()->create(['must_change_password' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
