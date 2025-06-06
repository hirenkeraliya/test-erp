<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\StoreManagerAuthorizationCode\Enum\StoreManagerAuthorizationCodeStatuses;
use App\Domains\StoreManagerAuthorizationCode\StoreManagerAuthorizationCodeQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\StoreManagerAuthorizationCodeUsage\StoreManagerAuthorizationCodeUsageQueries;
use App\Models\StoreManagerAuthorizationCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->storeManagerAuthorizationCodeUsageService = new StoreManagerAuthorizationCodeUsageService();
});

test('saveSaleDetails method returns null when code is null', function (): void {
    $response = $this->storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
        1,
        1,
        ModelMapping::SALE->name,
        null
    );

    $this->assertNull($response);
});

test('saveSaleDetails method call getByCode method of StoreManagerAuthorizationCodeQueries class', function (): void {
    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn(null);
    });

    $response = $this->storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
        1,
        1,
        ModelMapping::SALE->name,
        '123456'
    );

    $this->assertNull($response);
});

test('saveSaleDetails method call addNew method of StoreManagerAuthorizationCodeUsageQueries class', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $this->mock(StoreManagerAuthorizationCodeUsageQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $response = $this->storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
        1,
        1,
        ModelMapping::SALE->name,
        '123456'
    );

    $this->assertNull($response);
});

test(
    'checkStoreManagerAuthorizationCode method return null when store_manager_authorization_code not set',
    function (): void {
        $response = $this->storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            collect([]),
            1,
            null,
            null
        );

        $this->assertNull($response);
    }
);

test(
    'checkStoreManagerAuthorizationCode method throw exception when code not match in database',
    function (): void {
        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn(null);
        });

        $this->storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            collect([]),
            1,
            '1234',
            now()->format('Y-m-d H:i:s')
        );
    }
)->throws(HttpException::class, 'Specified Store manager authorization code does not correspond with our records.');

test(
    'checkStoreManagerAuthorizationCode method throw exception when code not match with store manager',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 2,
            'code' => '1234',
            'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });
        $this->storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            collect([]),
            1,
            '1234',
            now()->format('Y-m-d H:i:s')
        );
    }
)->throws(HttpException::class, 'Specified Store manager authorization code and store manager not match.');

test('checkStoreManagerAuthorizationCode method throw exception when code not active', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::CANCELLED->value,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $this->storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
        collect([]),
        1,
        '1234',
        now()->format('Y-m-d H:i:s')
    );
})->throws(HttpException::class, 'Specified Store manager authorization code is not active.');

test(
    'checkStoreManagerAuthorizationCode method throw exception when code is expire',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'code' => '1234',
            'expiry_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $this->storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            collect([]),
            1,
            '1234',
            now()->format('Y-m-d H:i:s')
        );
    }
)->throws(HttpException::class, 'Specified Store manager authorization code is expiry.');

test(
    'checkStoreManagerAuthorizationCode method throw exception when code is expire and happened_at set null',
    function (): void {
        $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'code' => '1234',
            'expiry_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
        ]);

        $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
            $storeManagerAuthorizationCode
        ): void {
            $mock->shouldReceive('getByCode')
                ->once()
                ->andReturn($storeManagerAuthorizationCode);
        });

        $this->storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            collect([]),
            1,
            '1234',
            null
        );
    }
)->throws(HttpException::class, 'Specified Store manager authorization code is expiry.');

test('checkStoreManagerAuthorizationCode return null as expected', function (): void {
    $storeManagerAuthorizationCode = StoreManagerAuthorizationCode::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'code' => '1234',
        'expiry_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => StoreManagerAuthorizationCodeStatuses::ACTIVE,
    ]);

    $this->mock(StoreManagerAuthorizationCodeQueries::class, function ($mock) use (
        $storeManagerAuthorizationCode
    ): void {
        $mock->shouldReceive('getByCode')
            ->once()
            ->andReturn($storeManagerAuthorizationCode);
    });

    $response = $this->storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
        collect([]),
        1,
        '1234',
        now()->format('Y-m-d H:i:s')
    );

    $this->assertNull($response);
});
