<?php

declare(strict_types=1);

use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Domains\HoldSale\Enums\HoldSaleTypes;
use App\Domains\HoldSale\HoldSaleQueries;
use App\Domains\HoldSale\Services\CheckHoldSaleDetailsService;
use App\Domains\HoldSale\Services\HoldSaleReturnService;
use App\Domains\Member\MemberQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Models\Cashier;
use App\Models\HoldSale;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StoreManager;
use App\Models\UnitOfMeasureDerivative;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->checkHoldSaleDetailsService = new CheckHoldSaleDetailsService();
    $this->companyId = 1;

    $this->saleDetails = [
        'offline_id' => '1',
        'type_id' => HoldSaleTypes::REGULAR_SALE->value,
        'total_amount_paid' => 1.00,
        'items_discount_amount' => 1.00,
        'total_discount_amount' => 1.00,
        'round_off' => 1.00,
        'return_items' => null,
        'items' => [
            [
                'id' => 1,
                'price' => '10.00',
                'quantity' => '10',
                'promoter_ids' => [1],
            ],
        ],
        'notes' => 'Notes goes here',
        'happened_at' => '2022-01-04 04:20:50',
        'member_id' => 1,
        'store_manager_id' => 1,
        'store_manager_passcode' => '123',
        'reason' => 'Test',
    ];

    $this->holdSaleData = new HoldSaleData(...$this->saleDetails);
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;

    $this->product = Product::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'name' => 'ABC',
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'upc' => 'abd123',
        'retail_price' => 10.00,
        'has_batch' => false,
        'status' => false,
    ]);

    $this->cartItems = collect($this->holdSaleData->items);

    $this->cashier = Cashier::factory()->make([
        'employee_id' => 1,
        'cashier_group_id' => 1,
    ]);
});

test('setDetails method works as expected', function (): void {
    $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIds')
            ->once();
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('memberExistsById')
            ->once()
            ->andReturn(null);
    });

    $cartItems = $this->cartItems->toArray();
    $cartItems[0]['derivative_id'] = 1;

    $this->checkHoldSaleDetailsService->setDetails(
        $this->holdSaleData,
        collect($this->product),
        collect($cartItems),
        1
    );
});

test('checkRequestDetails method return null when cart item is empty', function (): void {
    $mock = $this->createPartialMock(CheckHoldSaleDetailsService::class, ['hasCartItems']);

    $mock->holdSaleReturnService = $this->mock(HoldSaleReturnService::class, function ($mock): void {
        $mock->shouldReceive('hasReturnItems')
            ->once()
            ->andReturn(false);
    });

    $mock->cartItems = collect($this->cartItems);
    $mock->products = collect([$this->product]);

    $mock->holdSaleData = $this->holdSaleData;
    $mock->companyId = 1;

    $mock->expects($this->once())
        ->method('hasCartItems')
        ->will($this->returnValue(false));

    $response = $mock->checkRequestDetails();
    $this->assertNull($response);
});

test('checkRequestDetails method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(
        CheckHoldSaleDetailsService::class,
        [
            'hasCartItems',
            'checkMemberExists',
            'checkEmployeeExists',
            'checkProducts',
            'checkDerivativeDetails',
            'checkHoldSaleCancelled',
            'checkHoldSaleCompleted',
            'checkHoldSaleReleased',
            'checkHoldSale',
        ]
    );

    $mock->cartItems = collect($this->cartItems);
    $mock->products = collect([$this->product]);

    $mock->holdSaleData = $this->holdSaleData;
    $mock->companyId = 1;
    $mock->holdSaleReturnService = $this->mock(HoldSaleReturnService::class, function ($mock): void {
        $mock->shouldReceive('hasReturnItems')
            ->once()
            ->andReturn(true);
        $mock->shouldReceive('checkReturnItems')
            ->once();
    });

    $mock->expects($this->once())
        ->method('hasCartItems')
        ->will($this->returnValue(true));

    $mock->expects($this->once())
        ->method('checkMemberExists');

    $mock->expects($this->once())
        ->method('checkEmployeeExists');

    $mock->expects($this->once())
        ->method('checkProducts');

    $mock->expects($this->once())
        ->method('checkDerivativeDetails');

    $mock->expects($this->once())
        ->method('checkHoldSaleCancelled');

    $mock->expects($this->once())
        ->method('checkHoldSaleCompleted');

    $mock->expects($this->once())
        ->method('checkHoldSaleReleased');

    $mock->expects($this->once())
        ->method('checkHoldSale');

    $mock->checkRequestDetails();
});

