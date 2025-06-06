<?php

declare(strict_types=1);

use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Services\CreditNoteService;
use App\Domains\Sale\DataObjects\SaleData;
use App\Models\CreditNote;
use App\Models\SaleReturn;

beforeEach(function (): void {
    $this->saleDetails = [
        'offline_sale_id' => '1',
        'employee_id' => null,
        'vouchers' => null,
        'cashback_id' => null,
        'cashback_amount' => null,
        'cashback_round_off_amount' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promotion_id' => 1,
            ],
        ],
        'return_items' => [
            [
                'sale_item_id' => 1,
                'price_paid_per_unit' => '11.00',
                'quantity' => '5',
                'sale_return_details' => [
                    [
                        'quantity' => '2.00',
                        'sale_return_reason_id' => '1',
                        'batch_number' => '123456',
                    ],
                    [
                        'quantity' => '3.00',
                        'sale_return_reason_id' => '2',
                        'batch_number' => 'ABCDEF',
                    ],
                ],
            ],
        ],
        'payments' => [
            [
                'type_id' => 4,
                'amount' => '100',
                'credit_note_id' => 1,
            ],
        ],
        'sale_notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'is_layaway' => true,
        'cart_promotion_id' => null,
        'sale_round_off_amount' => 0.01,
    ];

    $this->saleData = new SaleData(...$this->saleDetails);

    $this->creditNoteService = new CreditNoteService();
});

test('getCreditNotes method calls getByIds method of CreditNoteQueries class', function (): void {
    $creditNoteA = CreditNote::factory()->make([
        'id' => 1,
        'counter_update_id' => 1,
        'sale_return_id' => 1,
        'cancel_layaway_sale_id' => 1,
        'member_id' => 1,
    ]);

    $creditNoteB = CreditNote::factory()->make([
        'id' => 2,
        'counter_update_id' => 2,
        'sale_return_id' => 2,
        'cancel_layaway_sale_id' => 2,
        'member_id' => 2,
    ]);

    $this->mock(CreditNoteQueries::class, function ($mock) use ($creditNoteB): void {
        $mock->shouldReceive('getByIds')
            ->once()
            ->andReturn(collect([$creditNoteB]));
    });

    $saleReturn = new SaleReturn();
    $saleReturn->creditNote = $creditNoteA;
    $response = $this->creditNoteService->getCreditNotes($saleReturn, $this->saleData, 1);

    $response->toArray();
    expect($response[0])
        ->toHaveKey('id', 1)
        ->toHaveKey('counter_update_id', 1)
        ->toHaveKey('sale_return_id', 1)
        ->toHaveKey('member_id', 1);

    expect($response[1])
        ->toHaveKey('id', 2)
        ->toHaveKey('counter_update_id', 2)
        ->toHaveKey('sale_return_id', 2)
        ->toHaveKey('member_id', 2);
});
