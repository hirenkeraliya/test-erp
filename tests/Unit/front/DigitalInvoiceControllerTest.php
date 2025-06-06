<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\DigitalInvoice\DataObjects\DigitalInvoiceApiData;
use App\Domains\DigitalInvoice\DigitalInvoiceQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Http\Controllers\Front\DigitalInvoiceController;
use App\Models\Company;
use App\Models\Location;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

test(
    'It calls the store query method of the digitalInvoiceQueries class and e-invoice details already submit and redirect',
    function (): void {
        $data = [
            'buyer_name' => 'test',
            'buyer_tin' => 'test',
            'buyer_identification_number' => '123456789',
            'buyer_sst_number' => 'test',
            'buyer_email' => null,
            'buyer_address' => 'test',
            'buyer_contact' => '9723233232',
        ];
        $locationId = 1;
        $counterId = 1;
        $type = SequenceTypes::SS->name;
        $offlineId = '123';

        $sale = Sale::factory()->make([
            'id' => 1,
            'offline_sale_id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'digital_invoice_submitted' => true,
        ]);
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $location->company = new Company([
            'enable_e_invoice' => true,
        ]);

        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getCompanyOfStore')
                ->once()
                ->andReturn($location);
        });

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByStoreIdCounterId')
                ->once()
                ->andReturn($sale);
        });

        $digitalInvoiceController = resolve(DigitalInvoiceController::class);
        $response = $digitalInvoiceController->store(
            new DigitalInvoiceApiData(...$data),
            $locationId,
            $counterId,
            $type,
            $offlineId
        );
        expect($response)->toBeInstanceOf(RedirectResponse::class);
        expect($response->getTargetUrl())->toBe(route('front.digital_invoice.digital_invoice_thank_you', true));
    }
);

test(
    'It calls the store query method of the digitalInvoiceQueries class and e-invoice details store and redirect',
    function (): void {
        $data = [
            'buyer_name' => 'test',
            'buyer_tin' => 'test',
            'buyer_identification_number' => '123456789',
            'buyer_sst_number' => 'test',
            'buyer_email' => null,
            'buyer_address' => 'test',
            'buyer_contact' => '9723233232',
        ];
        $locationId = 1;
        $counterId = 1;
        $type = SequenceTypes::SS->name;
        $offlineId = '123';

        $dataApi = new DigitalInvoiceApiData(...$data);

        $location = Location::factory()->make([
            'id' => $locationId,
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $location->company = new Company([
            'enable_e_invoice' => true,
        ]);

        $sale = Sale::factory()->make([
            'id' => 1,
            'offline_sale_id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'digital_invoice_submitted' => false,
        ]);
        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getCompanyOfStore')
                ->once()
                ->andReturn($location);
        });
        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByStoreIdCounterId')
                ->once()
                ->andReturn($sale);
            $mock->shouldReceive('digitalInvoiceUpdate')
                ->once();
        });
        $data['module_id'] = $sale->id;
        $data['module_type'] = ModelMapping::getCaseName($sale::class);

        $this->mock(DigitalInvoiceQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('addNew')
                ->with($data)
                ->once();
        });

        $digitalInvoiceController = resolve(DigitalInvoiceController::class);
        $response = $digitalInvoiceController->store($dataApi, $locationId, $counterId, $type, $offlineId);

        expect($response)->toBeInstanceOf(RedirectResponse::class);
        expect($response->getTargetUrl())->toBe(route('front.digital_invoice.digital_invoice_thank_you'));
    }
);

test(
    'It calls the store query method of the digitalInvoiceQueries class and disable e invoice and throw exceptions',
    function (): void {
        $data = [
            'buyer_name' => 'test',
            'buyer_tin' => 'test',
            'buyer_identification_number' => '123456789',
            'buyer_sst_number' => 'test',
            'buyer_email' => null,
            'buyer_address' => 'test',
            'buyer_contact' => '9723233232',
        ];
        $locationId = 1;
        $counterId = 1;
        $type = SequenceTypes::SS->name;
        $offlineId = '123';

        $location = Location::factory()->make([
            'id' => $locationId,
            'company_id' => 1,
            'country_id' => 1,
        ]);

        $location->company = new Company([
            'enable_e_invoice' => false,
        ]);

        $dataApi = new DigitalInvoiceApiData(...$data);

        $sale = Sale::factory()->make([
            'id' => 1,
            'offline_sale_id' => 1,
            'member_id' => 1,
            'counter_update_id' => 1,
            'digital_invoice_submitted' => false,
        ]);
        $this->mock(LocationQueries::class, function ($mock) use ($location): void {
            $mock->shouldReceive('getCompanyOfStore')
                ->once()
                ->andReturn($location);
        });

        $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
            $mock->shouldReceive('getSaleByStoreIdCounterId')
                ->once()
                ->andReturn($sale);
        });
        $data['module_id'] = $sale->id;
        $data['module_type'] = ModelMapping::getCaseName($sale::class);

        $digitalInvoiceController = resolve(DigitalInvoiceController::class);
        $digitalInvoiceController->store($dataApi, $locationId, $counterId, $type, $offlineId);
    }
)->throws(HttpException::class);