test('checkMemberExists return null when member id not pass', function (): void {
    $this->holdSaleData->member_id = null;
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $this->checkHoldSaleDetailsService->companyId = $this->companyId;
    $response = $this->checkHoldSaleDetailsService->checkMemberExists();
    $this->assertNull($response);
});

test('checkMemberExists return null when member id is available in our records', function (): void {
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $this->checkHoldSaleDetailsService->companyId = $this->companyId;
    $this->checkHoldSaleDetailsService->member = new Member();

    $response = $this->checkHoldSaleDetailsService->checkMemberExists();
    $this->assertNull($response);
});

test('checkMemberExists method throws an exception when member id not available in our records', function (): void {
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $this->checkHoldSaleDetailsService->companyId = $this->companyId;

    $this->checkHoldSaleDetailsService->checkMemberExists();
})->throws(HttpException::class, 'The selected member id is invalid.');

test('isMemberAttached method returns boolean as expected', function (): void {
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $response = $this->checkHoldSaleDetailsService->isMemberAttached();
    $this->assertTrue($response);

    $this->holdSaleData->member_id = null;
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $response = $this->checkHoldSaleDetailsService->isMemberAttached();
    $this->assertFalse($response);
});

test('checkProducts method returns null when all product available in our records', function (): void {
    $this->product->status = false;
    $this->checkHoldSaleDetailsService->products = collect([$this->product]);
    $this->checkHoldSaleDetailsService->cartItems = collect($this->cartItems);
    $response = $this->checkHoldSaleDetailsService->checkProducts();
    $this->assertNull($response);
});

test('checkProducts method throws an exception when product is not available in our records', function (): void {
    $this->checkHoldSaleDetailsService->products = collect([]);
    $this->checkHoldSaleDetailsService->cartItems = collect($this->cartItems);
    $this->checkHoldSaleDetailsService->checkProducts();
})->throws(HttpException::class, 'Some of the products are not in our records.');

test('checkDerivativeDetails method returns nothing when no derivative details specified', function (): void {
    $response = $this->checkHoldSaleDetailsService->checkDerivativeDetails($this->holdSaleData->items[0]);
    $this->assertNull($response);
});

test(
    'checkDerivativeDetails method throws an exception when when specified derivative id does not exists in our records.',
    function (): void {
        $this->checkHoldSaleDetailsService->derivatives = collect([
            UnitOfMeasureDerivative::factory()->make([
                'id' => 1,
                'unit_of_measure_id' => 1,
            ]),
        ]);
        $this->product->unit_of_measure_id = 1;
        $cartItem = $this->holdSaleData->items[0];
        $cartItem['derivative_id'] = 2;

        $this->checkHoldSaleDetailsService->checkDerivativeDetails($cartItem);
    }
)->throws(HttpException::class, 'Specified derivative id is not available in our records.');

test('hasCartItems method returns boolean as expected', function (): void {
    $this->checkHoldSaleDetailsService->cartItems = collect($this->cartItems);
    $response = $this->checkHoldSaleDetailsService->hasCartItems();
    $this->assertTrue($response);

    $this->checkHoldSaleDetailsService->cartItems = collect([]);
    $response = $this->checkHoldSaleDetailsService->hasCartItems();
    $this->assertFalse($response);
});

