<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Support\ActivityLogger;

/**
 * Resets the password of a user account.
 *
 * The deployment cannot send mail, so an administrator performs the reset and
 * hands the temporary password to the account holder. The account is flagged
 * so the holder has to choose their own password at the next sign in.
 */
class ResetUserPassword
{
    /**
     * The event that is recorded in the activity log.
     */
    public const string EVENT = 'password-reset';

    /**
     * Reset the password of the given user.
     *
     * The new password is never written to the activity log.
     */
    public function handle(User $user, string $password, bool $mustChangePassword = true): User
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => $mustChangePassword,
        ])->save();

        $this->recordActivity($user);

        return $user;
    }

    /**
     * Record that the password was reset, without storing the password.
     */
    protected function recordActivity(User $user): void
    {
        app(ActivityLogger::class)
            ->useLog('user')
            ->event(self::EVENT)
            ->performedOn($user)
            ->withProperty('password_reset', true)
            ->log('Password Reset');
    }
}
