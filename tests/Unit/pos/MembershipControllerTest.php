<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Membership\MembershipQueries;
use App\Http\Controllers\Api\Pos\MembershipController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Membership;
use Illuminate\Http\Request;

test('it calls the getList method and returns membership list records', function (): void {
    $companyId = 1;

    $membership = Membership::factory()->make([
        'company_id' => $companyId,
        'min_loyalty_points_for_redemption' => 10,
        'max_loyalty_points_for_redemption' => 100,
    ]);

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

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(MembershipQueries::class, function ($mock) use ($companyId, $membership): void {
        $mock->shouldReceive('getByCompanyIdSortByMinimumSpendAmount')
            ->once()
            ->with($companyId, null)
            ->andReturn(collect($membership));
    });

    $membershipController = new MembershipController();
    $response = $membershipController->getList($request);

    expect($response['memberships']->resource->toArray())
        ->toHaveKeys(
            [
                'name',
                'lifetime_value',
                'loyalty_points_per_currency_unit',
                'min_loyalty_points_for_redemption',
                'max_loyalty_points_for_redemption',
            ]
        );
});