test('hasDerivativeAttached method returns boolean as expected', function (): void {
    $cartItem = $this->holdSaleData->items[0];
    $cartItem['derivative_id'] = 2;
    $response = $this->checkHoldSaleDetailsService->hasDerivativeAttached($cartItem);
    $this->assertTrue($response);

    $cartItem['derivative_id'] = null;
    $response = $this->checkHoldSaleDetailsService->hasDerivativeAttached($cartItem);
    $this->assertFalse($response);
});

test('checkOfflineId method returns null when offline id not in over data base', function (): void {
    $this->mock(HoldSaleQueries::class, function ($mock): void {
        $mock->shouldReceive('doesOfflineIdExist')
            ->once()
            ->andReturn(false);
    });

    $response = $this->checkHoldSaleDetailsService->checkOfflineId();
    $this->assertNull($response);
});

test('checkOfflineId method throws an exception when when offline id in over data base', function (): void {
    $this->mock(HoldSaleQueries::class, function ($mock): void {
        $mock->shouldReceive('doesOfflineIdExist')
            ->once()
            ->andReturn(true);
    });

    $response = $this->checkHoldSaleDetailsService->checkOfflineId();
    $this->assertNull($response);
})->throws(HttpException::class, 'The selected offline ID is already in use.');

test('checkEmployeeExists return null when employee id not pass', function (): void {
    $this->holdSaleData->employee_id = null;
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $this->checkHoldSaleDetailsService->companyId = $this->companyId;
    $response = $this->checkHoldSaleDetailsService->checkEmployeeExists();
    $this->assertNull($response);
});

test('checkEmployeeExists return null when employee id is available in our records', function (): void {
    $this->holdSaleData->employee_id = 1;
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $this->checkHoldSaleDetailsService->companyId = $this->companyId;
    $this->checkHoldSaleDetailsService->member = new Member();

    $response = $this->checkHoldSaleDetailsService->checkEmployeeExists();
    $this->assertNull($response);
});

test('checkEmployeeExists method throws an exception when employee id not available in our records', function (): void {
    $this->holdSaleData->employee_id = 1;
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $this->checkHoldSaleDetailsService->companyId = $this->companyId;
    $this->checkHoldSaleDetailsService->member = null;

    $this->checkHoldSaleDetailsService->checkEmployeeExists();
})->throws(HttpException::class, 'The selected employee id is invalid.');

test('checkHoldSaleCancelled method return null when cancelled_at not set', function (): void {
    $response = $this->checkHoldSaleDetailsService->checkHoldSaleCancelled();
    $this->assertNull($response);
});

test('checkHoldSaleCancelled method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(CheckHoldSaleDetailsService::class, ['checkHoldSaleCompletedOrCancelled']);

    $this->holdSaleData->cancelled_at = now()->format('Y-m-d H:i:s');

    $mock->holdSaleData = $this->holdSaleData;
    $mock->saleMismatches = collect([]);

    $mock->expects($this->once())
        ->method('checkHoldSaleCompletedOrCancelled');

    $mock->companyId = $this->companyId;

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '123',
    ]);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn($storeManager);
    });

    $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
        $mock->shouldReceive('checkStoreManagerAuthorizationCode')
            ->once();
    });

    $mock->checkHoldSaleCancelled();
});

test('checkHoldSaleCompleted method return null when complete_at not set', function (): void {
    $response = $this->checkHoldSaleDetailsService->checkHoldSaleCompleted();
    $this->assertNull($response);
});

test('checkHoldSaleCompleted method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(
        CheckHoldSaleDetailsService::class,
        ['checkHoldSaleCompletedOrCancelled', 'checkCompleteOfflineId']
    );

    $this->holdSaleData->complete_at = now()->format('Y-m-d H:i:s');

    $mock->holdSaleData = $this->holdSaleData;

    $mock->expects($this->once())
        ->method('checkHoldSaleCompletedOrCancelled');

    $mock->expects($this->once())
        ->method('checkCompleteOfflineId');

    $mock->checkHoldSaleCompleted();
});

