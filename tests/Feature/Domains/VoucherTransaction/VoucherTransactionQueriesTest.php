<?php

declare(strict_types=1);

use App\Domains\VoucherTransaction\Enums\VoucherTransactionActionTypes;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Company;
use App\Models\Voucher;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->voucher = Voucher::factory()->create();

    $this->voucherTransactionQueries = new VoucherTransactionQueries();
});

test('New voucher Transaction can be added', function (): void {
    $voucherId = Voucher::factory()->create()->id;

    $this->voucherTransactionQueries->addNew(
        $voucherId,
        VoucherTransactionActionTypes::CANCELLED->value,
        now()->format('Y-m-d H:i:s'),
        null,
        null
    );

    $this->assertDatabaseHas('voucher_transactions', [
        'voucher_id' => $voucherId,
        'action_type_id' => VoucherTransactionActionTypes::CANCELLED->value,
    ]);
});

test('New voucher Transaction can be added when void sale id not pass', function (): void {
    $voucherId = Voucher::factory()->create()->id;

    $this->voucherTransactionQueries->addNew(
        $voucherId,
        VoucherTransactionActionTypes::CANCELLED->value,
        now()->format('Y-m-d H:i:s'),
        null,
        null
    );

    $this->assertDatabaseHas('voucher_transactions', [
        'voucher_id' => $voucherId,
        'action_type_id' => VoucherTransactionActionTypes::CANCELLED->value,
    ]);
});
