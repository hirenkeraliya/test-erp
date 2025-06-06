<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\Member\Imports\ImportUpdateLoyaltyPoints;
use App\Domains\Member\MemberQueries;
use App\Models\ImportRecord;

test('save method works for the member loyalty point details update', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::UPDATE_MEMBER_LOYALTY_POINTS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);
    $memberData = getMemberLoyaltyPointUpdateData();

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('getMemberByCardNumber')
            ->times(1);
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('updateLoyaltyPointsForAdmin')
            ->times();
    });

    $ImportUpdateLoyaltyPoints = new ImportUpdateLoyaltyPoints();
    $ImportUpdateLoyaltyPoints->save($memberData, $importRecord);
    $this->assertTrue(true);
});

function getMemberLoyaltyPointUpdateData(): array
{
    return [
        'card_number' => '123456789',
        'loyalty_points' => 234,
        'reasons' => 'abcd',
    ];
}
