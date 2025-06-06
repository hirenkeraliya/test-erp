<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\CounterUpdateDeclarationAttempt\CounterUpdateDeclarationAttemptQueries;
use App\Domains\CounterUpdateDeclarationAttempt\DataObjects\CounterUpdateDeclarationAttemptData;
use App\Domains\CounterUpdateDeclarationAttemptPayment\CounterUpdateDeclarationAttemptPaymentQueries;
use App\Domains\PaymentType\PaymentTypeQueries;
use App\Http\Controllers\Api\Pos\CounterUpdateDeclarationAttemptController;
use App\Models\Cashier;
use App\Models\Employee;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls the getList method and returns the list of counter update declaration attempt', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CounterUpdateDeclarationAttemptQueries::class, function ($mock): void {
        $mock->shouldReceive('getList')
            ->once()
            ->andReturn(collect([]));
    });

    $counterUpdateDeclarationAttemptController = new CounterUpdateDeclarationAttemptController();
    $response = $counterUpdateDeclarationAttemptController->getList($request);

    expect($response['counter_update_declaration_attempts']->resource);
});

test('It can store counter update declaration attempt', function (): void {
    $data = [
        'offline_id' => '1',
        'happened_at' => '1',
        'payments' => [
            [
                'payment_type_id' => 2,
                'declared_amount' => 50,
                'calculated_amount' => 50,
            ],
        ],
    ];

    $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(PaymentTypeQueries::class, function ($mock): void {
        $mock->shouldReceive('checkExistingPaymentTypeIds')
            ->once()
            ->andReturn(true);
    });

    $this->mock(CounterUpdateDeclarationAttemptQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn(1);
    });

    $this->mock(CounterUpdateDeclarationAttemptPaymentQueries::class, function ($mock): void {
        $mock->shouldReceive('createMany')
            ->once();
    });

    $counterUpdateDeclarationAttemptController = new CounterUpdateDeclarationAttemptController();
    $counterUpdateDeclarationAttemptController->store($request, new CounterUpdateDeclarationAttemptData(...$data));
});

test(
    'It cannot store counter update declaration attempt when payment type id does not available in our records.',
    function (): void {
        $data = [
            'offline_id' => '1',
            'happened_at' => '1',
            'payments' => [
                [
                    'payment_type_id' => 2,
                    'declared_amount' => 50,
                    'calculated_amount' => 50,
                ],
            ],
        ];

        $cashier = makeCashierAndEmployeeForPosWithCounterUpdateId()['cashier'];

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
                ->once()
                ->with($cashier)
                ->andReturn(1);
        });

        $this->mock(PaymentTypeQueries::class, function ($mock): void {
            $mock->shouldReceive('checkExistingPaymentTypeIds')
                ->once()
                ->andReturn(false);
        });

        $this->mock(CounterUpdateDeclarationAttemptQueries::class, function ($mock): void {
            $mock->shouldNotReceive('addNew');
        });

        $this->mock(CounterUpdateDeclarationAttemptPaymentQueries::class, function ($mock): void {
            $mock->shouldNotReceive('createMany');
        });

        $counterUpdateDeclarationAttemptController = new CounterUpdateDeclarationAttemptController();
        $counterUpdateDeclarationAttemptController->store($request, new CounterUpdateDeclarationAttemptData(...$data));
    }
)->throws(HttpException::class, 'Some of the payment types do not exist in our records.');