test('checkHoldSaleReleased method return null when released_at not set', function (): void {
    $response = $this->checkHoldSaleDetailsService->checkHoldSaleReleased();
    $this->assertNull($response);
});

test('checkHoldSaleReleased method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(CheckHoldSaleDetailsService::class, ['checkHoldSaleCompletedOrCancelled']);

    $this->holdSaleData->released_at = now()->format('Y-m-d H:i:s');

    $mock->holdSaleData = $this->holdSaleData;

    $mock->expects($this->once())
        ->method('checkHoldSaleCompletedOrCancelled');

    $mock->checkHoldSaleReleased();
});

test(
    'checkHoldSaleCompletedOrCancelled method return null when hold sale not completed or not cancelled',
    function (): void {
        $this->mock(HoldSaleQueries::class, function ($mock): void {
            $mock->shouldReceive('isCompletedHoldSale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('isCancelledHoldSale')
                ->once()
                ->andReturn(false);
        });

        $response = $this->checkHoldSaleDetailsService->checkHoldSaleCompletedOrCancelled();
        $this->assertNull($response);
    }
);

test(
    'checkHoldSaleCompletedOrCancelled method throws an exception when hold sale not already Cancelled',
    function (): void {
        $this->mock(HoldSaleQueries::class, function ($mock): void {
            $mock->shouldReceive('isCompletedHoldSale')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('isCancelledHoldSale')
                ->once()
                ->andReturn(true);
        });

        $response = $this->checkHoldSaleDetailsService->checkHoldSaleCompletedOrCancelled();
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Specified hold sale: 1 is already cancelled.');

test(
    'checkHoldSaleCompletedOrCancelled method throws an exception when hold sale not already completed',
    function (): void {
        $this->mock(HoldSaleQueries::class, function ($mock): void {
            $mock->shouldReceive('isCompletedHoldSale')
                ->once()
                ->andReturn(true);
            $mock->shouldReceive('isCancelledHoldSale')
                ->times(0)
                ->andReturn(false);
        });

        $response = $this->checkHoldSaleDetailsService->checkHoldSaleCompletedOrCancelled();
        $this->assertNull($response);
    }
)->throws(HttpException::class, 'Specified hold sale: 1 is already Completed.');

test(
    'checkCompleteOfflineId method throws an exception when complete offline id not provided',
    function (): void {
        $this->holdSaleData->complete_offline_id = null;
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->checkCompleteOfflineId();
    }
)->throws(HttpException::class, 'complete_offline_id is required.');

test(
    'checkCompleteOfflineId method return null when complete offline id is taken in sales',
    function (): void {
        $this->holdSaleData->complete_offline_id = '1';
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->companyId = $this->companyId;

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineSaleIdExist')
                ->once()
                ->andReturn(true);
        });

        $response = $this->checkHoldSaleDetailsService->checkCompleteOfflineId();
        $this->assertNull($response);
    }
);

test(
    'checkCompleteOfflineId method return null when complete offline id is taken in sale returns',
    function (): void {
        $this->holdSaleData->complete_offline_id = '1';
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->companyId = $this->companyId;

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineSaleIdExist')
                ->once()
                ->andReturn(false);
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineSaleReturnIdExist')
                ->once()
                ->andReturn(true);
        });

        $response = $this->checkHoldSaleDetailsService->checkCompleteOfflineId();
        $this->assertNull($response);
    }
);

test(
    'checkCompleteOfflineId method throws an exception when complete offline id is not in records',
    function (): void {
        $this->holdSaleData->complete_offline_id = '1';
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->companyId = $this->companyId;

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineSaleIdExist')
                ->once()
                ->andReturn(false);
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('doesOfflineSaleReturnIdExist')
                ->once()
                ->andReturn(false);
        });

        $this->checkHoldSaleDetailsService->checkCompleteOfflineId();
    }
)->throws(HttpException::class, 'The complete offline id is not in our records.');

