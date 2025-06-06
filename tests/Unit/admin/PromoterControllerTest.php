<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\DataObjects\ChangePasswordData;
use App\Domains\Promoter\DataObjects\PromoterData;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommissionRegeneration\Jobs\PromoterCommissionRegenerationJob;
use App\Domains\PromoterCommissionRegeneration\PromoterCommissionRegenerationQueries;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Admin\PromoterController;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\PromoterCommissionRegeneration;
use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the promoter queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => 1,
        'location_ids' => 'null',
        'group_ids' => null,
        'status' => null,
    ];

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $promoterController = new PromoterController($promoterQueries);

    $response = $promoterController->fetchPromoters(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the promoter queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promoterRecords = [
        'employee_id' => 1,
        'username' => 'ABC',
        'password' => '123456',
        'monthly_sales_target' => 100,
        'code' => 'test',
        'location_ids' => [1, 2],
        'default_commission_amount_percentage' => 0.0,
        'monthly_target_commission_percentage' => 0.0,
        'group_id' => null,
    ];

    $promoterData = new PromoterData(...$promoterRecords);

    $request = new Request();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $this->mock(LocationQueries::class, function ($mock) use ($promoterData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $promoterData->location_ids)
            ->andReturn(true);
    });

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use ($promoterData, $admin): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($promoterData, $admin);
        $mock->shouldReceive('doesCodeExist')
            ->once();
    });

    $promoterController = new PromoterController($promoterQueries);
    $redirectResponse = $promoterController->store($promoterData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Promoter added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/promoters', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the promoter queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'employee_id' => 1,
            'monthly_sales_target' => 1,
        ];

        $promoterData = new Promoter($requestParameter);
        $promoterData->stores = [1, 2];

        $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
            $promoterData,
            $companyId
        ): void {
            $mock->shouldReceive('getByIdWithEmployeeAndLocations')
                ->once()
                ->with(1, $companyId)
                ->andReturn($promoterData);
        });

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getFormattedEmployeesOf')
                ->once()
                ->with(1)
                ->andReturn(new Collection([]));
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
                ->once()
                ->with(1)
                ->andReturn(new Collection([]));
        });

        $this->mock(PromoterGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getPromoterGroupByCompanyId')
                ->once()
                ->andReturn(new EloquentCollection([]));
        });

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
                ->once()
                ->with(1)
                ->andReturn(new Company());
        });

        $promoterController = new PromoterController($promoterQueries);
        $response = $promoterController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has('promoter', fn (Assert $promoter): Assert => $promoter
            ->where('employee_id', 1)
            ->where('monthly_sales_target', 1)
            ->has('stores', 2))
        );
    }
);

test('It calls the update method of promoter queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $promoterData = new PromoterData(1, 'ABC', '12345', 100, 'test', [1, 2], 0.0, 0.0);

    $this->mock(LocationQueries::class, function ($mock) use ($promoterData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $promoterData->location_ids)
            ->andReturn(true);
    });

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use ($promoterData, $companyId): void {
        $mock->shouldReceive('doesCodeExist')
            ->once();
        $mock->shouldReceive('update')
            ->once()
            ->with($promoterData, 1, $companyId);
    });

    $promoterController = new PromoterController($promoterQueries);
    $redirectResponse = $promoterController->update($promoterData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Promoter updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/promoters', $redirectResponse->getTargetUrl());
});

test('An exception is thrown if store_id does not match the company_id', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promoterRecords = [
        'employee_id' => 1,
        'username' => 'ABCD',
        'password' => '123456',
        'monthly_sales_target' => 100,
        'code' => 'test',
        'location_ids' => [],
        'default_commission_amount_percentage' => 0.0,
        'monthly_target_commission_percentage' => 0.0,
    ];

    $promoterData = new PromoterData(...$promoterRecords);

    $request = new Request();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): Admin => $admin);

    $this->mock(LocationQueries::class, function ($mock) use ($promoterData, $companyId): void {
        $mock->shouldReceive('doAllStoresExist')
            ->once()
            ->with($companyId, $promoterData->location_ids)
            ->andReturn(false);
    });

    $promoterQueries = resolve(PromoterQueries::class);

    $promoterController = new PromoterController($promoterQueries);
    $promoterController->store($promoterData, $request);
})->throws(RedirectWithErrorException::class);

