<?php

declare(strict_types=1);

use App\Domains\Member\MemberQueries;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Http\Controllers\Api\SaleChannel\MemberGroup\MemberGroupController;
use Illuminate\Pagination\LengthAwarePaginator;

it('returns a list of member ids by groupId.', function (): void {
    $filterData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'member_group_id' => 1,
    ];

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('getMemberIdsForSalesChannel')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 10));
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel($filterData);

    $memberGroupQueries = resolve(MemberGroupQueries::class);

    $memberGroupController = new MemberGroupController($memberGroupQueries);
    $response = $memberGroupController->getMemberIds($request);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), $response['member_ids']);
});
