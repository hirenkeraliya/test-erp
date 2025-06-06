<?php

declare(strict_types=1);

use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Jobs\CreditNoteExpirationJob;
use App\Domains\CreditNoteExpiration\CreditNoteExpirationQueries;
use App\Models\CreditNote;

test(
    'CreditNoteExpirationJob job calls respective methods and expired credit note as expected',
    function (): void {
        $creditNotes = [];
        $creditNotes[] = CreditNote::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'available_amount' => 10,
        ]);

        $creditNotes[] = CreditNote::factory()->make([
            'id' => 2,
            'counter_update_id' => 1,
            'sale_return_id' => 1,
            'cancel_layaway_sale_id' => 1,
            'member_id' => 1,
            'available_amount' => 10,
        ]);

        $this->mock(CreditNoteExpirationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNotes): void {
            $mock->shouldReceive('getActiveWithExpirationDue')
                ->once()
                ->andReturn(collect($creditNotes));

            $mock->shouldReceive('markAseExpired')
                ->times(2);
        });

        CreditNoteExpirationJob::dispatch()->onQueue(config('horizon.default_queue_name'));
    }
);
