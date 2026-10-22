<?php

namespace App\Rules;

use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * The validation rules of the client forms.
 *
 * The rules live in one place so the create form, the edit form and the
 * import of a client agree on what a valid client is.
 */
class ClientRules
{
    /**
     * The rules of the details of a client.
     *
     * A client name identifies a client for the whole life of the record, so
     * it stays unique even when the client is archived.
     *
     * @param  Client|null  $client  The client that is being changed, if any.
     * @param  int|string|null  $provinceId  The province the city has to belong to.
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public static function personal(?Client $client = null, int|string|null $provinceId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'name')->ignore($client?->getKey()),
            ],
            'client_number' => ['required', 'string', 'max:255'],
            'line_address' => ['required', 'string', 'max:255'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => [
                'required',
                'integer',
                'exists:cities,id',
                self::cityBelongsToProvince($provinceId),
            ],
            'agent_id' => ['nullable', 'integer', 'exists:client_agents,id'],
        ];
    }

    /**
     * The rule that keeps the city inside the chosen province.
     */
    protected static function cityBelongsToProvince(int|string|null $provinceId): Exists
    {
        $rule = Rule::exists('cities', 'id');

        if ($provinceId === null || $provinceId === '') {
            return $rule;
        }

        return $rule->where('province_id', $provinceId);
    }
}
