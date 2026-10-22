<?php

namespace App\Rules;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the employee forms.
 *
 * The rules live in one place so the create form, the edit form and the
 * account management of an employee all agree on what a valid employee is.
 */
class EmployeeRules
{
    /**
     * The rules of the personal details of an employee.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function personal(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'birthday' => ['required', 'date', 'before:today'],
            'contact_number' => ['required', 'string', 'max:255'],
            'emergency_contact_person_name' => ['required', 'string', 'max:255'],
            'emergency_contact_person_number' => ['required', 'string', 'max:255'],
            'line_address' => ['required', 'string', 'max:255'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
        ];
    }

    /**
     * The rules of the account that belongs to an employee.
     *
     * @param  User|null  $user  The account that is already linked, if any.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function account(?User $user = null): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $user === null
                    ? Rule::unique(User::class)
                    : Rule::unique(User::class)->ignore($user->getKey()),
            ],
            'role' => ['required', Rule::enum(Role::class)],
        ];
    }
}
