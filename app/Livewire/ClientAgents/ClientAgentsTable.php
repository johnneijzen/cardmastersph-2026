<?php

namespace App\Livewire\ClientAgents;

use App\Models\ClientAgent;
use App\Services\ClientAgentService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * The list of the agents that acquired the clients.
 *
 * The table only ever holds the records that the signed in user is allowed to
 * see, because the ability to list the agents is checked before the query is
 * built.
 */
class ClientAgentsTable extends DataTableComponent
{
    /**
     * The agent that is about to be removed.
     */
    #[Locked]
    public ?int $deletingAgentId = null;

    /**
     * Whether the confirmation of the removal is shown.
     */
    public bool $showDeleteModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('viewAny', ClientAgent::class);
    }

    /**
     * The configuration of the table.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setEmptyMessage(__('No agents match the current filters.'))
            ->setDefaultSort('name', 'asc')
            ->setDefaultPerPage(25)
            ->setPerPageAccepted([10, 25, 50, 100]);

        $this->setTableName('client-agents-table');
    }

    /**
     * The query behind the table.
     *
     * @return Builder<ClientAgent>
     */
    public function builder(): Builder
    {
        return ClientAgent::query()
            ->withCount('clients');
    }

    /**
     * The agent that is about to be removed.
     */
    #[Computed]
    public function deletingAgent(): ?ClientAgent
    {
        if ($this->deletingAgentId === null) {
            return null;
        }

        return ClientAgent::query()
            ->withCount('clients')
            ->find($this->deletingAgentId);
    }

    /**
     * Ask for a confirmation before an agent is removed.
     */
    public function confirmDelete(int $agentId): void
    {
        $agent = ClientAgent::query()->findOrFail($agentId);

        Gate::authorize('delete', $agent);

        $this->deletingAgentId = $agentId;
        $this->showDeleteModal = true;
        $this->resetErrorBag();
    }

    /**
     * Remove the agent that was confirmed for removal.
     */
    public function deleteAgent(ClientAgentService $agents): void
    {
        $agent = ClientAgent::query()->findOrFail($this->deletingAgentId);

        Gate::authorize('delete', $agent);

        $agents->delete($agent);

        $this->deletingAgentId = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('The agent was removed.'));
    }

    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('livewire.client-agents.client-agents-table');
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

            Column::make(__('Clients'))
                ->label(fn (ClientAgent $clientAgent): string => number_format((int) $clientAgent->clients_count)),

            Column::make(__('Actions'))
                ->label(fn (ClientAgent $clientAgent): string => view('livewire.client-agents.actions', [
                    'clientAgent' => $clientAgent,
                ])->render())
                ->html(),
        ];
    }
}
