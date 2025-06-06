<?php

declare(strict_types=1);

use App\Domains\DigitalInvoice\DigitalInvoiceQueries;

beforeEach(function (): void {
    $this->digitalInvoiceQueries = resolve(DigitalInvoiceQueries::class);
});

test('addNew method is location the digital invoice details', function (): void {
    $data = [
        'module_id' => 1,
        'module_type' => 'SALE',
        'buyer_name' => 'test',
        'buyer_tin' => 'test',
        'buyer_identification_number' => '123456789',
        'buyer_sst_number' => 'test',
        'buyer_email' => null,
        'buyer_address' => 'test',
        'buyer_contact' => '9723233232',
    ];
    $this->digitalInvoiceQueries->addNew($data);
    $this->assertDatabaseHas('digital_invoices', $data);
});
