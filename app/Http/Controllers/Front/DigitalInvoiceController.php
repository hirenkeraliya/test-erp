<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Domains\BookingPayment\BookingPaymentQueries;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\DigitalInvoice\DataObjects\DigitalInvoiceApiData;
use App\Domains\DigitalInvoice\DigitalInvoiceQueries;
use App\Domains\DigitalInvoice\Services\DigitalInvoiceService;
use App\Domains\Location\LocationQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\Sequence\Enums\SequenceTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SiteConfiguration;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class DigitalInvoiceController extends Controller
{
    public function __construct(
        protected DigitalInvoiceQueries $digitalInvoiceQueries
    ) {
    }

    public function index(
        int $locationId,
        int $counterId,
        string $type,
        string $offlineId
    ): Factory|View|RedirectResponse {
        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getCompanyLogoOfStore($locationId);

        /** @var Company $company */
        $company = $location->company;

        $record = $this->getModuleRecord($locationId, $counterId, $type, $offlineId);

        if (null === $record || ! $company->enable_e_invoice) {
            abort(404);
        }

        if ($record->digital_invoice_submitted) {
            return to_route('front.digital_invoice.digital_invoice_thank_you', true);
        }

        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);

        $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();

        $themeColor = $getSiteConfigurationTheme instanceof SiteConfiguration ? ThemeColors::getHexColor(
            $getSiteConfigurationTheme->value
        ) : ThemeColors::getHexColor(ThemeColors::AMARANTH_DEEP_PURPLE->value);

        $sequentTypes = SequenceTypes::formattedForDigitalInvoice();

        return view('front/digital_invoice/add', [
            'companyLogo' => $company->getDiskBasedFirstMediaUrl('light_logo'),
            'companyName' => $company->name,
            'themeColor' => $themeColor,
            'sequenceTypes' => $sequentTypes,
            'locationId' => $locationId,
            'counterId' => $counterId,
            'type' => $type,
            'offlineId' => $offlineId,
        ]);
    }

    public function store(
        DigitalInvoiceApiData $digitalInvoiceApiData,
        int $locationId,
        int $counterId,
        string $type,
        string $offlineId
    ): RedirectResponse {
        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getCompanyOfStore($locationId);

        /** @var Company $company */
        $company = $location->company;
        $record = $this->getModuleRecord($locationId, $counterId, $type, $offlineId);
        if (null === $record || ! $company->enable_e_invoice) {
            abort(404);
        }

        if ($record->digital_invoice_submitted) {
            return to_route('front.digital_invoice.digital_invoice_thank_you', true);
        }

        $digitalInvoiceDetails = $digitalInvoiceApiData->all();
        $digitalInvoiceDetails['module_id'] = $record->id;
        $digitalInvoiceDetails['module_type'] = ModelMapping::getCaseName($record::class);
        $this->digitalInvoiceQueries->addNew($digitalInvoiceDetails);

        $digitalInvoiceService = resolve(DigitalInvoiceService::class);
        $moduleObject = $digitalInvoiceService->getObject(ModelMapping::getCaseName($record::class));
        $moduleObject->digitalInvoiceUpdate($record->id);

        return to_route('front.digital_invoice.digital_invoice_thank_you');
    }

    public function thankYou(bool $isSubmitted = false): Factory|View
    {
        return view('front/digital_invoice/thank_you', [
            'isSubmitted' => $isSubmitted,
        ]);
    }

    private function getModuleRecord(
        int $locationId,
        int $counterId,
        string $type,
        string $offlineId
    ): Sale|BookingPayment|SaleReturn|CreditNote|null {
        if ($type === SequenceTypes::SS->name) {
            $saleQueries = resolve(SaleQueries::class);

            return $saleQueries->getSaleByStoreIdCounterId($offlineId, $locationId, $counterId);
        }

        if ($type === SequenceTypes::BP->name) {
            $bookingPaymentQueries = resolve(BookingPaymentQueries::class);

            return $bookingPaymentQueries->getBookingPaymentByStoreIdCounterId($offlineId, $locationId, $counterId);
        }

        if ($type === SequenceTypes::SR->name) {
            $saleReturnQueries = resolve(SaleReturnQueries::class);

            return $saleReturnQueries->getSaleReturnByStoreIdCounterId($offlineId, $locationId, $counterId);
        }

        if ($type === SequenceTypes::CN->name) {
            $creditNoteQueries = resolve(CreditNoteQueries::class);

            return $creditNoteQueries->getCreditNoteReturnByStoreIdCounterId($offlineId, $locationId, $counterId);
        }

        throw new InvalidArgumentException('Invalid module name: ' . $type);
    }
}