test('It calls the exportPromoters method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => 'null',
        'group_ids' => null,
        'status' => null,
    ];

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getPromotersExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Promoter()));
    });

    $promoterController = new PromoterController($promoterQueries);

    $response = $promoterController->exportPromoters('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the exportExistingPromoters method and returns a proper response', function (): void {
    $companyId = 1;

    $company = Company::factory()->make([
        'id' => $companyId,
        'name' => 'ABCD',
        'commission_type_id' => CommissionTypes::BY_PROMOTER,
        'default_country_id' => 1,
    ]);

    setCompanyIdInSession($companyId);

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('getPromoterForBulkUpdate')
            ->once()
            ->with($companyId)
            ->andReturn(collect([]));
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
            ->once()
            ->with($company->id)
            ->andReturn($company);
    });

    $promoterController = new PromoterController($promoterQueries);

    $response = $promoterController->exportExistingPromoters();

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('An exception is thrown if username and password not pass.', function (): void {
    setCompanyIdInSession();
    $promoterController = new PromoterController(new PromoterQueries());
    $request = new Request([
        'username' => '',
        'password' => '',
    ]);
    $promoterController->regenerateCommission($request);
})->throws(ValidationException::class);

test('An exception is thrown if username not match with over data base.', function (): void {
    Bus::fake();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $admin->employee = new Employee([
        'company_id' => 1,
    ]);

    loginAdmin($admin);

    $promoterController = new PromoterController(new PromoterQueries());
    SuperAdmin::factory()->make([
        'username' => 'super_admin',
    ]);

    $request = new Request([
        'username' => 'admin',
        'password' => '123456',
        'reason' => 'abc',
    ]);

    $this->mock(SuperAdminQueries::class, function ($mock) use ($request): void {
        $mock->shouldReceive('getByUsername')
            ->with($request->username)
            ->once()
            ->andReturn(null);
    });

    $this->mock(PromoterCommissionRegenerationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
        $mock->shouldReceive('entryExistsForPeriod')
            ->times(1)
            ->andReturn(false);
    });

    $response = $promoterController->regenerateCommission($request);

    Bus::assertNotDispatched(PromoterCommissionRegenerationJob::class);

    $this->assertEquals(302, $response->getStatusCode());
});

test('An exception is thrown if promoter commission regeneration already running.', function (): void {
    Bus::fake();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $admin->employee = new Employee([
        'company_id' => 1,
    ]);

    loginAdmin($admin);

    $promoterController = new PromoterController(new PromoterQueries());
    $superAdmin = SuperAdmin::factory()->make([
        'username' => 'super_admin',
    ]);

    $request = new Request([
        'username' => 'admin',
        'password' => '123456',
        'reason' => 'abc',
    ]);

    $this->mock(SuperAdminQueries::class, function ($mock) use ($request, $superAdmin): void {
        $mock->shouldReceive('getByUsername')
            ->with($request->username)
            ->times(0)
            ->andReturn($superAdmin);
    });

    $this->mock(PromoterCommissionRegenerationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
        $mock->shouldReceive('entryExistsForPeriod')
            ->times(1)
            ->andReturn(true);
    });

    $response = $promoterController->regenerateCommission($request);

    Bus::assertNotDispatched(PromoterCommissionRegenerationJob::class);

    $this->assertEquals(302, $response->getStatusCode());

    $this->assertEquals(
        'Your request for Commission Regeneration is currently being processed in the background. Please wait for the process to complete.',
        $response->getSession()->all()['error']
    );
});

