<?php

use App\Exports\ClientExport;
use App\Livewire\Clients\ClientsTable;
use App\Models\Client;
use App\Models\ClientContactPerson;
use App\Models\User;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Read the rows of an export of the client list.
 *
 * @return array<int, array<int, mixed>>
 */
function clientExportRows(ClientsTable $table): array
{
    $contents = Excel::raw(new ClientExport($table->exportQuery()), ExcelWriter::XLSX);

    $path = tempnam(sys_get_temp_dir(), 'client-export').'.xlsx';

    file_put_contents($path, $contents);

    try {
        $spreadsheet = IOFactory::load($path);
    } finally {
        unlink($path);
    }

    return $spreadsheet->getActiveSheet()->toArray();
}

test('a user without the permission may not export the clients', function () {
    Livewire::actingAs(User::factory()->staff()->create())
        ->test(ClientsTable::class)
        ->call('export')
        ->assertForbidden();
});

test('a manager may export the clients', function () {
    $manager = User::factory()->manager()->create();

    Client::factory()->create(['name' => 'Silver Studio']);

    Livewire::actingAs($manager)
        ->test(ClientsTable::class)
        ->call('export')
        ->assertFileDownloaded();
});

test('the export holds the required headings', function () {
    $manager = User::factory()->manager()->create();

    $rows = clientExportRows(
        Livewire::actingAs($manager)->test(ClientsTable::class)->instance()
    );

    expect($rows[0])->toBe([
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
    ]);
});

test('the export holds one row per contract person', function () {
    $manager = User::factory()->manager()->create();
    $client = Client::factory()->create(['name' => 'Silver Studio']);

    ClientContactPerson::factory()->create([
        'client_id' => $client->id,
        'contact_person' => 'Maria Dela Cruz',
    ]);
    ClientContactPerson::factory()->create([
        'client_id' => $client->id,
        'contact_person' => 'Jose Rizal',
    ]);

    $rows = clientExportRows(
        Livewire::actingAs($manager)->test(ClientsTable::class)->instance()
    );

    expect($rows)->toHaveCount(3)
        ->and($rows[1][1])->toBe('Silver Studio')
        ->and($rows[1][7])->toBe('Maria Dela Cruz')
        ->and($rows[2][7])->toBe('Jose Rizal');
});

test('a client without a contract person is exported as well', function () {
    $manager = User::factory()->manager()->create();

    Client::factory()->create(['name' => 'Silver Studio']);

    $rows = clientExportRows(
        Livewire::actingAs($manager)->test(ClientsTable::class)->instance()
    );

    expect($rows)->toHaveCount(2)
        ->and($rows[1][1])->toBe('Silver Studio')
        ->and($rows[1][7])->toBeNull();
});

test('the export holds the address and the agent of the client', function () {
    $manager = User::factory()->manager()->create();
    $client = Client::factory()->create(['name' => 'Silver Studio']);

    $rows = clientExportRows(
        Livewire::actingAs($manager)->test(ClientsTable::class)->instance()
    );

    expect($rows[1][3])->toBe($client->address->line_address)
        ->and($rows[1][4])->toBe($client->address->city->name)
        ->and($rows[1][5])->toBe($client->address->city->province->name)
        ->and($rows[1][6])->toBe($client->clientAgent->name);
});

test('the export only holds the clients that match the search', function () {
    $manager = User::factory()->manager()->create();

    Client::factory()->create(['name' => 'Silver Studio']);
    Client::factory()->create(['name' => 'Golden Workshop']);

    $component = Livewire::actingAs($manager)
        ->test(ClientsTable::class)
        ->set('search', 'Silver');

    $rows = clientExportRows($component->instance());

    expect($rows)->toHaveCount(2)
        ->and($rows[1][1])->toBe('Silver Studio');
});

test('the export only holds the clients that match the filters', function () {
    $manager = User::factory()->manager()->create();

    Client::factory()->create(['name' => 'Silver Studio']);
    Client::factory()->archived()->create(['name' => 'Archived Studio']);

    $component = Livewire::actingAs($manager)
        ->test(ClientsTable::class)
        ->set('filterComponents.status', 'archived');

    $rows = clientExportRows($component->instance());

    expect($rows)->toHaveCount(2)
        ->and($rows[1][1])->toBe('Archived Studio');
});

test('the export does not hold the archived clients by default', function () {
    $manager = User::factory()->manager()->create();

    Client::factory()->create(['name' => 'Silver Studio']);
    Client::factory()->archived()->create(['name' => 'Archived Studio']);

    $rows = clientExportRows(
        Livewire::actingAs($manager)->test(ClientsTable::class)->instance()
    );

    expect($rows)->toHaveCount(2)
        ->and($rows[1][1])->toBe('Silver Studio');
});

test('the export protects the values against spreadsheet formula injection', function () {
    $manager = User::factory()->manager()->create();

    Client::factory()->create(['name' => '=HYPERLINK("http://example.com")']);

    $rows = clientExportRows(
        Livewire::actingAs($manager)->test(ClientsTable::class)->instance()
    );

    expect($rows[1][1])->toBe('\'=HYPERLINK("http://example.com")');
});
