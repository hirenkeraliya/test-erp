<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\MemberGroup\DataObjects\PaginatedMemberGroupListDataForPos;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Http\Controllers\Api\Pos\MemberGroupController;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'It calls the list query method of the member group queries class and returns proper response',
    function (): void {
        $paginatedMemberGroupListData = [
            'per_page' => 10,
            'page' => 1,
            'sort_by' => 'id',
            'search_text' => '',
            'sort_direction' => 'asc',
            'after_updated_at' => null,
        ];

        $paginatedMemberGroupListDataForPos = new PaginatedMemberGroupListDataForPos(...$paginatedMemberGroupListData);

        $requestParameter = [
            'search_text' => $paginatedMemberGroupListDataForPos->search_text,
            'sort_by' => $paginatedMemberGroupListDataForPos->sort_by,
            'sort_direction' => $paginatedMemberGroupListDataForPos->sort_direction,
            'per_page' => $paginatedMemberGroupListDataForPos->per_page,
            'after_updated_at' => $paginatedMemberGroupListDataForPos->after_updated_at,
        ];

        $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];

        $request = new Request($requestParameter);

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $memberGroupQueries = $this->mock(MemberGroupQueries::class, function ($mock): void {
            setCompanyIdInSession(1);

            $mock->shouldReceive('listQuery')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $memberGroupController = new MemberGroupController($memberGroupQueries);

        $response = $memberGroupController->getPaginateMemberGroup($request, $paginatedMemberGroupListDataForPos);

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);
