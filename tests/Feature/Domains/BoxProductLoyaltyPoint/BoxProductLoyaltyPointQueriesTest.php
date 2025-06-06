<?php

declare(strict_types=1);

use App\Domains\BoxProductLoyaltyPoint\BoxProductLoyaltyPointQueries;
use App\Models\BoxProduct;
use App\Models\BoxProductLoyaltyPoint;
use App\Models\Membership;

beforeEach(function (): void {
    $this->boxProductLoyaltyPointQueries = new BoxProductLoyaltyPointQueries();
});

test('a box product loyalty points can be added', function (): void {
    $boxProduct = BoxProduct::factory()->create()->id;

    $memberShip = Membership::factory()->create()->id;

    $boxProductLoyaltyPointRecord = BoxProductLoyaltyPoint::factory()->make([
        'box_product_id' => $boxProduct,
        'membership_id' => $memberShip,
    ]);

    $this->boxProductLoyaltyPointQueries->addNew($boxProductLoyaltyPointRecord->toArray());

    $this->assertDatabaseHas('box_product_loyalty_points', [
        'box_product_id' => $boxProductLoyaltyPointRecord->box_product_id,
        'membership_id' => $boxProductLoyaltyPointRecord->membership_id,
        'points' => $boxProductLoyaltyPointRecord->points,
    ]);
});
