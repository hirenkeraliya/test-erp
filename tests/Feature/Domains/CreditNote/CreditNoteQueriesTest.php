<?php

declare(strict_types=1);

use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\CancelCreditSale;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\CreditNoteUse;
use App\Models\Location;
use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use Carbon\Carbon;
use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->location = Location::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'store@company.test',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->counter = Counter::factory()->create([
        'location_id' => $this->location->id,
        'name' => 'Counter 1',
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->creditNoteQueries = new CreditNoteQueries();
});

test(
    'the getPaginatedListOfActiveCreditNotes method returns the list of paginated credit notes',
    function (): void {
        $cashier = Cashier::factory()->create([
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $this->counter->id,
            'cashier_id' => $cashier->id,
        ]);

        $creditNote = CreditNote::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $filterData = [
            'per_page' => 1,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => null,
            'employee_id' => null,
            'member_id' => null,
            'after_updated_at' => null,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): Cashier => $cashier);

        $response = $this->creditNoteQueries->getPaginatedListOfActiveCreditNotes(
            $filterData,
            $this->company->id,
            $this->location->id
        );

        $this->assertEquals(1, $response->count());

        expect($response->first()->toArray())
            ->toHaveKey('counter_update_id', $creditNote->counter_update_id)
            ->toHaveKey('sale_return_id', $creditNote->sale_return_id)
            ->toHaveKey('status', $creditNote->status);
    }
);

test('Credit Note can be added', function (): void {
    $creditNote = CreditNote::factory()->make();
    $happenedAt = now()->format('Y-m-d H:i:s');

    $creditNoteQueries = new CreditNoteQueries();
    $creditNote = $creditNoteQueries->addNew(
        $creditNote->counter_update_id,
        $creditNote->sale_return_id,
        '00001',
        20,
        $happenedAt,
        10,
        $creditNote->member_id
    );

    expect($creditNote->toArray())
        ->toHaveKey('counter_update_id', $creditNote->counter_update_id)
        ->toHaveKey('sale_return_id', $creditNote->sale_return_id)
        ->toHaveKey('total_amount', 20.00)
        ->toHaveKey('status', 1);

    /** @var Carbon $happenedAtFormat */
    $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);
    $expiryDate = $happenedAtFormat->addDays(10)->format('Y-m-d');

    $this->assertDatabaseHas('credit_notes', [
        'counter_update_id' => $creditNote->counter_update_id,
        'sale_return_id' => $creditNote->sale_return_id,
        'expiry_date' => $expiryDate,
        'total_amount' => 20.00,
        'available_amount' => 20.00,
        'status' => 1,
    ]);
});

test('Credit Note can be fetched', function (): void {
    $creditNote = CreditNote::factory()->create();

    $response = $this->creditNoteQueries->getById($creditNote->id);

    expect($response->toArray())
        ->toHaveKey('id', $creditNote->id)
        ->toHaveKey('total_amount', $creditNote->total_amount)
        ->toHaveKey('available_amount', $creditNote->available_amount)
        ->toHaveKey('status', $creditNote->status);
});

test('Credit Note mark as refunded', function (): void {
    $creditNote = CreditNote::factory()->create([
        'available_amount' => 100,
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);

    $this->creditNoteQueries->markAsRefunded($creditNote);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'available_amount' => 0,
        'status' => CreditNoteStatuses::REFUNDED->value,
    ]);
});

test('the getByIds method returns the credit notes list as expected', function (): void {
    $counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $creditNote = CreditNote::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'available_amount' => 100,
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);

    $response = $this->creditNoteQueries->getByIds([$creditNote->id], $this->location->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $creditNote->id)
        ->toHaveKey('available_amount', $creditNote->available_amount)
        ->toHaveKeys(['mismatches', 'counter_update']);
});

