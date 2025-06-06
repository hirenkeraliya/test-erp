<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\DataObjects\PaginatedMemberListDataForPos;
use App\Domains\Member\MemberQueries;
use App\Http\Controllers\Api\Pos\MemberController;
use App\Models\Cashier;
use App\Models\Location;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls the getPaginatedListForPos method and returns paginated members', function (): void {
    $companyId = 1;

    Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
    ]);

    $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];

    $paginatedMemberListData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'search_text' => '',
        'after_updated_at' => null,
    ];

    $paginatedMemberListDataForPos = new PaginatedMemberListDataForPos(...$paginatedMemberListData);

    $filterData = [
        'per_page' => $paginatedMemberListDataForPos->per_page,
        'sort_by' => $paginatedMemberListDataForPos->sort_by,
        'search_text' => $paginatedMemberListDataForPos->search_text,
        'sort_direction' => $paginatedMemberListDataForPos->sort_direction,
        'after_updated_at' => $paginatedMemberListDataForPos->after_updated_at,
    ];

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(MemberQueries::class, function ($mock) use ($filterData, $companyId): void {
        $mock->shouldReceive('getPaginatedListForPos')
            ->once()
            ->with($filterData, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $memberController = new MemberController();
    $memberController->getPaginatedList($request, $paginatedMemberListDataForPos);
});

test('It returns the Genders list', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getGenders();
    expect($response['genders'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Male')
        ->toHaveKey('key', 'MALE');
});

test('It returns the races list', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getRaces();
    expect($response['races'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Malay')
        ->toHaveKey('key', 'MALAY');
});

test('It returns the titles list', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getTitles();
    expect($response['titles'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Datin')
        ->toHaveKey('key', 'DATIN');
});

test('It returns the types list', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getTypes();
    expect($response['types'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Vip')
        ->toHaveKey('key', 'VIP');
});

test('It returns the statuses list', function (): void {
    $memberController = new MemberController();
    $response = $memberController->getStatuses();
    expect($response['statuses'][0])
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Active')
        ->toHaveKey('key', 'ACTIVE');
});

test('It returns an error when the counter is not open when trying to add a member', function (): void {
    $cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'counter_update_id' => null,
    ]);

    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once();
    });

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $memberController = new MemberController();
    $memberController->store($request);
})->throws(HttpException::class, 'The counter has not been opened yet.');

test('It can store member', function (): void {
    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once();
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn(Location::factory()->make([
                'id' => 1,
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]));
    });

    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $request = $this->mock(Request::class, function ($mock) use ($cashier, $member): void {
        $mock->shouldReceive('validate')
            ->once();
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($cashier);
        $mock->shouldReceive('all')
            ->times(19)
            ->andReturn($member);
        $mock->shouldReceive('route');
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
        $mock->shouldReceive('loadRelationsForPos')
            ->once();
    });

    $memberController = new MemberController();
    $response = $memberController->store($request);
    expect($response)->toBeArray();
});

test('it calls the getMemberDetailsForPos method and returns member details', function (): void {
    $companyId = 1;

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
    ]);

    $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(MemberQueries::class, function ($mock) use ($companyId, $member): void {
        $mock->shouldReceive('getMemberDetailsForPos')
            ->once()
            ->with($member->id, $companyId)
            ->andReturn($member);
    });

    $memberController = new MemberController();
    $response = $memberController->getMember($request, $member->id);
    expect($response)->toBeArray();
    $this->assertEquals($member->id, $response['member']->id);
});

test('It calls the update method of the MemberQueries class', function (): void {
    $this->mock(CashierQueries::class, function ($mock): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once();
    });

    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $request = $this->mock(Request::class, function ($mock) use ($cashier, $member): void {
        $mock->shouldReceive('validate')
            ->once();
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($cashier);
        $mock->shouldReceive('all')
            ->times(20)
            ->andReturn($member);
        $mock->shouldReceive('route');
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('updatePosMember')
            ->once();
    });

    $memberController = new MemberController();
    $memberController->update($request, 1);
});

test('it calls the getEmployeeMembers method and returns paginated members', function (): void {
    $companyId = 1;

    Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
    ]);

    $cashier = makeCashierAndEmployeeForPosWithoutCounterUpdateId()['cashier'];

    $paginatedMemberListData = [
        'per_page' => 1,
        'page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'search_text' => '',
        'after_updated_at' => null,
    ];

    $paginatedMemberListDataForPos = new PaginatedMemberListDataForPos(...$paginatedMemberListData);

    $filterData = [
        'per_page' => $paginatedMemberListDataForPos->per_page,
        'sort_by' => $paginatedMemberListDataForPos->sort_by,
        'search_text' => $paginatedMemberListDataForPos->search_text,
        'sort_direction' => $paginatedMemberListDataForPos->sort_direction,
        'after_updated_at' => $paginatedMemberListDataForPos->after_updated_at,
    ];

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(MemberQueries::class, function ($mock) use ($filterData, $companyId): void {
        $mock->shouldReceive('fetchMembersListForPos')
            ->once()
            ->with($filterData, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $memberController = new MemberController();
    $memberController->getEmployeeMembers($request, $paginatedMemberListDataForPos);
});
