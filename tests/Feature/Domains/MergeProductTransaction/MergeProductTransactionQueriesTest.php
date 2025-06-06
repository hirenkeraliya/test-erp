<?php

declare(strict_types=1);

use App\Domains\MergeProductTransaction\MergeProductTransactionQueries;
use App\Models\Admin;
use App\Models\MergeProductTransaction;
use App\Models\Product;

beforeEach(function (): void {
    $this->mergeProductTransaction = new MergeProductTransactionQueries();
});

test('can add merge product transaction can be added', function (): void {
    $user = Admin::factory()->create();
    $productAId = Product::factory()->create()->id;
    $productBId = Product::factory()->create()->id;

    $this->mergeProductTransaction->addNew($user, $productBId, $productAId);

    $this->assertDatabaseHas(MergeProductTransaction::class, [
        'user_id' => $user->id,
        'old_product_id' => $productBId,
        'new_product_id' => $productAId,
    ]);
});