test(
    'the decreaseAvailableAmountAndMarkAsUsed method updates the credit note available amount for refund',
    function (): void {
        $creditNote = CreditNote::factory()->create([
            'available_amount' => 100,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);

        $this->creditNoteQueries->decreaseAvailableAmountAndMarkAsUsed(
            $creditNote,
            (float) $creditNote->available_amount
        );

        $this->assertDatabaseHas('credit_notes', [
            'id' => $creditNote->id,
            'available_amount' => 0,
            'status' => CreditNoteStatuses::USED->value,
        ]);
    }
);

test(
    'the getActiveWithExpirationDue method returns the list of credit notes',
    function (): void {
        $cashier = Cashier::factory()->create([
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $this->counter->id,
            'cashier_id' => $cashier->id,
        ]);

        $creditNote = CreditNote::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => CreditNoteStatuses::ACTIVE->value,
            'expiry_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response = $this->creditNoteQueries->getActiveWithExpirationDue();

        expect($response->first()->toArray())
            ->toHaveKey('id', $creditNote->id)
            ->toHaveKey('total_amount', $creditNote->total_amount)
            ->toHaveKey('available_amount', $creditNote->available_amount)
            ->toHaveKey('status', $creditNote->status);
    }
);

test('Credit Note mark as expired', function (): void {
    $creditNote = CreditNote::factory()->create([
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);

    $this->creditNoteQueries->markAseExpired($creditNote);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'status' => CreditNoteStatuses::EXPIRED->value,
    ]);
});

test(
    'the getCreditNoteDetails method returns the credit note details as expected',
    function (): void {
        $cashier = Cashier::factory()->create([
            'username' => 'Cashier',
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $this->counter->id,
            'cashier_id' => $cashier->id,
        ]);

        $creditNote = CreditNote::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'status' => CreditNoteStatuses::USED->value,
        ]);

        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $cashier);

        $response = $this->creditNoteQueries->getCreditNoteDetails(
            $this->location->id,
            $this->company->id,
            $creditNote->id
        );

        $this->assertEquals(1, $response->count());

        expect($response->toArray())
            ->toHaveKey('counter_update_id', $creditNote->counter_update_id)
            ->toHaveKey('sale_return_id', $creditNote->sale_return_id)
            ->toHaveKey('status', $creditNote->status);
    }
);

test('the getPaginatedListByCompanyWithRelations method returns the credit notes list as expected', function (): void {
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $this->counter->id,
    ]);
    $creditNote = CreditNote::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'member_id' => $this->member->id,
    ]);
    CreditNoteUse::factory()->create([
        'credit_note_id' => $creditNote->id,
    ]);

    $filterData = [
        'per_page' => 10,
        'search_text' => null,
        'sort_by' => null,
        'location_ids' => null,
        'counter_ids' => [],
        'cashier_id' => null,
        'date_range' => null,
        'member_id' => null,
        'status_id' => null,
        'employee_id' => null,
        'e_invoice_submitted' => null,
    ];

    $response = $this->creditNoteQueries->getPaginatedListByCompanyWithRelations($filterData, $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKeys(
            [
                'id',
                'sale_return_id',
                'cancel_layaway_sale_id',
                'expiry_date',
                'total_amount',
                'available_amount',
                'status',
                'uses',
                'counter_update',
                'credit_note_refund',
                'credit_note_expiration',
                'mismatches',
                'member_id',
            ]
        );
});

test('the getSumOfAvailableAmountByCompany method returns proper response', function (): void {
    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $this->counter->id,
    ]);
    $creditNote = CreditNote::factory()->create([
        'counter_update_id' => $counterUpdate->id,
        'member_id' => $this->member->id,
    ]);
    CreditNoteUse::factory()->create([
        'credit_note_id' => $creditNote->id,
    ]);

    $filterData = [
        'per_page' => 10,
        'search_text' => null,
        'sort_by' => null,
        'location_ids' => null,
        'counter_ids' => [],
        'cashier_id' => null,
        'date_range' => null,
        'member_id' => null,
        'status_id' => null,
        'employee_id' => null,
        'e_invoice_submitted' => null,
    ];

    $response = $this->creditNoteQueries->getSumOfAvailableAmountByCompany($filterData, $this->company->id);
    expect($response)->toBe($creditNote->available_amount);
});

