<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The legacy application changed `inventory.current_quantity` in place and
     * kept no stock movement ledger, so a quantity could not be explained
     * afterwards. Every quantity is now written through a movement that keeps
     * the quantity before, the change and the quantity after.
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory')->cascadeOnDelete();
            $table->string('type', 32);
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_id', 'created_at']);
        });

        $this->backfillOpeningMovements();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }

    /**
     * Record the quantity that was carried over from the legacy application.
     *
     * A quantity that is not a whole number is left alone; it is corrected
     * through a stock adjustment, which records why it changed.
     */
    protected function backfillOpeningMovements(): void
    {
        DB::table('inventory')
            ->select(['id', 'current_quantity', 'created_at'])
            ->whereNotNull('current_quantity')
            ->whereRaw("current_quantity REGEXP '^-?[0-9]+$'")
            ->orderBy('id')
            ->chunkById(500, function ($items): void {
                $now = now();

                $rows = $items->map(fn (object $item): array => [
                    'inventory_id' => $item->id,
                    'type' => 'opening',
                    'quantity' => (int) $item->current_quantity,
                    'quantity_before' => 0,
                    'quantity_after' => (int) $item->current_quantity,
                    'notes' => 'Opening balance carried over from the legacy application.',
                    'created_by' => null,
                    'created_at' => $item->created_at ?? $now,
                    'updated_at' => $item->created_at ?? $now,
                ])->all();

                DB::table('inventory_movements')->insert($rows);
            });
    }
};
