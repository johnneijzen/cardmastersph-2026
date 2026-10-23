<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\JobOrder;
use App\Models\WithdrawalSlip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Issues and reverses the inventory withdrawal slips.
 *
 * The slip and the stock movements it causes are written in one transaction
 * while the withdrawn items are locked, so two slips can never take the same
 * stock and a slip is never written without its movements.
 */
class WithdrawalSlipService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly NumberSequenceService $numbers,
    ) {}

    /**
     * Issue a withdrawal slip and take the withdrawn items out of the stock.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): WithdrawalSlip
    {
        $lines = $this->lines($data['items'] ?? []);
        $date = Carbon::parse((string) $data['date']);

        $this->assertJobOrderBelongsToClient((int) $data['client_id'], (int) $data['job_order_id']);

        return DB::transaction(function () use ($data, $lines, $date, $userId): WithdrawalSlip {
            $items = $this->lockItems($lines);

            $slip = WithdrawalSlip::create([
                'slip_number' => $this->numbers->next('withdrawal-slip', 'WS', $date),
                'date' => $date->toDateString(),
                'client_id' => (int) $data['client_id'],
                'job_order_id' => (int) $data['job_order_id'],
                'employee_id' => (int) $data['employee_id'],
                'created_by' => $userId ?? $this->actorId(),
                'updated_by' => $userId ?? $this->actorId(),
            ]);

            foreach ($lines as $index => $line) {
                $item = $items[$line['inventory_id']];

                $this->inventory->withdraw(
                    $item,
                    $line['quantity'],
                    __('Withdrawal slip :number.', ['number' => $slip->slip_number]),
                    $userId,
                    "items.{$index}.quantity",
                );

                $slip->inventory()->attach($item->getKey(), [
                    'qty' => $line['quantity'],
                    'particulars' => $line['particulars'],
                ]);
            }

            $this->logIssued($slip, $lines, $userId);

            return $slip->load(['client', 'jobOrder', 'employee', 'inventory']);
        });
    }

    /**
     * Reverse a withdrawal slip and put the withdrawn stock back.
     *
     * The slip is archived instead of deleted, so the withdrawal history and
     * the stock movements stay readable. Reversing a slip twice is impossible:
     * the slip is locked and archived inside the transaction.
     */
    public function reverse(WithdrawalSlip $slip, ?int $userId = null): void
    {
        DB::transaction(function () use ($slip, $userId): void {
            $locked = WithdrawalSlip::query()
                ->whereKey($slip->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lines = $locked->inventory()->get();
            $items = $this->lockInventoryFor($lines->pluck('id')->all());

            $reversed = [];

            foreach ($lines as $line) {
                $item = $items->get($line->getKey());

                if (! $item instanceof Inventory) {
                    continue;
                }

                $this->inventory->returnStock(
                    $item,
                    (int) $line->pivot?->getAttribute('qty'),
                    __('Reversal of withdrawal slip :number.', ['number' => $locked->slip_number]),
                    $userId,
                );

                $reversed[] = [
                    'inventory_id' => (int) $line->getKey(),
                    'quantity' => (int) $line->pivot?->getAttribute('qty'),
                ];
            }

            $this->logReversed($locked, $reversed, $userId);

            $locked->delete();
        });
    }

    /**
     * Make sure the job order is an active job order of the client.
     */
    protected function assertJobOrderBelongsToClient(int $clientId, int $jobOrderId): void
    {
        $belongsToClient = JobOrder::query()
            ->whereKey($jobOrderId)
            ->whereHas('order', function (Builder $orders) use ($clientId): void {
                $orders->where('client_id', $clientId);
            })
            ->exists();

        if (! $belongsToClient) {
            throw ValidationException::withMessages([
                'job_order_id' => __('The job order does not belong to the chosen client.'),
            ]);
        }
    }

    /**
     * Read the withdrawn items and make sure an item is only named once.
     *
     * @param  array<int|string, mixed>  $items
     * @return array<int, array{inventory_id: int, quantity: int, particulars: string}>
     */
    protected function lines(array $items): array
    {
        $lines = [];
        $seen = [];

        foreach (array_values($items) as $index => $item) {
            $item = (array) $item;

            $inventoryId = (int) ($item['inventory_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $particulars = trim((string) ($item['particulars'] ?? ''));

            if ($inventoryId < 1 || $quantity < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => __('Every withdrawn item needs an item and a quantity of at least one.'),
                ]);
            }

            if ($particulars === '') {
                throw ValidationException::withMessages([
                    "items.{$index}.particulars" => __('Every withdrawn item needs its particulars.'),
                ]);
            }

            if (in_array($inventoryId, $seen, true)) {
                throw ValidationException::withMessages([
                    'items' => __('An item may only be withdrawn once on the same slip.'),
                ]);
            }

            $seen[] = $inventoryId;

            $lines[] = [
                'inventory_id' => $inventoryId,
                'quantity' => $quantity,
                'particulars' => $particulars,
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'items' => __('A withdrawal slip needs at least one item.'),
            ]);
        }

        return $lines;
    }

    /**
     * Lock the items of the slip so no other slip can take the same stock.
     *
     * @param  array<int, array{inventory_id: int, quantity: int, particulars: string}>  $lines
     * @return Collection<int, Inventory>
     */
    protected function lockItems(array $lines): Collection
    {
        $items = $this->lockInventoryFor(array_column($lines, 'inventory_id'));

        foreach ($lines as $index => $line) {
            $item = $items->get($line['inventory_id']);

            if (! $item instanceof Inventory) {
                throw ValidationException::withMessages([
                    "items.{$index}.inventory_id" => __('The chosen item is no longer in the inventory.'),
                ]);
            }

            if ($line['quantity'] > $item->quantity()) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => __('Only :available of :item are on hand.', [
                        'available' => $item->quantity(),
                        'item' => $item->item_name,
                    ]),
                ]);
            }
        }

        return $items;
    }

    /**
     * Lock the given inventory items in a stable order, including archived
     * items, so a reversal can always put the stock back.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Inventory>
     */
    protected function lockInventoryFor(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return Inventory::withTrashed()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Inventory $item): int => (int) $item->getKey());
    }

    /**
     * Record that the slip was issued, including the items it withdrew.
     *
     * @param  array<int, array{inventory_id: int, quantity: int, particulars: string}>  $lines
     */
    protected function logIssued(WithdrawalSlip $slip, array $lines, ?int $userId): void
    {
        activity('withdrawal-slip')
            ->performedOn($slip)
            ->causedBy($userId)
            ->withProperties([
                'slip_number' => $slip->slip_number,
                'items' => $lines,
            ])
            ->log('Withdrawal slip issued');
    }

    /**
     * Record that the slip was reversed.
     *
     * @param  array<int, array{inventory_id: int, quantity: int}>  $items
     */
    protected function logReversed(WithdrawalSlip $slip, array $items, ?int $userId): void
    {
        activity('withdrawal-slip')
            ->performedOn($slip)
            ->causedBy($userId)
            ->withProperties([
                'slip_number' => $slip->slip_number,
                'items' => $items,
            ])
            ->log('Withdrawal slip reversed');
    }

    /**
     * The account that is recorded as the author of the slip.
     */
    protected function actorId(): ?int
    {
        $actorId = auth()->id();

        return $actorId === null ? null : (int) $actorId;
    }
}
