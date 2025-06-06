<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\DataObjects\AddMemberDataForPromoterApi;
use App\Domains\Member\DataObjects\MemberListDataForPromoterApi;
use App\Domains\Member\DataObjects\PromoterMemberData;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Http\Controllers\Api\Promoter\MemberController;
use App\Models\Employee;
use App\Models\Promoter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

test('It calls the store method of the memberQueries class', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $memberRecords = new PromoterMemberData(
        first_name: 'test',
        mobile_number : '1234567890',
        email: 'test@gmail.com',
        date_of_birth: '2020-01-02',
    );

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('addNewMemberByPromoter')
            ->once();
    });
    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfStore')
            ->once();
    });
    $memberController = new MemberController();
    $response = $memberController->store($request, $memberRecords, 1);
    expect($response['member']);
});

test('it calls the getPaginatedList method and returns the paginated list of members', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $filteredData = [
        'page' => 1,
        'per_page' => 10,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'search_text' => 'test',
    ];

    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $memberListDataForPromoterApi = new MemberListDataForPromoterApi(...$filteredData);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });
    unset($filteredData['page']);
    $this->mock(MemberQueries::class, function ($mock) use ($filteredData, $companyId): void {
        $mock->shouldReceive('getPaginatedListForStoreManagerAndPromoterApp')
            ->once()
            ->with($filteredData, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $memberController = new MemberController();
    $response = $memberController->getPaginatedList($request, $memberListDataForPromoterApi);
    expect($response['members'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test('It calls the addMember method of the memberQueries class with pass card_number', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $memberRecords = new AddMemberDataForPromoterApi(
        type_id: Types::REGULAR->value,
        first_name: 'test',
        last_name: null,
        mobile_number : '1234567890',
        card_number : '67890',
        created_store_id: null,
        created_location_id: null,
    );

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('create')
            ->once();
    });
    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });
    $memberController = new MemberController();
    $response = $memberController->addMember($memberRecords, $request);
    expect($response['member']);
});

test('It calls the addMember method of the memberQueries class without card_number', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $memberRecords = new AddMemberDataForPromoterApi(
        type_id: Types::REGULAR->value,
        first_name: 'test',
        last_name: null,
        mobile_number : '1234567890',
        card_number : null,
        created_store_id: null,
        created_location_id: null,
    );

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('create')
            ->once();
        $mock->shouldReceive('generateUniqueCardNumber')
            ->once()
            ->andReturn('123456ABCD');
    });
    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });
    $memberController = new MemberController();
    $response = $memberController->addMember($memberRecords, $request);
    expect($response['member']);
});

test('it calls the getMemberPreference method and returns the preference color, size, category', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(MemberService::class, function ($mock): void {
        $mock->shouldReceive('getMemberPreferencesRecordsForApp')
            ->once()
            ->andReturn(['color', 'size', 'category']);
    });

    $memberController = new MemberController();
    $response = $memberController->getMemberPreference(1, $request);

    expect($response)
       ->toHaveKey('preference_color')
       ->toHaveKey('preference_size')
       ->toHaveKey('preference_category');
});
