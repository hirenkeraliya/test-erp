<?php

declare(strict_types=1);

use App\Domains\Member\DataObjects\UpdateLoyaltyPointData;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('company validation works.', function (): void {
    $request = new Request([
        'loyalty_points' => '',
        'remarks' => '',
    ]);

    UpdateLoyaltyPointData::validate($request);
})->throws(ValidationException::class);

test('admin can update member loyalty point.', function (): void {
    $request = new Request([
        'loyalty_points' => 10,
        'remarks' => 'Test',
    ]);

    UpdateLoyaltyPointData::validate($request);
    $this->assertTrue(true);
});
