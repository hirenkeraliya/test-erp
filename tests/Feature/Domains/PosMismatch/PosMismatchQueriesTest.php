<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Models\Sale;

test('new sale mismatches can be added', function (): void {
    $posMismatchQueries = new PosMismatchQueries();
    $sale = Sale::factory()->create();

    $posMismatchQueries->addNew($sale, 'Sale mismatch message goes here');

    $this->assertDatabaseHas('pos_mismatches', [
        'module_id' => $sale->id,
        'module_type' => ModelMapping::SALE->name,
        'message' => 'Sale mismatch message goes here',
    ]);
});
