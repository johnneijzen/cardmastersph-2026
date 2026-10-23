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
     * The legacy withdrawal slip only carried a date, a client and a job
     * order, so a slip had no number, no responsible employee and no way to be
     * withdrawn from the active list. The number is added to the existing rows
     * before the unique index is created.
     */
    public function up(): void
    {
        Schema::table('withdrawal_slips', function (Blueprint $table) {
            $table->string('slip_number', 32)->nullable()->after('id');
            $table->foreignId('employee_id')
                ->nullable()
                ->after('job_order_id')
                ->constrained('employees')
                ->nullOnDelete();
            $table->softDeletes();
        });

        $this->backfillSlipNumbers();

        Schema::table('withdrawal_slips', function (Blueprint $table) {
            $table->unique('slip_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_slips', function (Blueprint $table) {
            $table->dropUnique(['slip_number']);
            $table->dropConstrainedForeignId('employee_id');
            $table->dropSoftDeletes();
            $table->dropColumn('slip_number');
        });
    }

    /**
     * Give every slip that was carried over from the legacy application a
     * number that cannot collide with a number that is handed out from now on.
     */
    protected function backfillSlipNumbers(): void
    {
        DB::table('withdrawal_slips')
            ->select(['id', 'date'])
            ->whereNull('slip_number')
            ->orderBy('id')
            ->chunkById(500, function ($slips): void {
                foreach ($slips as $slip) {
                    $year = $slip->date === null ? '0000' : substr((string) $slip->date, 0, 4);

                    DB::table('withdrawal_slips')
                        ->where('id', $slip->id)
                        ->update([
                            'slip_number' => sprintf('WS-%s-L%06d', $year, $slip->id),
                        ]);
                }
            });
    }
};
