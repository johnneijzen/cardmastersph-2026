<?php

namespace App\Livewire\ClientContactPersons;

use App\Models\ClientAgent;
use App\Models\ClientContactPerson;
use App\Models\Province;
use App\Services\ClientContactPersonService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * The list of the contract persons of the clients.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the contract persons is checked before the
 * query is built.
 */
class ClientContactPersonsTable extends DataTableComponent
{
    /**
     * The contract person that is about to be removed.
     */
    #[Locked]
    public ?int $deletingContactPersonId = null;

    /**
     * Whether the confirmation of the removal is shown.
     */
    public bool $showDeleteModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', ClientContactPerson::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No contract persons match the current filters.'))
            ->setDefaultSort('contact_person', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('client-contact-persons-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<ClientContactPerson>
     */
    public function builder(): Builder
    {
        return ClientContactPerson::query()
            ->with('client');
    }

    /**
     * The contract person that is about to be removed.
     */
    #[Computed]
    public function deletingContactPerson(): ?ClientContactPerson
    {
        if ($this->deletingContactPersonId === null) {
            return null;
        }

        return ClientContactPerson::query()
            ->with('client')
            ->find($this->deletingContactPersonId);
    }

    /**
     * Ask for a confirmation before a contract person is removed.
     */
    public function confirmDelete(int $contactPersonId): void
    {
        $contactPerson = ClientContactPerson::query()->findOrFail($contactPersonId);

        Gate::authorize('delete', $contactPerson);

        $this->deletingContactPersonId = $contactPersonId;
        $this->showDeleteModal = true;
        $this->resetErrorBag();
    }

    /**
     * Remove the contract person that was confirmed for removal.
     */
    public function deleteContactPerson(ClientContactPersonService $contactPersons): void
    {
        $contactPerson = ClientContactPerson::query()->findOrFail($this->deletingContactPersonId);

        Gate::authorize('delete', $contactPerson);

        $contactPersons->delete($contactPerson);

        $this->deletingContactPersonId = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('The contract person was removed.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.client-contact-persons.client-contact-persons-table');
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

            Column::make(__('Client'), 'client.name')
                ->searchable()
                ->sortable(),

            Column::make(__('Contact person'), 'contact_person')
                ->searchable()
                ->sortable(),

            Column::make(__('Contact number'), 'contact_number')
                ->searchable(),

            Column::make(__('E-mail address'), 'contact_email')
                ->searchable(),

            Column::make(__('Actions'))
                ->label(fn (ClientContactPerson $contactPerson): string => view('livewire.client-contact-persons.actions', [
                    'clientContactPerson' => $contactPerson,
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
            SelectFilter::make(__('Agent'))
                ->options($this->agentOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->whereHas('client', function (Builder $clients) use ($value): void {
                        $clients->where('agent_id', $value);
                    });
                }),

            SelectFilter::make(__('Province'))
                ->options($this->provinceOptions())
                ->filter(function (Builder $builder, string $value): void {
                    $builder->whereHas('client.address.city', function (Builder $cities) use ($value): void {
                        $cities->where('province_id', $value);
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
}
