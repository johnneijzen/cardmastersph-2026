<?php

namespace App\Rules;

use App\Models\ClientAgent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The validation rules of the client agent forms.
 *
 * The rules live in one place so the create form and the edit form agree on
 * what a valid agent is.
 */
class ClientAgentRules
{
    /**
     * The rules of the details of a client agent.
     *
     * @param  ClientAgent|null  $clientAgent  The record that is being changed, if any.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function personal(?ClientAgent $clientAgent = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('client_agents', 'name')->ignore($clientAgent?->getKey()),
            ],
        ];
    }
}
