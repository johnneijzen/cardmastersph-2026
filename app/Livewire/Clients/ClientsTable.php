<?php

namespace App\Livewire\Clients;

use App\Exports\ClientExport;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientAgent;
use App\Models\Province;
use App\Services\ClientService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Maatwebsite\Excel\Facades\Excel;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * The list of the clients.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the clients is checked before the query
 * is built.
 */
class ClientsTable extends DataTableComponent
{
    /**
     * The client that is about to be archived.
     */
    #[Locked]
    public ?int $archivingClientId = null;

    /**
     * Whether the confirmation of the archive is shown.
     */
    public bool $showArchiveModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', Client::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No clients match the current filters.'))
            ->setDefaultSort('name', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('clients-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<Client>
     */
    public function builder(): Builder
    {
        return Client::query()
            ->with(['address.city.province', 'clientAgent'])
            ->withCount('clientContactPersons');
    }

    /**
     * The client that is about to be archived.
     */
    #[Computed]
    public function archivingClient(): ?Client
    {
        if ($this->archivingClientId === null) {
            return null;
        }

        return Client::query()
            ->with(['address.city.province', 'clientAgent'])
            ->find($this->archivingClientId);
    }

    /**
     * Ask for a confirmation before a client is archived.
     */
    public function confirmArchive(int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);

        Gate::authorize('archive', $client);

        $this->archivingClientId = $clientId;
        $this->showArchiveModal = true;
        $this->resetErrorBag();
    }

    /**
     * Archive the client that was confirmed for archiving.
     */
    public function archiveClient(ClientService $clients): void
    {
        $client = Client::query()->findOrFail($this->archivingClientId);

        Gate::authorize('archive', $client);

        $clients->archive($client);

        $this->archivingClientId = null;
        $this->showArchiveModal = false;

        Flux::toast(variant: 'success', text: __('The client was archived.'));
    }

    /**
     * Put an archived client back on the active list.
     */
    public function restoreClient(int $clientId, ClientService $clients): void
    {
        $client = Client::withTrashed()->findOrFail($clientId);

        Gate::authorize('restore', $client);

        $clients->restore($client);

        Flux::toast(variant: 'success', text: __('The client was restored.'));
    }

    /**
     * Download the clients that match the current filters.
     */
    public function export(): mixed
    {
        Gate::authorize('export', Client::class);

        return Excel::download(
            new ClientExport($this->exportQuery()),
            'clients-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * The query behind the export.
     *
     * The query is built by the table itself, so the export holds exactly the
     * clients that the list shows, including the search term, the filters and
     * the columns the user may see.
     *
     * @return Builder<Client>
     */
    public function exportQuery(): Builder
    {
        $this->baseQuery();

        return $this->getBuilder()
            ->select('clients.*')
            ->leftJoin('client_contact_persons', 'clients.id', '=', 'client_contact_persons.client_id')
            ->addSelect([
                'client_contact_persons.id as export_contact_person_id',
                'client_contact_persons.contact_person as export_contact_person',
                'client_contact_persons.contact_number as export_contact_number',
                'client_contact_persons.contact_email as export_contact_email',
            ])
            ->with(['address.city.province', 'clientAgent'])
            ->orderBy('clients.id')
            ->orderBy('client_contact_persons.id');
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.clients.clients-table');
    }

    /**
     * The columns of the table.
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('ID'), 'id')
                ->sortable(),

            Column::make(__('Name'), 'name')
                ->searchable()
                ->sortable(),

            Column::make(__('Phone number'), 'client_number')
                ->searchable()
                ->sortable(),

            Column::make(__('Address'), 'address.line_address')
                ->searchable(),

            Column::make(__('City'), 'address.city.name')
                ->searchable(),

            Column::make(__('Province'), 'address.city.province.name'),

            Column::make(__('Agent'), 'clientAgent.name')
                ->searchable(),

            Column::make(__('Contract persons'))
                ->label(fn (Client $client): string => number_format((int) $client->client_contact_persons_count)),

            Column::make(__('Actions'))
                ->label(fn (Client $client): string => view('livewire.clients.actions', [
                    'client' => $client,
                ])->render())
                ->html(),
        ];
    }

    /**
     * The filters of the table.
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        return [
            SelectFilter::make(__('Status'))
                ->options([
                    'active' => __('Active'),
                    'archived' => __('Archived'),
                    'all' => __('Active and archived'),
                ])
                ->setFilterDefaultValue('active')
                ->filter(function (Builder $builder, string $value): void {
                    if ($value === 'active') {
                        return;
                    }

                    // The soft delete scope is removed, so the archived
                    // clients can be listed. An archived client is a row
                    // that carries a deletion date.
                    $builder->withoutGlobalScope(SoftDeletingScope::class);

                    if ($value === 'archived') {
                        $builder->whereNotNull('deleted_at');
                    }
                }),
            SelectFilter::make(__('Agent'))
                ->options($this->agentOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->where('agent_id', $value);
                }),

            SelectFilter::make(__('Province'))
                ->options($this->provinceOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->whereHas('address.city', function (Builder $cities) use ($value): void {
                        $cities->where('province_id', $value);
                    });
                }),

            SelectFilter::make(__('City'))
                ->options($this->cityOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->whereHas('address', function (Builder $addresses) use ($value): void {
                        $addresses->where('city_id', $value);
                    });
                }),
        ];
    }

    /**
     * The agents that the clients use.
     *
     * @return array<string, string>
     */
    protected function agentOptions(): array
    {
        return ClientAgent::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * The provinces that the clients are located in.
     *
     * @return array<string, string>
     */
    protected function provinceOptions(): array
    {
        return Province::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * The cities that the clients are located in.
     *
     * @return array<string, string>
     */
    protected function cityOptions(): array
    {
        return City::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
