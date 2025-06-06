<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\SaleItemComplimentary\SaleItemComplimentaryQueries;
use App\Models\Director;
use App\Models\SaleItem;

test('new sale item complimentary can be added', function (): void {
    $saleItem = SaleItem::factory()->create();
    $director = Director::factory()->create();
    $saleItemComplimentaryQueries = new SaleItemComplimentaryQueries();
    $saleItemComplimentaryQueries->addNew($saleItem->id, $director->id, ModelMapping::DIRECTOR->name, 10);

    $this->assertDatabaseHas('sale_item_complimentaries', [
        'sale_item_id' => $saleItem->id,
        'authorizer_id' => $director->id,
        'authorizer_type' => ModelMapping::DIRECTOR->name,
        'amount' => '10',
    ]);
});
