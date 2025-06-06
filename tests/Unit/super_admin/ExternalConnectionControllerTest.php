<?php

declare(strict_types=1);

use App\Domains\ExternalCompany\Jobs\ExternalCompanyUpdateJob;
use App\Domains\ExternalConnection\DataObjects\ExternalConnectionData;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalConnection\Services\ExternalConnectionService;
use App\Http\Controllers\SuperAdmin\ExternalConnectionController;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Queue;

test(
    'It calls the list query method of the External Connection queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $externalConnectionQueries = $this->mock(ExternalConnectionQueries::class, function ($mock) use (
            $requestParameter
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $externalConnectionController = new ExternalConnectionController($externalConnectionQueries);

        $response = $externalConnectionController->fetchExternalConnections(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls addNew method of the External Connection queries class', function (): void {
    $superAdmin = SuperAdmin::factory()->make([
        'id' => 1,
    ]);

    loginSuperAdmin($superAdmin);

    $externalConnectionRecord = new ExternalConnectionData('External Connection name', 'http://test.com');

    $externalConnectionQueries = $this->mock(ExternalConnectionQueries::class, function ($mock) use (
        $externalConnectionRecord,
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($externalConnectionRecord);
    });

    $this->mock(ExternalConnectionService::class, function ($mock): void {
        $mock->shouldReceive('sendNotification')
            ->once();
        $mock->shouldReceive('checkExternalConnectionAvailable')
            ->once();
    });

    $externalConnectionController = new ExternalConnectionController($externalConnectionQueries);
    $redirectResponse = $externalConnectionController->store($externalConnectionRecord);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('External Connection added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/external-connections', $redirectResponse->getTargetUrl());
});

test('It calls update method of the External Connection queries class', function (): void {
    $externalConnectionRecord = new ExternalConnectionData('External Connection name', 'http://test.com');

    $externalConnectionQueries = $this->mock(ExternalConnectionQueries::class, function ($mock) use (
        $externalConnectionRecord,
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($externalConnectionRecord, 1);
    });

    $externalConnectionController = new ExternalConnectionController($externalConnectionQueries);
    $redirectResponse = $externalConnectionController->update($externalConnectionRecord, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('External Connection updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/external-connections', $redirectResponse->getTargetUrl());
});

test('It calls rejectExternalConnection method of the External Connection service class', function (): void {
    $superAdmin = SuperAdmin::factory()->make([
        'id' => 1,
    ]);

    loginSuperAdmin($superAdmin);

    $this->mock(ExternalConnectionService::class, function ($mock): void {
        $mock->shouldReceive('rejectExternalConnection')
            ->once();
    });

    $filterData = [
        'name' => 'Test',
        'url' => 'http:://test.com',
        'id' => 'abc',
        'notification_id' => 1,
    ];

    $request = new Request($filterData);

    $externalConnectionController = new ExternalConnectionController(new ExternalConnectionQueries());
    $redirectResponse = $externalConnectionController->reject($request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'External Connection rejected successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('super-admin/external-connections', $redirectResponse->getTargetUrl());
});

test('It calls approveExternalConnection method of the External Connection service class', function (): void {
    $superAdmin = SuperAdmin::factory()->make([
        'id' => 1,
    ]);

    loginSuperAdmin($superAdmin);

    $this->mock(ExternalConnectionService::class, function ($mock): void {
        $mock->shouldReceive('approveExternalConnection')
            ->once();
    });

    $filterData = [
        'name' => 'Test',
        'url' => 'http:://test.com',
        'id' => 'abc',
        'notification_id' => 1,
    ];

    $request = new Request($filterData);

    $externalConnectionController = new ExternalConnectionController(new ExternalConnectionQueries());
    $redirectResponse = $externalConnectionController->approve($request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'External Connection approved successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('super-admin/external-connections', $redirectResponse->getTargetUrl());
});

test('syncData method work as expected', function (): void {
    Queue::fake();

    $externalConnectionQueries = $this->mock(ExternalConnectionQueries::class, function ($mock): void {
        $mock->shouldReceive('getById')
            ->once();
    });

    $this->mock(ExternalConnectionService::class, function ($mock): void {
        $mock->shouldReceive('syncDataExternalConnection')
            ->once();
    });

    $externalConnectionController = new ExternalConnectionController($externalConnectionQueries);
    $externalConnectionController->syncData(1);
    Queue::assertPushed(ExternalCompanyUpdateJob::class);
});
