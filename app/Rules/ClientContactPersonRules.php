<?php

namespace App\Rules;

use App\Models\ClientContactPerson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the contract person forms.
 *
 * The rules live in one place so the create form and the edit form agree on
 * what a valid contract person is.
 */
class ClientContactPersonRules
{
    /**
     * The rules of the details of a contract person.
     *
     * @param  ClientContactPerson|null  $contactPerson  The record that is being changed, if any.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function personal(?ClientContactPerson $contactPerson = null): array
    {
        return [
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
