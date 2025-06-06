<?php

declare(strict_types=1);

use App\Domains\Membership\DataObjects\MembershipData;
use App\Domains\Membership\MembershipQueries;
use App\Http\Controllers\Admin\MembershipController;
use App\Models\Admin;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the membership queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $membershipQueries = $this->mock(MembershipQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $membershipController = new MembershipController($membershipQueries);

    $response = $membershipController->fetchMemberships(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the addNew method of membership queries class', function (): void {
    $membershipData = new MembershipData('Membership', 10.10, 10, 10, 10);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $membershipQueries = $this->mock(MembershipQueries::class, function ($mock) use (
        $membershipData,
        $companyId,
        $admin
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($membershipData, $companyId, $admin);
    });

    $membershipController = new MembershipController($membershipQueries);
    $redirectResponse = $membershipController->store($membershipData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Membership added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/memberships', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the membership queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'company_id' => $companyId,
            'name' => 'STUV',
        ];

        $membershipQueries = $this->mock(MembershipQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new Membership($requestParameter));
        });

        $membershipController = new MembershipController($membershipQueries);
        $response = $membershipController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'membership',
            fn (Assert $membership): Assert => $membership->where('name', 'STUV')->where('company_id', $companyId)
        )
        );
    }
);

test('It calls the update method of membership queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $membershipData = new MembershipData('Membership', 10.10, 10, 10, 10);

    $membershipQueries = $this->mock(MembershipQueries::class, function ($mock) use (
        $membershipData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($membershipData, 1, $companyId);
    });

    $membershipController = new MembershipController($membershipQueries);
    $redirectResponse = $membershipController->update($membershipData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Membership updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/memberships', $redirectResponse->getTargetUrl());
});

test('It calls the exportMemberships method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $membershipQueries = $this->mock(MembershipQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getMembershipsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Membership()));
    });

    $membershipController = new MembershipController($membershipQueries);

    $response = $membershipController->exportMemberships('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
