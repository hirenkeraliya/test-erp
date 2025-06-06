<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\ExternalProduct\ExternalProductQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\Api\ExternalConnection\ExternalConnectionController;
use App\Models\Admin;
use App\Models\ExternalConnection;
use App\Models\Notification;
use App\Models\Product;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;

test('setNotification method calls the addNewWithNullValue method of notificationQueries calls', function (): void {
    $filterData = [
        'name' => 'Test',
        'url' => 'http:://test.com',
        'id' => 'abc',
    ];

    $request = new Request($filterData);

    $superAdmin = SuperAdmin::factory()->make([
        'id' => 1,
    ]);

    $this->mock(SuperAdminQueries::class, function ($mock) use ($superAdmin): void {
        $mock->shouldReceive('getAll')
            ->once()
            ->andReturn(collect([$superAdmin]));
    });

    $notification = Notification::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'from_user_id' => 1,
        'to_user_id' => 1,
    ]);

    $this->mock(NotificationQueries::class, function ($mock) use ($notification): void {
        $mock->shouldReceive('addNewWithNullValue')
            ->once()
            ->andReturn($notification);

        $mock->shouldReceive('updateMessage')
            ->once();
    });

    $externalConnectionController = new ExternalConnectionController(new ExternalConnectionQueries());
    $externalConnectionController->setNotification($request);
});

test('reject method calls the reject method of notificationQueries calls', function (): void {
    $filterData = [
        'name' => 'Test',
        'url' => 'http:://test.com',
        'id' => 'abc',
    ];

    $request = new Request($filterData);

    $externalConnectionQueries = $this->mock(ExternalConnectionQueries::class, function ($mock): void {
        $mock->shouldReceive('reject')
            ->once();
    });

    $externalConnectionController = new ExternalConnectionController($externalConnectionQueries);
    $externalConnectionController->reject($request);
});

test('approve method calls the approve method of notificationQueries calls', function (): void {
    $filterData = [
        'name' => 'Test',
        'url' => 'http:://test.com',
        'id' => 'abc',
    ];

    $request = new Request($filterData);

    $externalConnection = ExternalConnection::factory()->make([
        'create_by_super_admin_id' => 1,
        'approve_by_super_admin_id' => 1,
    ]);

    $externalConnectionQueries = $this->mock(ExternalConnectionQueries::class, function ($mock) use (
        $externalConnection
    ): void {
        $mock->shouldReceive('approve')
            ->once()
            ->andReturn($externalConnection);
    });

    $externalConnectionController = new ExternalConnectionController($externalConnectionQueries);
    $response = $externalConnectionController->approve($request);

    expect($response)
        ->toHaveKey('name', config('app.name'))
        ->toHaveKey('url', config('app.url'))
        ->toHaveKey('token', $externalConnection->token);
});

test(
    'sendExternalProductData method calls than create external product and notificationQueries calls',
    function (): void {
        $companyId = 1;
        $externalCompanyId = 1;

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'upc' => '1546',
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

        $externalConnection = ExternalConnection::factory()->make([
            'id' => 1,
            'token' => '123456',
        ]);

        $admin = Admin::factory()->make([
            'employee_id' => 1,
        ]);

        $requestData = [
            'token' => $externalConnection->token,
            'product' => json_encode($product->toArray()),
            'receiver_company_id' => $companyId,
            'sender_company_id' => $externalCompanyId,
        ];

        $request = new Request($requestData);

        $externalConnectionQueries = $this->mock(ExternalConnectionQueries::class, function ($mock) use (
            $request,
        ): void {
            $mock->shouldReceive('getByToken')
                ->once()
                ->with($request->token);
        });

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByUpc')
            ->once()
            ->andReturn(false);
        });

        $this->mock(ExternalProductQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn(true);
        });

        $this->mock(AdminQueries::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('getAdminListByCompanyId')
                ->once()
                ->andReturn(collect([$admin]));
        });

        $this->mock(NotificationQueries::class, function ($mock): void {
            $mock->shouldReceive('addNewWithNullValue')
                ->once();
        });

        $externalConnectionController = new ExternalConnectionController($externalConnectionQueries);
        $externalConnectionController->sendExternalProductData($request);
    }
);
