<?php

declare(strict_types=1);

use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotification\DataObjects\AutomatedNotificationData;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTimeframeTypes;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\EmailRecipient\EmailRecipientQueries;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Admin\AutomatedNotificationController;
use App\Models\Admin;
use App\Models\AutomatedNotification;
use App\Models\ImportRecord;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the AutomatedNotificationQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);

        $response = $automatedNotificationController->fetchAutomatedNotifications(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the add Automated Notification method of automatedNotificationQueries class', function (): void {
    $automatedNotificationData = new AutomatedNotificationData(
        'abc',
        '',
        AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        true,
        AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        [],
        [],
        20,
    );
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
        $automatedNotificationData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($automatedNotificationData, $companyId);
    });

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);
    $redirectResponse = $automatedNotificationController->store($automatedNotificationData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'Automated Notification added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/automated-notifications', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the AutomatedNotificationQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        ];

        $automatedNotification = AutomatedNotification::factory()->make([
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
            'company_id' => $companyId,
        ]);

        $automatedNotification->importRecord = new ImportRecord([
            'status' => 3,
        ]);

        $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
            $companyId,
            $automatedNotification
        ): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(new Collection());

            $mock->shouldReceive('getByIdWithRelations')
                ->once()
                ->with(1, $companyId)
                ->andReturn($automatedNotification);
        });

        $this->mock(EmailRecipientQueries::class, function ($mock): void {
            $mock->shouldReceive('getAutomatedEmailReceivers')
                ->once()
                ->andReturn(new EloquentCollection());
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getWithBasicColumns')
                ->once()
                ->andReturn(collect([]));
        });

        $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);
        $response = $automatedNotificationController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'automatedNotification.data',
            fn (Assert $automatedNotification): Assert => $automatedNotification
                ->where('type_id', AutomatedNotificationTypes::LOW_STOCK_COMPANY->value)
                ->where('timeframe_type_id', AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value)
                ->etc()
        )
        ->has('automatedNotificationTypes')
        ->has('automatedNotificationTimeframeStaticDetails')
        ->has('automatedNotificationTimeframeTypes')
        );
    }
);

test('It calls the update automated notification method of AutomatedNotificationData class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $automatedNotificationData = new AutomatedNotificationData(
        'abcd',
        '',
        AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        true,
        AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
        [],
        [],
        30
    );

    $automatedNotification = AutomatedNotification::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_WEEK->value,
    ]);
    $automatedNotification->importRecord = new ImportRecord([
        'status' => 3,
    ]);

    $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
        $automatedNotificationData,
        $companyId,
        $automatedNotification,
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($automatedNotificationData, $automatedNotification, $companyId);
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($automatedNotification);
    });

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Admin => $admin);

    $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);
    $redirectResponse = $automatedNotificationController->update($automatedNotificationData, $request, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'Automated Notification updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/automated-notifications', $redirectResponse->getTargetUrl());
});

test('It calls the exportautomatedNotifications method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getautomatedNotificationExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new AutomatedNotification()));
    });

    $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);

    $response = $automatedNotificationController->exportautomatedNotifications(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the removeSelectedStores method and remove automated notifications attached stores', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $automatedNotification = AutomatedNotification::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
    ]);

    $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
        $automatedNotification,
        $companyId
    ): void {
        $mock->shouldReceive('removeSelectedStores')
            ->with($automatedNotification->id, $companyId)
            ->once();
    });

    $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);

    $automatedNotificationController->removeSelectedStores($automatedNotification->id);
});

test('It calls the exportAutomatedNotificationStores method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $automatedNotification = AutomatedNotification::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
    ]);

    $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
        $automatedNotification,
        $companyId
    ): void {
        $mock->shouldReceive('getByIdWithAutomatedNotificationStores')
            ->once()
            ->with($automatedNotification->id, $companyId)
            ->andReturn(new AutomatedNotification());
    });

    $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);

    $response = $automatedNotificationController->exportAutomatedNotificationStores(
        $automatedNotification->id,
        'filename.csv',
        new Request([
            'id' => $automatedNotification->id,
        ])
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the removeSelectedProducts method and remove automated notifications attached products',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $automatedNotification = AutomatedNotification::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
        ]);

        $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
            $automatedNotification,
            $companyId
        ): void {
            $mock->shouldReceive('removeSelectedProducts')
                ->with($automatedNotification->id, $companyId)
                ->once();
        });

        $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);

        $automatedNotificationController->removeSelectedProducts($automatedNotification->id);
    }
);

test('It calls the exportAutomatedNotificationProducts method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $automatedNotification = AutomatedNotification::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
        'timeframe_type_id' => AutomatedNotificationTimeframeTypes::LIMIT_BY_DAY_OF_THE_MONTH->value,
    ]);

    $automatedNotificationQueries = $this->mock(AutomatedNotificationQueries::class, function ($mock) use (
        $automatedNotification,
        $companyId
    ): void {
        $mock->shouldReceive('getByIdWithAutomatedNotificationProducts')
            ->once()
            ->with($automatedNotification->id, $companyId)
            ->andReturn(new AutomatedNotification());
    });

    $automatedNotificationController = new AutomatedNotificationController($automatedNotificationQueries);

    $response = $automatedNotificationController->exportAutomatedNotificationProducts(
        $automatedNotification->id,
        'filename.csv',
        new Request([
            'id' => $automatedNotification->id,
        ])
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