test(
    'the getPaginatedListByCompanyWithRelationsForStoreManager method returns the credit notes list as expected',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $this->counter->id,
        ]);
        $creditNote = CreditNote::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'member_id' => $this->member->id,
        ]);
        CreditNoteUse::factory()->create([
            'credit_note_id' => $creditNote->id,
        ]);

        $filterData = [
            'per_page' => 10,
            'search_text' => null,
            'sort_by' => null,
            'cashier_id' => null,
            'counter_ids' => null,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
        ];

        $response = $this->creditNoteQueries->getPaginatedListByCompanyWithRelationsForStoreManager(
            $filterData,
            $this->company->id,
            $this->location->id
        );

        expect($response->first()->toArray())
        ->toHaveKeys(
            [
                'id',
                'sale_return_id',
                'cancel_layaway_sale_id',
                'expiry_date',
                'total_amount',
                'available_amount',
                'status',
                'uses',
                'counter_update',
                'credit_note_refund',
                'credit_note_expiration',
                'mismatches',
                'member_id',
            ]
        );
    }
);

test(
    'the getCreditNoteListByCompanyWithRelationsForExport method returns the credit notes list as expected',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $this->counter->id,
        ]);
        $creditNote = CreditNote::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'member_id' => $this->member->id,
        ]);
        CreditNoteUse::factory()->create([
            'credit_note_id' => $creditNote->id,
        ]);

        $filterData = [
            'per_page' => 10,
            'search_text' => null,
            'sort_by' => null,
            'location_ids' => null,
            'counter_ids' => [],
            'cashier_id' => null,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
            'employee_id' => null,
            'e_invoice_submitted' => null,
        ];

        $response = $this->creditNoteQueries->getCreditNoteListByCompanyWithRelationsForExport(
            $filterData,
            $this->company->id
        );

        expect($response->first()->toArray())
        ->toHaveKeys(
            [
                'id',
                'sale_return_id',
                'cancel_layaway_sale_id',
                'expiry_date',
                'total_amount',
                'available_amount',
                'status',
                'counter_update',
                'member_id',
            ]
        );
    }
);

test('incrementAvailableAmountAndActivate update the available note and status', function (): void {
    $creditNote = CreditNote::factory()->create([
        'available_amount' => 100,
        'status' => CreditNoteStatuses::USED->value,
    ]);

    $this->creditNoteQueries->incrementAvailableAmountAndActivate($creditNote->id, 50);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'available_amount' => 150,
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);
});

test(
    'the getCreditNoteListByCompanyWithRelationsForExportInStoreManagerPanel method returns the credit notes list as expected',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $this->counter->id,
        ]);
        $creditNote = CreditNote::factory()->create([
            'counter_update_id' => $counterUpdate->id,
            'member_id' => $this->member->id,
        ]);
        CreditNoteUse::factory()->create([
            'credit_note_id' => $creditNote->id,
        ]);

        $filterData = [
            'per_page' => 10,
            'search_text' => null,
            'sort_by' => null,
            'cashier_id' => null,
            'counter_ids' => null,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
        ];

        $response = $this->creditNoteQueries->getCreditNoteListByCompanyWithRelationsForExportInStoreManagerPanel(
            $filterData,
            $this->company->id,
            $this->location->id
        );

        expect($response->first()->toArray())
        ->toHaveKeys(
            [
                'id',
                'sale_return_id',
                'cancel_layaway_sale_id',
                'expiry_date',
                'total_amount',
                'available_amount',
                'status',
                'counter_update',
                'member_id',
            ]
        );
    }
);

test('Credit Note can be added by cancel layaway sale', function (): void {
    $creditNote = CreditNote::factory()->make();
    $happenedAt = now()->format('Y-m-d H:i:s');

    $creditNoteQueries = new CreditNoteQueries();
    $creditNote = $creditNoteQueries->addNewForCancelLayawaySale(
        $creditNote->counter_update_id,
        $creditNote->cancel_layaway_sale_id,
        '00001',
        20,
        $happenedAt,
        10,
        $creditNote->member_id
    );

    expect($creditNote->toArray())
        ->toHaveKey('counter_update_id', $creditNote->counter_update_id)
        ->toHaveKey('cancel_layaway_sale_id', $creditNote->cancel_layaway_sale_id)
        ->toHaveKey('total_amount', 20.00)
        ->toHaveKey('status', 1);

    /** @var Carbon $happenedAtFormat */
    $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

    $expiryDate = $happenedAtFormat->addDays(10)->format('Y-m-d');

    $this->assertDatabaseHas('credit_notes', [
        'counter_update_id' => $creditNote->counter_update_id,
        'cancel_layaway_sale_id' => $creditNote->cancel_layaway_sale_id,
        'expiry_date' => $expiryDate,
        'total_amount' => 20.00,
        'available_amount' => 20.00,
        'status' => 1,
    ]);
});

