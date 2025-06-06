<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\OrderCreditNote\OrderCreditNoteQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\OrderCreditNote;
use App\Models\OrderReturn;
use App\Models\StoreManager;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Order Test',
    ]);

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->getKey(),
    ]);

    $this->storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employee->getKey(),
    ]);

    $this->location = Location::factory()->create([
        'company_id' => $this->company->getKey(),
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->getKey(),
        'created_location_id' => $this->location->getKey(),
    ]);

    $this->orderReturn = OrderReturn::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'original_order_id' => null,
    ]);

    $this->orderCreditNoteQueries = new OrderCreditNoteQueries();
});

test('addNew method creates new order credit note', function (): void {
    $response = $this->orderCreditNoteQueries->addNew(
        $this->storeManager->getKey(),
        $this->location->getKey(),
        $this->orderReturn->getKey(),
        10,
        $this->member->getKey(),
        null
    );

    expect($response)->toBeInstanceOf(OrderCreditNote::class);
    assertDatabaseHas(OrderCreditNote::class, [
        'id' => $response->getKey(),
        'order_return_id' => $this->orderReturn->getKey(),
    ]);
});

test(
    'the updateMember method update the order credit note queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $orderCreditNote = OrderCreditNote::factory()->create([
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'order_return_id' => $this->orderReturn->getKey(),
            'member_id' => $this->member->getKey(),
        ]);

        $this->assertDatabaseHas(OrderCreditNote::class, [
            'id' => $orderCreditNote->getKey(),
            'member_id' => $orderCreditNote->member_id,
        ]);

        $this->orderCreditNoteQueries->updateMember($orderCreditNote->member_id, $member->getKey());

        $this->assertDatabaseHas(OrderCreditNote::class, [
            'id' => $orderCreditNote->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);
