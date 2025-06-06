<?php

declare(strict_types=1);

use App\Domains\DraftProductTransaction\DraftProductTransactionQueries;
use App\Domains\Product\Enums\Statuses;
use App\Models\Company;
use App\Models\DraftProductTransaction;
use App\Models\Product;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
    ]);

    $this->productA = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'ABCD',
        'code' => 'A1236',
        'upc' => 'UPC',
        'article_number' => '1234',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
    ]);

    $this->draftProductTransactionData = DraftProductTransaction::factory()->make([
        'product_id' => $this->productA->id,
    ])->toArray();

    $this->draftProductTransactionQueries = new DraftProductTransactionQueries();
});

test('Draft Product Transaction can be added', function (): void {
    $this->draftProductTransactionQueries->addNew($this->draftProductTransactionData);

    $this->assertDatabaseHas('draft_product_transactions', [
        'product_id' => $this->productA->id,
        'approved_by_id' => $this->draftProductTransactionData['approved_by_id'],
        'approved_by_type' => $this->draftProductTransactionData['approved_by_type'],
        'approved_at' => $this->draftProductTransactionData['approved_at'],
    ]);
});
