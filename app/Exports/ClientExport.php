<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

/**
 * The export of the client list.
 *
 * The export holds one row per contract person. A client without a contract
 * person is written with empty contract person columns, so no client is lost,
 * and a client with several contract persons is written once per contract
 * person.
 */
class ClientExport extends SpreadsheetQueryExport
{
    /**
     * @param  Builder<Client>  $query
     */
    public function __construct(private readonly Builder $query) {}

    /**
     * The headings of the exported sheet.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Client ID',
            'Client Name',
            'Client Phone Number',
            'Client Line Address',
            'Client City',
            'Client Province',
            'Client Agent',
            'Contract Person',
            'Contract Person Number',
            'Contract Person E-mail Address',
            'Client Created At',
            'Client Updated At',
        ];
    }

    /**
     * The query behind the export.
     *
     * @return Builder<Client>
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * Build the row of a client and its contract person.
     *
     * @return array<int|string, mixed>
     */
    public function map(mixed $row): array
    {
        return $this->row([
            $row->id,
            $row->name,
            $row->client_number,
            $row->address?->line_address,
            $row->address?->city?->name,
            $row->address?->city?->province?->name,
            $row->clientAgent?->name,
            $row->export_contact_person,
            $row->export_contact_number,
            $row->export_contact_email,
            $row->created_at ? date('Y-m-d H:i', (int) $row->created_at) : '',
            $row->updated_at ? date('Y-m-d H:i', (int) $row->updated_at) : '',
        ]);
    }
}
