<?php

use App\Models\CloseCounterDenomination;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $closeCounterPayments = DB::table('close_counter_denominations')
            ->select('counter_update_id', 'denomination', DB::raw('COUNT(id) as record_count'))
            ->groupBy('counter_update_id', 'denomination')
            ->having('record_count', '>', 1)
            ->get();

        foreach ($closeCounterPayments as $closeCounterPayment) {
            $closeCounterDenominations = CloseCounterDenomination::query()
                ->where('counter_update_id', $closeCounterPayment->counter_update_id)
                ->get()
                ->groupBy('denomination')
                ->filter(fn ($group): bool => $group->count() > 1);

            foreach ($closeCounterDenominations as $groupCloseCounterDenominations) {
                foreach ($groupCloseCounterDenominations as $key => $closeCounterDenomination) {
                    if (0 === $key) {
                        continue;
                    }

                    $closeCounterDenomination->delete();
                }
            }
        }
    }
};
