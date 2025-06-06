<?php

declare(strict_types=1);

use App\Domains\SaleChannel\DataObjects\SaleChannelData;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Http\Controllers\SuperAdmin\SaleChannelController;
use App\Models\SaleChannel;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('It calls the sale channel queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $SaleChannelQueries = $this->mock(SaleChannelQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $saleChannelController = new SaleChannelController($SaleChannelQueries);

    $response = $saleChannelController->fetchSalesChannel(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the Add New method of sale channel queries class', function (): void {
    $saleChannelData = new SaleChannelData(
        'ABC',
        'XYZ',
        1,
        1,
        1,
        1,
        'http://example.com',
        'secret123',
        [1],
        [
            'webhook_url_type_id' => 1,
            'url' => 'test',
        ],
        true,
        true,
        '{"decimal_place":0.05,"value":"1"}'
    );

    $SaleChannelQueries = $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannelData): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($saleChannelData);
    });

    $superAdminController = new SaleChannelController($SaleChannelQueries);
    $redirectResponse = $superAdminController->store($saleChannelData);

    expect($redirectResponse)->toHaveKey('token');
});

test('It calls the update sale channel method of the sale channel queries class', function (): void {
    $saleChannelData = new SaleChannelData(
        'ABC',
        'XYZ',
        1,
        1,
        1,
        1,
        'http://example.com',
        'secret123',
        [1],
        [
            'webhook_url_type_id' => 1,
            'url' => 'test',
        ],
        true,
        true,
        '{"decimal_place":0.05,"value":"1"}'
    );

    $saleChannel = SaleChannel::factory()->make([
        'company_id' => 1,
        'default_location_id' => 1,
    ]);

    $SaleChannelQueries = $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannel): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($saleChannel);
        $mock->shouldReceive('update')
            ->once();
    });

    $superAdminController = new SaleChannelController($SaleChannelQueries);
    $redirectResponse = $superAdminController->update($saleChannelData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Sales Channel updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/sales-channel', $redirectResponse->getTargetUrl());
});

test('It calls the refreshToken method of the saleChannelQueries class', function (): void {
    $SaleChannelQueries = $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('refreshToken')
            ->once()
            ->andReturn('test');
    });

    $superAdmin = SuperAdmin::factory()->make();

    $this->mock(SuperAdminQueries::class, function ($mock) use ($superAdmin): void {
        $mock->shouldReceive('getByUsername')
            ->once()
            ->andReturn($superAdmin);
    });

    $superAdminController = new SaleChannelController($SaleChannelQueries);
    $redirectResponse = $superAdminController->refreshAccessToken(new Request([
        'username' => $superAdmin->username,
        'password' => '123456',
    ]), 1);

    expect($redirectResponse)->toHaveKey('access_token');
});

test(
    'It does not call the refreshToken method of the saleChannelQueries class if the super admin is not verified',
    function (): void {
        $SaleChannelQueries = $this->mock(SaleChannelQueries::class, function ($mock): void {
            $mock->shouldNotReceive('refreshToken');
        });

        $this->mock(SuperAdminQueries::class, function ($mock): void {
            $mock->shouldReceive('getByUsername')
                ->once();
        });

        $superAdminController = new SaleChannelController($SaleChannelQueries);
        $redirectResponse = $superAdminController->refreshAccessToken(new Request([
            'username' => 'username',
            'password' => '123456',
        ]), 1);

        expect($redirectResponse)->toHaveKey('access_token');
    }
)->throws(HttpException::class, 'Username or password is incorrect.');

test('It calls the setStatus method and returns a proper response', function (): void {
    $saleChannelQueries = $this->mock(SaleChannelQueries::class, function ($mock): void {
        $mock->shouldReceive('updateStatus')
            ->once();
    });

    $saleChannelController = new SaleChannelController($saleChannelQueries);

    $redirectResponse = $saleChannelController->setStatus(1, true);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Status changed successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/sales-channels', $redirectResponse->getTargetUrl());
});
