<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Company\CompanyQueries;
use App\Domains\DigitalInvoice\DataObjects\DigitalInvoiceData;
use App\Domains\DigitalInvoice\DigitalInvoiceQueries;
use App\Domains\DigitalInvoice\Services\DigitalInvoiceService;
use App\Http\Controllers\Controller;

class DigitalInvoiceController extends Controller
{
    public function __construct(
        protected DigitalInvoiceQueries $digitalInvoiceQueries
    ) {
    }

    public function digitalInvoiceStore(DigitalInvoiceData $digitalInvoiceData): void
    {
        $companyId = session('admin_company_id');
        $companyQueries = resolve(CompanyQueries::class);
        $allowEInvoice = $companyQueries->getEnableEInvoiceById($companyId);
        if (! $allowEInvoice) {
            abort(412, 'Company does not allowed to submit E-Invoice form.');
        }

        $digitalInvoiceDetails = $digitalInvoiceData->all();
        $this->digitalInvoiceUpdateByModule($digitalInvoiceData);
        $this->digitalInvoiceQueries->addNew($digitalInvoiceDetails);
    }

    private function digitalInvoiceUpdateByModule(DigitalInvoiceData $digitalInvoiceData): void
    {
        $digitalInvoiceService = resolve(DigitalInvoiceService::class);
        $moduleObject = $digitalInvoiceService->getObject($digitalInvoiceData->module_type);
        $moduleObject->digitalInvoiceUpdate($digitalInvoiceData->module_id);
    }
}