test('It calls the regenerateCommission and returns a proper response.', function (): void {
    Bus::fake();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $admin->employee = new Employee([
        'company_id' => 1,
    ]);

    loginAdmin($admin);

    $promoterController = new PromoterController(new PromoterQueries());
    $superAdmin = SuperAdmin::factory()->make([
        'username' => 'super_admin',
    ]);

    $request = new Request([
        'username' => 'admin',
        'password' => '123456',
        'reason' => 'abc',
    ]);

    $promoterCommissionRegeneration = PromoterCommissionRegeneration::factory()->make([
        'id' => 1,
        'admin_id' => 1,
        'super_admin_id' => 1,
        'started_at' => null,
        'completed_at' => null,
    ]);

    $this->mock(SuperAdminQueries::class, function ($mock) use ($request, $superAdmin): void {
        $mock->shouldReceive('getByUsername')
            ->with($request->username)
            ->once()
            ->andReturn($superAdmin);
    });

    $this->mock(PromoterCommissionRegenerationQueries::class, function ($mock) use (
        $promoterCommissionRegeneration
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn($promoterCommissionRegeneration);
        $mock->shouldReceive('entryExistsForPeriod')
            ->times(1)
            ->andReturn(false);
    });

    $response = $promoterController->regenerateCommission($request);

    Bus::assertDispatched(PromoterCommissionRegenerationJob::class);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals(
        'Your Commission Regenerate request has been sent successfully. The Regeneration process will now commence in the background. Kindly allow some time for the process to complete.',
        $response->getSession()->all()['success']
    );
});

test('An exception is thrown if password not match with over data base.', function (): void {
    Bus::fake();

    $admin = Admin::factory()->make([
        'employee_id' => 1,
    ]);

    $admin->employee = new Employee([
        'company_id' => 1,
    ]);

    loginAdmin($admin);

    $promoterController = new PromoterController(new PromoterQueries());
    $superAdmin = SuperAdmin::factory()->make([
        'username' => 'super_admin',
    ]);

    $request = new Request([
        'username' => 'admin',
        'password' => '122222',
        'reason' => 'abc',
    ]);

    $this->mock(SuperAdminQueries::class, function ($mock) use ($request, $superAdmin): void {
        $mock->shouldReceive('getByUsername')
            ->with($request->username)
            ->once()
            ->andReturn($superAdmin);
    });

    $this->mock(PromoterCommissionRegenerationQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0);
        $mock->shouldReceive('entryExistsForPeriod')
            ->times(1)
            ->andReturn(false);
    });

    $response = $promoterController->regenerateCommission($request);

    Bus::assertNotDispatched(PromoterCommissionRegenerationJob::class);

    $this->assertEquals(302, $response->getStatusCode());
});

test(
    'It calls the getPromoterByStores method of the promoter queries class and returns proper response',
    function (): void {
        setCompanyIdInSession();

        $requestParameter = [
            'location_ids' => [1],
        ];

        $promoterQueries = $this->mock(PromoterQueries::class, function ($mock): void {
            $mock->shouldReceive('getPromoterByLocations')
                ->once()
                ->with([1])
                ->andReturn(collect([]));
        });

        $promoterController = new PromoterController($promoterQueries);

        $response = $promoterController->getByLocationIds(new Request($requestParameter));
        expect($response['promoters'])->toBeInstanceOf(AnonymousResourceCollection::class);
    }
);

test('It calls change password method of the promoter queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $promoter = Promoter::factory()->make([
        'employee_id' => 1,
    ]);

    $changePasswordData = new ChangePasswordData('111111');

    $promoterQueries = $this->mock(PromoterQueries::class, function ($mock) use (
        $companyId,
        $promoter,
        $changePasswordData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn($promoter);
        $mock->shouldReceive('changePassword')
            ->once()
            ->with($promoter, $changePasswordData);
    });

    $promoterController = new PromoterController($promoterQueries);

    $redirectResponse = $promoterController->updatePassword($changePasswordData, 1);
    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/promoters', $redirectResponse->getTargetUrl());
});