test('checkHoldSaleCancelled method throws an exception when reason is null', function (): void {
    $mock = $this->createPartialMock(CheckHoldSaleDetailsService::class, ['checkHoldSaleCompletedOrCancelled']);

    $this->holdSaleData->cancelled_at = now()->format('Y-m-d H:i:s');
    $this->holdSaleData->reason = null;
    $mock->holdSaleData = $this->holdSaleData;

    $mock->expects($this->never())
        ->method('checkHoldSaleCompletedOrCancelled');

    $mock->companyId = $this->companyId;

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '123',
    ]);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->never()
            ->andReturn($storeManager);
    });

    $mock->checkHoldSaleCancelled();
})->throws(HttpException::class, 'reason is required.');

test('checkHoldSaleCancelled method throws an exception when store_manager_id is null', function (): void {
    $mock = $this->createPartialMock(CheckHoldSaleDetailsService::class, ['checkHoldSaleCompletedOrCancelled']);

    $this->holdSaleData->cancelled_at = now()->format('Y-m-d H:i:s');
    $this->holdSaleData->store_manager_id = null;
    $mock->holdSaleData = $this->holdSaleData;

    $mock->expects($this->never())
        ->method('checkHoldSaleCompletedOrCancelled');

    $mock->companyId = $this->companyId;

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '123',
    ]);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->never()
            ->andReturn($storeManager);
    });

    $mock->checkHoldSaleCancelled();
})->throws(HttpException::class, 'store_manager_id is required.');

test('checkHoldSaleCancelled method throws an exception when store manager is null', function (): void {
    $mock = $this->createPartialMock(CheckHoldSaleDetailsService::class, ['checkHoldSaleCompletedOrCancelled']);

    $this->holdSaleData->cancelled_at = now()->format('Y-m-d H:i:s');
    $this->holdSaleData->store_manager_id = 1;
    $mock->holdSaleData = $this->holdSaleData;

    $mock->expects($this->never())
        ->method('checkHoldSaleCompletedOrCancelled');

    $mock->companyId = $this->companyId;

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn(null);
    });

    $mock->checkHoldSaleCancelled();
})->throws(HttpException::class, 'Specified Store Manager does not correspond with our records.');

test('checkHoldSaleCancelled method throws an exception when store manager passcode not match', function (): void {
    $mock = $this->createPartialMock(CheckHoldSaleDetailsService::class, ['checkHoldSaleCompletedOrCancelled']);

    $this->holdSaleData->cancelled_at = now()->format('Y-m-d H:i:s');
    $this->holdSaleData->store_manager_id = 1;
    $mock->holdSaleData = $this->holdSaleData;

    $mock->expects($this->never())
        ->method('checkHoldSaleCompletedOrCancelled');

    $mock->companyId = $this->companyId;

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'passcode' => '1234',
    ]);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->andReturn($storeManager);
    });

    $mock->checkHoldSaleCancelled();
})->throws(
    HttpException::class,
    'The Store Manager provided passcode for authorization does not correspond with our records.'
);

test(
    'checkHoldSale method method throws an exception when hold sale is cancelled and offline_id not match',
    function (): void {
        $this->holdSaleData->cancelled_at = now()->format('Y-m-d');
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->holdSale = null;

        $this->mock(HoldSaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getNotCancelByOfflineId')
                ->once()
                ->andReturn(null);
        });

        $this->checkHoldSaleDetailsService->checkHoldSale();
        $this->assertNull($this->checkHoldSaleDetailsService->holdSale);
    }
)->throws(HttpException::class, 'The offline ID you chose could not be located');

