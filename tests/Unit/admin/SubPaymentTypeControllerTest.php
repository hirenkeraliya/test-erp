<?php

declare(strict_types=1);

use App\Domains\SubPaymentType\DataObjects\SubPaymentTypeData;
use App\Domains\SubPaymentType\SubPaymentTypeQueries;
use App\Http\Controllers\Admin\SubPaymentTypeController;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the sub payment type queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
        ];

        $paymentTypeId = 1;

        $subPaymentTypeQueries = $this->mock(SubPaymentTypeQueries::class, function ($mock) use (
            $requestParameter,
            $paymentTypeId,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $paymentTypeId, $companyId)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $subPaymentTypeController = new SubPaymentTypeController($subPaymentTypeQueries);

        $response = $subPaymentTypeController->fetchSubPaymentTypes(new Request($requestParameter), $paymentTypeId);

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls the add method of sub payment type queries class', function (): void {
    $subPaymentTypeData = new SubPaymentTypeData(
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

    $paymentTypeId = 1;

    $subPaymentTypeQueries = $this->mock(SubPaymentTypeQueries::class, function ($mock) use (
        $subPaymentTypeData,
        $paymentTypeId,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($subPaymentTypeData, $paymentTypeId, $companyId);
    });

    $subPaymentTypeController = new SubPaymentTypeController($subPaymentTypeQueries);
    $redirectResponse = $subPaymentTypeController->store($subPaymentTypeData, $paymentTypeId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The sub payment type has been successfully added.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/sub-payment-types', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the sub payment type queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $paymentTypeId = 1;

        $requestParameter = [
            'company_id' => $companyId,
            'name' => 'payment_type_1',
            'is_member_required' => false,
            'is_available_for_refund' => false,
            'status' => false,
        ];

        $subPaymentTypeQueries = $this->mock(SubPaymentTypeQueries::class, function ($mock) use (
            $requestParameter,
            $paymentTypeId,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with($paymentTypeId, 1, $companyId)
                ->andReturn(new PaymentType($requestParameter));
        });

        $subPaymentTypeController = new SubPaymentTypeController($subPaymentTypeQueries);
        $response = $subPaymentTypeController->edit($paymentTypeId, 1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'subPaymentType',
            fn (Assert $subPaymentType): Assert => $subPaymentType
                ->where('name', 'payment_type_1')
                ->where('is_available_for_refund', false)
                ->where('is_member_required', false)
                ->where('status', false)
                ->etc()
        )
        );
    }
);

test('It calls the update method of sub payment type queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $paymentTypeId = 1;

    $subPaymentTypeData = new SubPaymentTypeData(
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

    $subPaymentTypeQueries = $this->mock(SubPaymentTypeQueries::class, function ($mock) use (
        $subPaymentTypeData,
        $paymentTypeId,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($subPaymentTypeData, 1, $paymentTypeId, $companyId);
    });

    $subPaymentTypeController = new SubPaymentTypeController($subPaymentTypeQueries);
    $redirectResponse = $subPaymentTypeController->update($subPaymentTypeData, 1, $paymentTypeId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The sub payment type has been successfully updated.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/sub-payment-types', $redirectResponse->getTargetUrl());
});

test('it calls the setStatus method of subPaymentTypeQueries class', function (): void {
    setCompanyIdInSession(1);

    $paymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $subPaymentType = PaymentType::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'parent_payment_type_id' => $paymentType->id,
    ]);

    $subPaymentTypeQueries = $this->mock(SubPaymentTypeQueries::class, function ($mock) use (
        $subPaymentType
    ): void {
        $mock->shouldReceive('setStatus')
            ->once()
            ->with($subPaymentType->id, 1, false);
    });

    $subPaymentTypeController = new SubPaymentTypeController($subPaymentTypeQueries);
    $response = $subPaymentTypeController->setStatus($paymentType->id, $subPaymentType->id, false);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/sub-payment-types', $response->getTargetUrl());
});

test('It calls the exportSubPaymentTypes method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $subPaymentTypeQueries = $this->mock(SubPaymentTypeQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getSubPaymentTypesExport')
            ->once()
            ->with($requestParameter, 1, $companyId)
            ->andReturn(collect(new PaymentType()));
    });

    $subPaymentTypeController = new SubPaymentTypeController($subPaymentTypeQueries);

    $response = $subPaymentTypeController->exportSubPaymentTypes(
        new Request($requestParameter),
        1,
        'filename.csv'
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