test('Credit Note can be added by cancel credit sale', function (): void {
    $cancelCreditSale = CancelCreditSale::factory()->create();

    $creditNote = CreditNote::factory()->create([
        'cancel_credit_sale_id' => $cancelCreditSale->id,
    ]);

    $happenedAt = now()->format('Y-m-d H:i:s');

    $creditNoteQueries = new CreditNoteQueries();
    $creditNote = $creditNoteQueries->addNewForCancelCreditSale(
        $creditNote->counter_update_id,
        $creditNote->cancel_credit_sale_id,
        '00001',
        20,
        $happenedAt,
        10,
        $creditNote->member_id
    );

    expect($creditNote->toArray())
        ->toHaveKey('counter_update_id', $creditNote->counter_update_id)
        ->toHaveKey('cancel_credit_sale_id', $creditNote->cancel_credit_sale_id)
        ->toHaveKey('total_amount', 20.00)
        ->toHaveKey('status', 1);

    /** @var Carbon $happenedAtFormat */
    $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $happenedAt);

    $expiryDate = $happenedAtFormat->addDays(10)->format('Y-m-d');

    $this->assertDatabaseHas('credit_notes', [
        'counter_update_id' => $creditNote->counter_update_id,
        'cancel_credit_sale_id' => $creditNote->cancel_credit_sale_id,
        'expiry_date' => $expiryDate,
        'total_amount' => 20.00,
        'available_amount' => 20.00,
        'status' => 1,
    ]);
});

test('digitalInvoiceUpdate can be update credit sale', function (): void {
    $cancelCreditSale = CancelCreditSale::factory()->create();

    $creditNote = CreditNote::factory()->create([
        'cancel_credit_sale_id' => $cancelCreditSale->id,
    ]);

    $creditNoteQueries = new CreditNoteQueries();
    $creditNoteQueries->digitalInvoiceUpdate($creditNote->id);

    $this->assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'digital_invoice_submitted' => true,
    ]);
});

test('getCreditNoteReturnByStoreIdCounterId can return credit sale', function (): void {
    $location = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $member = Member::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $counter = Counter::factory()->create([
        'location_id' => $location->id,
    ]);

    $counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $counter->id,
    ]);

    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdate->id,
    ]);

    $product = Product::factory()->create();

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $saleReturn = SaleReturn::factory()->create([
        'original_sale_id' => $sale->id,
        'counter_update_id' => $counterUpdate->id,
        'member_id' => $member->id,
    ]);

    $saleReturnReason = SaleReturnReason::factory()->create();

    $saleReturnItem = SaleReturnItem::factory()->create([
        'product_id' => $product->id,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
    ]);

    $saleReturn->saleReturnItems = collect($saleReturnItem);
    $cancelCreditSale = CancelCreditSale::factory()->create();

    $creditNote = CreditNote::factory()->create([
        'cancel_credit_sale_id' => $cancelCreditSale->id,
        'sale_return_id' => $saleReturn->id,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $creditNoteQueries = new CreditNoteQueries();
    $response = $creditNoteQueries->getCreditNoteReturnByStoreIdCounterId(
        $saleReturn->offline_sale_return_id,
        $location->id,
        $counter->id
    );

    expect($response->toArray())
        ->toHaveKey('id', $creditNote->id)
        ->toHaveKey('digital_invoice_submitted', $creditNote->digital_invoice_submitted);
});

test(
    'the updateMember method update the credit note queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $creditNote = CreditNote::factory()->create();

        $this->assertDatabaseHas(CreditNote::class, [
            'id' => $creditNote->getKey(),
            'member_id' => $creditNote->member_id,
        ]);

        $creditNoteQueries = new CreditNoteQueries();
        $creditNoteQueries->updateMember($creditNote->member_id, $member->getKey());

        $this->assertDatabaseHas(CreditNote::class, [
            'id' => $creditNote->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);
