<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\HappyHourDiscountTransaction\HappyHourDiscountTransactionQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\HappyHourDiscount;
use App\Models\HappyHourDiscountTransaction;
use App\Models\Location;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->cashier = Cashier::factory()->create();
    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $this->counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $this->counter->id,
        'cashier_id' => $this->cashier->id,
    ]);

    $this->happyHourDiscount = HappyHourDiscount::factory()->create([
        'location_id' => $this->location->id,
        'company_id' => $this->companyId,
        'name' => 'Abc',
    ]);

    $this->happyHourDiscountTransaction = HappyHourDiscountTransaction::factory()->create([
        'happy_hour_discount_id' => $this->happyHourDiscount->id,
        'counter_update_id' => $this->counterUpdate->id,
    ]);

    $this->happyHourDiscount->happyHourDiscountUpdate = $this->happyHourDiscountTransaction;

    $this->happyHourDiscountTransactionQueries = new HappyHourDiscountTransactionQueries();
});

test('doesOfflineIdExist method returns as expected', function (): void {
    $response = $this->happyHourDiscountTransactionQueries->doesOfflineIdExist(
        $this->happyHourDiscountTransaction->offline_id,
        $this->companyId
    );
    $this->assertTrue($response);

    $response = $this->happyHourDiscountTransactionQueries->doesOfflineIdExist('asasassff', $this->companyId);
    $this->assertFalse($response);
});

test('generateUniqueOfflineId method returns unique offlineId as expected', function (): void {
    $response = $this->happyHourDiscountTransactionQueries->generateUniqueOfflineId();
    expect($response)->not->toEqual($this->happyHourDiscountTransaction->offline_id);
});

test('addNew method create new happy hour discount update', function (): void {
    $happyHourDiscount = HappyHourDiscount::factory()->create();
    $happyHourDiscountDetails['counter_update_id'] = $this->counterUpdate->id;
    $happyHourDiscountDetails['offline_id'] = 'XYZ123';
    $happyHourDiscountDetails['authorizer_id'] = 1;
    $happyHourDiscountDetails['authorizer_type'] = ModelMapping::ADMIN->value;
    $happyHourDiscountDetails['happened_at'] = now()->format('Y-m-d H:i:s');

    $this->happyHourDiscountTransactionQueries->addNew($happyHourDiscount->id, $happyHourDiscountDetails);
    $this->assertDatabaseHas('happy_hour_discount_transactions', [
        'happy_hour_discount_id' => $happyHourDiscount->id,
        'counter_update_id' => $happyHourDiscountDetails['counter_update_id'],
        'offline_id' => $happyHourDiscountDetails['offline_id'],
        'authorizer_id' => $happyHourDiscountDetails['authorizer_id'],
        'authorizer_type' => $happyHourDiscountDetails['authorizer_type'],
        'happened_at' => $happyHourDiscountDetails['happened_at'],
    ]);
});
