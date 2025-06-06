<?php

declare(strict_types=1);

use App\Domains\PaymentType\DataObjects\PaymentTypeData;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Domains\ShippingZone\ShippingZoneQueries;
use App\Http\Controllers\Admin\PaymentTypeController;
use App\Models\PaymentType;
use App\Models\SaleChannel;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the payment type queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'abc',
        'sort_by' => 'name',
        'sort_direction' => 'desc',
        'per_page' => 1,
    ];

    $paymentTypeQueries = $this->mock(PaymentTypeQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
    });

    $paymentTypeController = new PaymentTypeController($paymentTypeQueries);

    $response = $paymentTypeController->fetchPaymentTypes(new Request($requestParameter));

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the add method of payment type queries class', function (): void {
    $paymentTypeData = new PaymentTypeData(
        'payment_type',
        false,
        false,
        false,
        false,
        false,
        true,
        'cash.png',
        'payment_type',
        false,
        false
    );

    $companyId = 1;
    setCompanyIdInSession($companyId);

    $paymentTypeQueries = $this->mock(PaymentTypeQueries::class, function ($mock) use (
        $paymentTypeData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($paymentTypeData, $companyId);
    });

    $paymentTypeController = new PaymentTypeController($paymentTypeQueries);
    $redirectResponse = $paymentTypeController->store($paymentTypeData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Payment Type added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/payment-types', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the payment type queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'company_id' => $companyId,
            'name' => 'payment_type_1',
            'is_member_required' => false,
            'is_available_for_refund' => false,
            'status' => false,
        ];

        $paymentTypeQueries = $this->mock(PaymentTypeQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new PaymentType($requestParameter));
        });

        $saleChannel = SaleChannel::factory()->make([
            'id' => 1,
            'name' => 'sale_channel_1',
            'company_id' => 1,
            'default_location_id' => 1,
        ]);

        $shippingZone = ShippingZone::factory()->make([
            'id' => 1,
            'name' => 'shipping_zone_1',
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $this->mock(SaleChannelQueries::class, function ($mock) use ($saleChannel, $companyId): void {
            $mock->shouldReceive('getAllByCompanyId')
                ->once()
                ->with($companyId)
                ->andReturn(collect([$saleChannel]));
        });

        $this->mock(ShippingZoneQueries::class, function ($mock) use ($shippingZone): void {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(collect([$shippingZone]));
        });

        $paymentTypeController = new PaymentTypeController($paymentTypeQueries);
        $response = $paymentTypeController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
            ->has(
                'paymentType',
                fn (Assert $paymentType): Assert => $paymentType
                    ->where('name', 'payment_type_1')
                    ->where('is_available_for_refund', false)
                    ->where('is_member_required', false)
                    ->where('status', false)
                    ->etc()
            )
        );
    }
);

test('It calls the update method of payment type queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $paymentTypeData = new PaymentTypeData(
        'payment_type_2',
        false,
        false,
        false,
        false,
        false,
        true,
        'cash.png',
        'payment_type_2',
        false,
        false
    );

    $paymentTypeQueries = $this->mock(PaymentTypeQueries::class, function ($mock) use (
        $paymentTypeData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($paymentTypeData, 1, $companyId);
    });

    $paymentTypeController = new PaymentTypeController($paymentTypeQueries);
    $redirectResponse = $paymentTypeController->update($paymentTypeData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Payment Type updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/payment-types', $redirectResponse->getTargetUrl());
});

test('it calls the setStatus method of paymentTypeQueries class if payment type is not static', function (): void {
    setCompanyIdInSession(1);

    $staticPaymentTypes = StaticPaymentTypes::getCasesValue();

    $paymentType = PaymentType::factory()->make([
        'id' => (int) $staticPaymentTypes->last() + 1,
        'company_id' => 1,
    ]);

    $paymentTypeQueries = $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
        $mock->shouldReceive('setStatus')
            ->once()
            ->with($paymentType->id, 1, false);
    });

    $paymentTypeController = new PaymentTypeController($paymentTypeQueries);
    $response = $paymentTypeController->setStatus($paymentType->id, false);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/payment-types', $response->getTargetUrl());
});

test('it cannot call the setStatus method of paymentTypeQueries class if payment type is static', function (): void {
    setCompanyIdInSession(1);

    $staticPaymentTypes = StaticPaymentTypes::getCasesValue();

    $paymentType = PaymentType::factory()->make([
        'id' => $staticPaymentTypes->first(),
        'company_id' => 1,
    ]);

    $paymentTypeQueries = $this->mock(PaymentTypeQueries::class, function ($mock) use ($paymentType): void {
        $mock->shouldReceive('setStatus')
            ->times(0)
            ->with($paymentType->id, 1, false);
    });

    $paymentTypeController = new PaymentTypeController($paymentTypeQueries);
    $response = $paymentTypeController->setStatus($paymentType->id, false);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals(
        'The status of static payment types cannot be changed.',
        $response->getSession()->all()['error']
    );
    $this->assertStringContainsString('admin/payment-types', $response->getTargetUrl());
});

test('It calls the paymentTypesExport method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $paymentTypeQueries = $this->mock(PaymentTypeQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getPaymentTypesExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new PaymentType()));
    });

    $paymentTypeController = new PaymentTypeController($paymentTypeQueries);

    $response = $paymentTypeController->paymentTypesExport('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls the exportBulkUpdatePaymentTypes method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $paymentTypeQueries = $this->mock(PaymentTypeQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('getActivePaymentTypesForBulkUpdate')
            ->once()
            ->with($companyId)
            ->andReturn(collect([]));
    });

    $paymentTypeController = new PaymentTypeController($paymentTypeQueries);

    $response = $paymentTypeController->exportBulkUpdatePaymentTypes();

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