test(
    'checkHoldSale method return null and set hold sale when hold sale is cancelled and offline_id match',
    function (): void {
        $this->holdSaleData->cancelled_at = now()->format('Y-m-d');
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->holdSale = null;

        $holdSale = HoldSale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'complete_sale_id' => 1,
        ]);

        $this->mock(HoldSaleQueries::class, function ($mock) use ($holdSale): void {
            $mock->shouldReceive('getNotCancelByOfflineId')
                ->once()
                ->andReturn($holdSale);
        });

        $response = $this->checkHoldSaleDetailsService->checkHoldSale();
        $this->assertNull($response);
        $this->assertTrue($this->checkHoldSaleDetailsService->holdSale === $holdSale);
    }
);

test(
    'checkHoldSale method method throws an exception when hold sale is released and offline_id not match',
    function (): void {
        $this->holdSaleData->released_at = now()->format('Y-m-d');
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->holdSale = null;

        $this->mock(HoldSaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getNotCompleteAndNotCancelByOfflineId')
                ->once()
                ->andReturn(null);
        });

        $this->checkHoldSaleDetailsService->checkHoldSale();
        $this->assertNull($this->checkHoldSaleDetailsService->holdSale);
    }
)->throws(HttpException::class, 'The offline ID you chose could not be located');

test(
    'checkHoldSale method return null and set hold sale when hold sale is released and offline_id match',
    function (): void {
        $this->holdSaleData->released_at = now()->format('Y-m-d');
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->holdSale = null;

        $holdSale = HoldSale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'complete_sale_id' => 1,
        ]);

        $this->mock(HoldSaleQueries::class, function ($mock) use ($holdSale): void {
            $mock->shouldReceive('getNotCompleteAndNotCancelByOfflineId')
                ->once()
                ->andReturn($holdSale);
        });

        $response = $this->checkHoldSaleDetailsService->checkHoldSale();
        $this->assertNull($response);
        $this->assertTrue($this->checkHoldSaleDetailsService->holdSale === $holdSale);
    }
);

test(
    'checkHoldSale method method throws an exception when hold sale is complete and offline_id not match',
    function (): void {
        $this->holdSaleData->complete_at = now()->format('Y-m-d');
        $this->holdSaleData->complete_offline_id = '1';
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->holdSale = null;
        $this->checkHoldSaleDetailsService->sale = null;
        $this->checkHoldSaleDetailsService->saleReturn = null;
        $this->checkHoldSaleDetailsService->companyId = 1;

        $this->mock(HoldSaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getNotCompleteByOfflineId')
                ->once()
                ->andReturn(null);
        });

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getByOfflineId')
                ->once()
                ->andReturn(null);
        });

        $this->mock(SaleReturnQueries::class, function ($mock): void {
            $mock->shouldReceive('getByOfflineId')
                ->once()
                ->andReturn(null);
        });

        $this->checkHoldSaleDetailsService->checkHoldSale();
        $this->assertNull($this->checkHoldSaleDetailsService->holdSale);
        $this->assertNull($this->checkHoldSaleDetailsService->sale);
        $this->assertNull($this->checkHoldSaleDetailsService->saleReturn);
    }
)->throws(HttpException::class, 'The offline ID you chose could not be located');

