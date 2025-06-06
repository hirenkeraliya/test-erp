<?php

declare(strict_types=1);

use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSaleDetail\HoldSaleDetailQueries;
use App\Models\HoldSale;
use App\Models\HoldSaleDetail;
use App\Models\Member;

beforeEach(function (): void {
    $this->holdSaleDetailQueries = new HoldSaleDetailQueries();
});

test('new hold sale details can be added', function (): void {
    $holdSale = HoldSale::factory()->create();

    $saleDetails = [
        'offline_id' => '123',
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
        'total_amount_paid' => 1.00,
        'items_discount_amount' => 1.00,
        'total_discount_amount' => 1.00,
        'round_off' => 1.00,
        'return_items' => null,
        'items' => [
            [
                'id' => 1,
                'price' => 10,
                'quantity' => '1',
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
    ];

    $holdSaleData = new HoldSaleData(...$saleDetails);

    $this->holdSaleDetailQueries->addNew($holdSale->id, $holdSaleData, [
        'abc' => 'xyz',
    ], null,);

    $this->assertDatabaseHas('hold_sale_details', [
        'hold_sale_id' => $holdSale->id,
    ]);
});

test(
    'the updateMember method update the hold Sale Details queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $holdSaleDetail = HoldSaleDetail::factory()->create();

        $this->assertDatabaseHas(HoldSaleDetail::class, [
            'id' => $holdSaleDetail->getKey(),
            'member_id' => $holdSaleDetail->member_id,
        ]);

        $this->holdSaleDetailQueries->updateMember($holdSaleDetail->member_id, $member->getKey());

        $this->assertDatabaseHas(HoldSaleDetail::class, [
            'id' => $holdSaleDetail->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);
