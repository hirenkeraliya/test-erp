<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\Imports\ImportAddLoyaltyPoints;
use App\Domains\Member\MemberQueries;
use App\Models\Admin;
use App\Models\ImportRecord;

test('save method works for the members loyalty point details update', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::ADD_MEMBER_LOYALTY_POINTS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $importRecord->createdBy = Admin::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $memberData = getMemberLoyaltyPointData();

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('getMemberByCardNumber')
            ->times(1);
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('updateLoyaltyPointsForAdmin')
            ->times();
    });

    $ImportAddLoyaltyPoints = new ImportAddLoyaltyPoints();
    $ImportAddLoyaltyPoints->save($memberData, $importRecord);
    $this->assertTrue(true);
});

function getMemberLoyaltyPointData(): array
{
    return [
        'card_number' => '123456789',
        'loyalty_points' => 231,
        'reasons' => 'abcdef',
    ];
}
