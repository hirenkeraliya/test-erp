<?php

declare(strict_types=1);

use App\Domains\ExternalCompany\Jobs\ExternalCompanyUpdateJob;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalConnection\Services\ExternalConnectionService;
use App\Domains\Notification\NotificationQueries;
use App\Models\ExternalConnection;
use App\Models\Product;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

beforeEach(function (): void {
    $this->externalConnectionService = new ExternalConnectionService();
});

test('sendNotification method works as expected.', function (): void {
    Http::fake();

    $externalConnection = ExternalConnection::factory()->make([
        'id' => 1,
        'create_by_super_admin_id' => 1,
        'approve_by_super_admin_id' => 1,
        'url' => 'http://example.com',
    ]);

    $this->externalConnectionService->sendNotification($externalConnection);
    Http::assertSent(
        fn ($request): bool => $request->url() === $externalConnection->url . '/api/external-connection/set-notification'
        && config('app.name') === $request['name']
        && config('app.url') === $request['url']
        && $request['id'] === $externalConnection->id
    );
});

test('rejectExternalConnection method works as expected.', function (): void {
    Http::fake();

    $superAdmin = SuperAdmin::factory()->make([
        'id' => 1,
    ]);

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsReadById')
            ->once();
    });

    $this->externalConnectionService->rejectExternalConnection($superAdmin, 'http://example.com', 1, 1);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://example.com/api/external-connection/reject');
});

test('approveExternalConnection method works as expected.', function (): void {
    Queue::fake();

    Http::fake([
        'http://example.com/api/external-connection/approve?id=1' => Http::response([
            'name' => 'Example',
            'url' => 'http://example.com',
            'token' => 'abc123',
        ], 200),
    ]);

    Http::fake([
        'http://example.com/api/external-connection/sync-data' => Http::response([
            'token' => 'abc123',
        ], 200),
    ]);

    $superAdmin = SuperAdmin::factory()->make([
        'id' => 1,
    ]);

    $externalConnection = ExternalConnection::factory()->make([
        'id' => 1,
        'create_by_super_admin_id' => 1,
        'approve_by_super_admin_id' => 1,
        'url' => 'http://example.com',
    ]);

    $this->mock(NotificationQueries::class, function ($mock): void {
        $mock->shouldReceive('markAsReadById')
            ->once();
    });

    $this->mock(ExternalConnectionQueries::class, function ($mock) use ($externalConnection): void {
        $mock->shouldReceive('addNewWithApprove')
            ->once()
            ->andReturn($externalConnection);
    });

    $this->externalConnectionService->approveExternalConnection($superAdmin, 'http://example.com', 1, 1);

    Http::assertSent(
        fn ($request): bool => $request->url() === 'http://example.com/api/external-connection/approve?id=1'
    );

    Queue::assertPushed(ExternalCompanyUpdateJob::class);
});

test('getExternalInventories method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getExternalInventories($filterData);
    expect($response)->toBeArray();
});

test('exportExternalInventories method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
        'filename' => 'demo.csv',
    ];

    $response = $this->externalConnectionService->exportExternalInventories($filterData);
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('getFilteredExternalInventoryProducts method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventoryProducts($filterData);
    expect($response)->toBeArray();
});

test('getFilteredExternalInventoryCategories method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventoryCategories($filterData);
    expect($response)->toBeArray();
});

test('getFilteredExternalInventoryBrands method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventoryBrands($filterData);
    expect($response)->toBeArray();
});

test('getFilteredExternalInventorySizes method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventorySizes($filterData);
    expect($response)->toBeArray();
});

test('getFilteredExternalInventoryColors method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventoryColors($filterData);
    expect($response)->toBeArray();
});

test('getFilteredExternalInventoryDepartments method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventoryDepartments($filterData);
    expect($response)->toBeArray();
});

test('getFilteredExternalInventoryArticleNumbers method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventoryArticleNumbers($filterData);
    expect($response)->toBeArray();
});

test('getFilteredExternalInventoryTags method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventoryTags($filterData);
    expect($response)->toBeArray();
});

test('getFilteredExternalInventoryStyles method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getFilteredExternalInventoryStyles($filterData);
    expect($response)->toBeArray();
});

test('getStoresWarehousesAndRegions method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getStoresWarehousesAndRegions($filterData);
    expect($response)->toBeArray();
});

test('getStoresAndRegions method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getStoresAndRegions($filterData);
    expect($response)->toBeArray();
});

test('getWarehousesAndRegions method works as expected.', function (): void {
    Http::fake([
        '*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $filterData = [
        'url' => 'http://example.com',
    ];

    $response = $this->externalConnectionService->getWarehousesAndRegions($filterData);
    expect($response)->toBeArray();
});

test('sendProductDataExternalConnection method works as expected.', function (): void {
    Http::fake();

    $externalConnection = ExternalConnection::factory()->make([
        'id' => 1,
        'create_by_super_admin_id' => 1,
        'approve_by_super_admin_id' => 1,
        'url' => 'http://example.com',
        'token' => 'abc123',
    ]);
    $companyId = 1;
    $externalCompanyId = 1;

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'code' => '1546',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'sub_department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'article_number' => '123456',
        'is_non_inventory' => false,
        'status' => true,
    ]);

    $this->externalConnectionService->sendProductDataExternalConnection(
        $externalConnection,
        $product,
        $externalCompanyId,
        $companyId,
    );

    Http::assertSentCount(1);
});