test(
    'checkHoldSale method return null and set hold sale when hold sale is complete and offline_id match',
    function (): void {
        $this->holdSaleData->complete_at = now()->format('Y-m-d');
        $this->holdSaleData->complete_offline_id = '1';
        $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
        $this->checkHoldSaleDetailsService->holdSale = null;
        $this->checkHoldSaleDetailsService->sale = null;
        $this->checkHoldSaleDetailsService->saleReturn = null;
        $this->checkHoldSaleDetailsService->companyId = 1;

        $holdSale = HoldSale::factory()->make([
            'id' => 1,
            'counter_update_id' => 1,
            'complete_sale_id' => 1,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => 1,
            'counter_update_id' => 1,
            'member_id' => 1,
        ]);

        $this->mock(HoldSaleQueries::class, function ($mock) use ($holdSale): void {
            $mock->shouldReceive('getNotCompleteByOfflineId')
                ->once()
                ->andReturn($holdSale);
        });

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getByOfflineId')
                ->once()
                ->andReturn($sale);
        });

        $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
            $mock->shouldReceive('getByOfflineId')
                ->once()
                ->andReturn($saleReturn);
        });

        $response = $this->checkHoldSaleDetailsService->checkHoldSale();
        $this->assertNull($response);
        $this->assertTrue($this->checkHoldSaleDetailsService->holdSale === $holdSale);
        $this->assertTrue($this->checkHoldSaleDetailsService->sale === $sale);
        $this->assertTrue($this->checkHoldSaleDetailsService->saleReturn === $saleReturn);
    }
);

test('checkHoldSale method calls same class methods as expected', function (): void {
    $mock = $this->createPartialMock(CheckHoldSaleDetailsService::class, ['checkOfflineId']);

    $mock->expects($this->once())
        ->method('checkOfflineId');

    $this->holdSaleData->complete_at = null;
    $this->holdSaleData->complete_offline_id = null;
    $mock->holdSaleData = $this->holdSaleData;
    $mock->holdSale = null;
    $mock->sale = null;
    $mock->saleReturn = null;
    $mock->companyId = 1;

    $mock->checkHoldSale();

    $this->assertNull($mock->holdSale);
    $this->assertNull($mock->sale);
    $this->assertNull($mock->saleReturn);
});

test(
    'checkStoreManagerAuthorizationCode method calls checkStoreManagerAuthorizationCode methods of StoreManagerAuthorizationCodeUsageService class',
    function (): void {
        $this->mock(StoreManagerAuthorizationCodeUsageService::class, function ($mock): void {
            $mock->shouldReceive('checkStoreManagerAuthorizationCode')
                ->once();
        });
        $this->checkHoldSaleDetailsService->saleMismatches = collect([]);
        $this->checkHoldSaleDetailsService->checkStoreManagerAuthorizationCode();
    }
);

test('it calls the getEmployeeMember method of MemberQueries class', function (): void {
    $member = Member::factory()->make([
        'company_id' => 1,
        'created_location_id' => 1,
        'status' => true,
        'membership_id' => 1,
        'employee_id' => 1,
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByEmployeeIdWithEmployee')
            ->once()
            ->andReturn($member);
    });
    $this->checkHoldSaleDetailsService->companyId = $this->companyId;
    $response = $this->checkHoldSaleDetailsService->getEmployeeMember($member->employee_id);

    expect($response)->toBeInstanceOf(Member::class);
});

test('getMember method returns null when the employee id and member id is null.', function (): void {
    $this->holdSaleData->member_id = null;
    $this->holdSaleData->employee_id = null;

    $this->checkHoldSaleDetailsService->companyId = $this->companyId;
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $this->checkHoldSaleDetailsService->holdSale = null;

    $response = $this->checkHoldSaleDetailsService->getMember();
    $this->assertEquals(null, $response);
});

test('getMember method returns member when the employee id is not null', function (): void {
    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'created_location_id' => 1,
        'first_name' => 'ABC',
        'mobile_number' => '123456789',
        'employee_id' => 1,
        'card_number' => '123456789',
    ]);

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getByEmployeeIdWithEmployee')
            ->once()
            ->andReturn($member);
    });

    $this->holdSaleData->member_id = null;
    $this->holdSaleData->employee_id = 1;

    $this->checkHoldSaleDetailsService->companyId = $this->companyId;
    $this->checkHoldSaleDetailsService->holdSaleData = $this->holdSaleData;
    $this->checkHoldSaleDetailsService->holdSale = null;

    $response = $this->checkHoldSaleDetailsService->getMember();
    expect($response)->toBeInstanceOf(Member::class);
});
