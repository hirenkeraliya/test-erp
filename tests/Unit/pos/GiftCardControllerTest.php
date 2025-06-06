<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\GiftCard\DataObjects\PaginatedGiftCardListDataForPos;
use App\Domains\GiftCard\GiftCardQueries;
use App\Http\Controllers\Api\Pos\GiftCardController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\GiftCard;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('it calls the getPaginatedList method and returns the paginated list of gift cards', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $voucher = GiftCard::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $paginatedGiftCardListData = [
        'per_page' => 10,
        'page' => 1,
        'after_updated_at' => null,
    ];

    $paginatedGiftCardListDataForPos = new PaginatedGiftCardListDataForPos(...$paginatedGiftCardListData);

    $filterData = [
        'per_page' => $paginatedGiftCardListDataForPos->per_page,
        'after_updated_at' => $paginatedGiftCardListDataForPos->after_updated_at,
    ];

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(GiftCardQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedList')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $giftCardController = new GiftCardController();
    $response = $giftCardController->getPaginatedList($request, $paginatedGiftCardListDataForPos);

    expect($response['gift_cards']->resource);
});
