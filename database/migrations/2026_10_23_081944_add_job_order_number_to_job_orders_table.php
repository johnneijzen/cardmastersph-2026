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
     * The legacy application used the primary key of the job order as its
     * number, so there was no sequence and a number could never be quoted
     * without the internal id. The number is added to the existing rows with
     * an `L` marker before the unique index is created, so a legacy number can
     * never collide with a number handed out from now on.
     */
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('job_order_number', 32)->nullable()->after('id');
        });

        $this->backfillJobOrderNumbers();

        Schema::table('job_orders', function (Blueprint $table) {
            $table->unique('job_order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropUnique(['job_order_number']);
            $table->dropColumn('job_order_number');
        });
    }

    /**
     * Give every job order that was carried over from the legacy application
     * a number based on the year of its order date.
     */
    protected function backfillJobOrderNumbers(): void
    {
        do {
            $jobOrders = DB::table('job_orders')
                ->leftJoin('orders', 'orders.id', '=', 'job_orders.order_id')
                ->select(['job_orders.id', 'orders.order_date'])
                ->whereNull('job_orders.job_order_number')
                ->orderBy('job_orders.id')
                ->limit(500)
                ->get();

            foreach ($jobOrders as $jobOrder) {
                $year = $jobOrder->order_date === null
                    ? '0000'
                    : substr((string) $jobOrder->order_date, 0, 4);

                DB::table('job_orders')
                    ->where('id', $jobOrder->id)
                    ->update([
                        'job_order_number' => sprintf('JO-%s-L%06d', $year, $jobOrder->id),
                    ]);
            }
        } while ($jobOrders->isNotEmpty());
    }
};
