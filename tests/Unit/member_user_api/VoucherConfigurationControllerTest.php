<?php

declare(strict_types=1);

use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Http\Controllers\Api\Member\VoucherConfigurationController;
use App\Models\Employee;
use App\Models\Member;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;

test(
    'it calls the getListLoyaltyPointForPosWithRelatedData and returns birthday voucher configuration',
    function (): void {
        [
            $request,
            $companyId,
            $member,
            $voucherConfiguration
        ] = seedVoucherConfigurationRecordsForMember();

        $this->mock(VoucherConfigurationQueries::class, function ($mock) use (
            $companyId,
            $voucherConfiguration
        ): void {
            $mock->shouldReceive('getListLoyaltyPointForPosWithRelatedData')
            ->times(1)
            ->with($companyId)
            ->andReturn(collect([$voucherConfiguration]));
        });

        $voucherConfigurationController = new VoucherConfigurationController();
        $response = $voucherConfigurationController->getLoyaltyPointVoucherConfiguration($request);

        expect($response['vouchers']->first()->resource->toArray())
        ->toHaveKeys(
            ['id', 'use_minimum_spend_amount', 'validity_days', 'discount_type', 'get_value', 'start_date', 'end_date']
        );
    }
);

function seedVoucherConfigurationRecordsForMember(): array
{
    $companyId = 1;

    Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $voucherConfiguration = VoucherConfiguration::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'voucher_type' => VoucherTypes::BIRTHDAY_VOUCHER->value,
        'status' => true,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'created_location_id' => 1,
        'membership_id' => 1,
        'loyalty_points' => 0,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Member => $member);

    return [$request, $companyId, $member, $voucherConfiguration];
}
