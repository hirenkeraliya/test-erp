<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\DigitalInvoice\DataObjects\DigitalInvoiceData;
use App\Domains\DigitalInvoice\DigitalInvoiceQueries;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Admin\DigitalInvoiceController;
use App\Models\Sale;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'It calls the digitalInvoiceStore query method of the digitalInvoiceQueries class with eInvoiceEnable true and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

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

        $sale = Sale::factory()->make([
            'id' => 1,
            'offline_sale_id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
        ]);

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('digitalInvoiceUpdate')
                ->once()
                ->andReturn($sale);
        });
        $this->mock(DigitalInvoiceQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($data);
        });
        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getEnableEInvoiceById')
                ->once()
                ->andReturn(true);
        });
        $digitalInvoiceController = resolve(DigitalInvoiceController::class);
        $digitalInvoiceController->digitalInvoiceStore(new DigitalInvoiceData(...$data));
        $this->assertTrue(true);
    }
);

test(
    'It calls the digitalInvoiceStore query method of the digitalInvoiceQueries class with eInvoiceEnable false and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

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

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getEnableEInvoiceById')
                ->once()
                ->andReturn(false);
        });
        $digitalInvoiceController = resolve(DigitalInvoiceController::class);
        $digitalInvoiceController->digitalInvoiceStore(new DigitalInvoiceData(...$data));
    }
)->throws(HttpException::class);
