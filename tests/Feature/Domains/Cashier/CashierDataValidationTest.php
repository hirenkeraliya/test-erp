<?php

declare(strict_types=1);

use App\Domains\Cashier\DataObjects\CashierData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Validation\ValidationException;

test('cashier with the same username cannot be added', function (): void {
    $companyId = Company::factory()->create()->id;
    setCompanyIdInSession($companyId);
    Cashier::factory()->create([
        'username' => 'ABCD',
    ]);
    CashierData::validate(cashierData());
})->throws(ValidationException::class);

function cashierData(): array
{
    $location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);

    return [
        'username' => 'ABCD',
        'pin' => '1234',
        'employee_id' => 1,
        'cashier_group_id' => 1,
        'location_ids' => [$location->id],
    ];
}
