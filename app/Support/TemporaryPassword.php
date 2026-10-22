<?php

namespace App\Support;

/**
 * Generates the temporary passwords an administrator hands out when resetting
 * an account.
 *
 * The password avoids characters that are easily confused when it is read
 * out loud or copied from a screen.
 */
final class TemporaryPassword
{
    /**
     * The characters the password is built from.
     */
    private const string ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

    /**
     * Generate a temporary password.
     */
    public static function generate(int $length = 12): string
    {
        $alphabet = self::ALPHABET;
        $maximum = strlen($alphabet) - 1;

        $password = '';

        for ($index = 0; $index < $length; $index++) {
            $password .= $alphabet[random_int(0, $maximum)];
        }

        return $password;
    }
}
