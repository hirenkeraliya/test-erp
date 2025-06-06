<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Store\DataObjects\MemberAppStoreListData;
use App\Http\Controllers\Api\Member\StoreController;
use App\Models\Company;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getPaginatedStoreList method and returns list of stores', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'created_location_id' => $location->id,
        'company_id' => $company->id,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Member => $member);

    $filterData = [
        'search_text' => '',
        'per_page' => 10,
        'page' => 1,
        'sort_by' => '',
        'sort_direction' => '',
    ];

    $memberAppStoreListData = new MemberAppStoreListData(...$filterData);
    $filterData['type_id'] = LocationTypes::STORE->value;

    $this->mock(LocationQueries::class, function ($mock) use ($filterData, $company): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($filterData, $company->id)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $storeController = new StoreController();
    $response = $storeController->getPaginatedStoreList($memberAppStoreListData, $request);

    expect($response['stores']->resource);
});
