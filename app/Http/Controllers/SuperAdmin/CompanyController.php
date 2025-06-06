<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\DataObjects\CompanyData;
use App\Domains\Company\DataObjects\CurrencyRateData;
use App\Domains\Company\Enums\BookingPaymentRefundTypes;
use App\Domains\Company\Enums\BookingPaymentUseTypes;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Company\Enums\CompanyStatuses;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Domains\Company\Jobs\ShareCompanyDetailsToPosAdminJob;
use App\Domains\Company\Resources\CompanyResource;
use App\Domains\Country\CountryQueries;
use App\Domains\CurrencyRate\CurrencyRateQueries;
use App\Domains\CurrencyRate\Resources\CurrencyRatesListResource;
use App\Domains\ExternalConnection\ExternalConnectionQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SaleChannel\SaleChannelQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyQueries $companyQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('companies/Index', [
            'statuses' => CompanyStatuses::getList(),
            'allStatuses' => CompanyStatuses::getFormattedArrayForStaticUse(),
        ]);
    }

    public function fetchCompanies(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
        ];

        $lengthAwarePaginator = $this->companyQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => CompanyResource::collection($lengthAwarePaginator),
        ];
    }

    public function create(): Response
    {
        $brandQueries = new BrandQueries();
        $countryQueries = new CountryQueries();

        return Inertia::render('companies/Manage', [
            'brands' => $brandQueries->getWithBasicColumns(),
            'countries' => $countryQueries->getList(),
            'commissionTypes' => CommissionTypes::formattedForSelection(),
            'discountApplicableTypes' => DiscountApplicableTypes::formattedForSelection(),
            'additionalDiscount' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'bookingPaymentUseTypes' => BookingPaymentUseTypes::formattedForSelection(),
            'partiallyPayment' => BookingPaymentUseTypes::PARTIALLY->value,
            'locationAssignmentTypes' => LocationAssignmentTypes::formattedForSelection(),
            'manualAssignment' => LocationAssignmentTypes::MANUAL_ASSIGNMENT->value,
            'locationAssignmentStaticDetails' => LocationAssignmentTypes::getFormattedArrayForStaticUse(),
            'bookingPaymentRefundTypes' => BookingPaymentRefundTypes::formattedForSelection(),
            'partiallyRefundPayment' => BookingPaymentRefundTypes::PARTIALLY->value,
        ]);
    }

    public function store(CompanyData $companyData): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $companyId = $this->companyQueries->addNew($companyData);
            DB::commit();

            ShareCompanyDetailsToPosAdminJob::dispatch($companyId)->onQueue(config('horizon.default_queue_name'));

            return to_route('super_admin.companies.index')
                ->with('success', 'Company added successfully.');
        } catch (Throwable $throwable) {
            Log::error('Add Company', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function edit(int $companyId, BrandQueries $brandQueries): Response
    {
        $locationQueries = resolve(LocationQueries::class);
        $countryQueries = resolve(CountryQueries::class);

        $company = $this->companyQueries->getByIdWithMediaAndBrands($companyId);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);
        $company['light_logo_url'] = $company->getDiskBasedFirstMediaUrl('light_logo');
        $company['dark_logo_url'] = $company->getDiskBasedFirstMediaUrl('dark_logo');
        $company['email_footer_logo_url'] = $company->getDiskBasedFirstMediaUrl('email_footer_logo');

        return Inertia::render('companies/Manage', [
            'brands' => $brandQueries->getWithBasicColumns(),
            'company' => $company,
            'countries' => $countryQueries->getList(),
            'commissionTypes' => CommissionTypes::formattedForSelection(),
            'discountApplicableTypes' => DiscountApplicableTypes::formattedForSelection(),
            'additionalDiscount' => DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value,
            'bookingPaymentUseTypes' => BookingPaymentUseTypes::formattedForSelection(),
            'partiallyPayment' => BookingPaymentUseTypes::PARTIALLY->value,
            'locationAssignmentTypes' => LocationAssignmentTypes::formattedForSelection(),
            'manualAssignment' => LocationAssignmentTypes::MANUAL_ASSIGNMENT->value,
            'locationAssignmentStaticDetails' => LocationAssignmentTypes::getFormattedArrayForStaticUse(),
            'locations' => $locations,
            'bookingPaymentRefundTypes' => BookingPaymentRefundTypes::formattedForSelection(),
            'partiallyRefundPayment' => BookingPaymentRefundTypes::PARTIALLY->value,
        ]);
    }

    public function update(
        CompanyData $companyData,
        int $companyId,
        LocationQueries $locationQueries
    ): RedirectResponse {
        $this->checkRequestDetails($companyData, $companyId, $locationQueries);

        DB::beginTransaction();

        try {
            $this->companyQueries->update($companyData, $companyId);

            DB::commit();

            ShareCompanyDetailsToPosAdminJob::dispatch($companyId)->onQueue(config('horizon.default_queue_name'));

            return to_route('super_admin.companies.index')
                ->with('success', 'Company updated successfully.');
        } catch (Throwable $throwable) {
            Log::error('Update Company', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            DB::rollBack();

            throw new RedirectBackWithErrorException('An error occurred. Please try again.');
        }
    }

    public function currencyRateUpdateView(int $companyId): Response
    {
        $currencyRateQueries = new CurrencyRateQueries();
        $companyQueries = new CompanyQueries();
        $company = $companyQueries->getCountryCurrencySymbol($companyId);
        /** @var Country $defaultCountry */
        $defaultCountry = $company->defaultCountry;
        /** @var Currency $baseCurrency */
        $baseCurrency = $defaultCountry->currency;

        $currencyRates = $currencyRateQueries->getByCompanyId($companyId, $baseCurrency->id);

        return Inertia::render('companies/currencyRateUpdateView', [
            'currencyRates' => CurrencyRatesListResource::collection($currencyRates),
            'companyId' => $companyId,
            'baseCurrency' => $baseCurrency,
            'currencyRateAutoUpdate' => $company->currency_rate_auto_update,
        ]);
    }

    public function currencyRateUpdate(CurrencyRateData $currencyRateData): RedirectResponse
    {
        $currencyRateQueries = resolve(CurrencyRateQueries::class);
        $currencyRateQueries->currencyRateUpdateByCompanyId($currencyRateData);

        return to_route('super_admin.companies.currency_rate_update', $currencyRateData->company_id)
                ->with('success', 'Currency rates updated successfully.');
    }

    public function currencyRateUpdateToggle(int $companyId): void
    {
        $this->companyQueries->toggleCurrencyUpdateRate($companyId);
    }

    public function resendVerificationEmail(int $companyId): RedirectResponse
    {
        $company = $this->companyQueries->getById($companyId);
        EmailVerificationJob::dispatch($company)->delay(now()->addSeconds(5))->onQueue('high');

        return to_route('super_admin.companies.index')
            ->with('success', 'The verification mail sent successfully.');
    }

    private function checkRequestDetails(
        CompanyData $companyData,
        int $companyId,
        LocationQueries $locationQueries
    ): void {
        $company = $this->companyQueries->getByIdWithBrands($companyId);

        $removedBrandIds = $company->brands->whereNotIn('id', $companyData->brand_ids);
        $storeHasBrands = $locationQueries->hasBrands($companyId, $removedBrandIds->pluck('id')->toArray());

        if ($storeHasBrands) {
            throw new RedirectWithErrorException(
                'super_admin.companies.index',
                'Some of the removed brands are used by one or more stores of the company and cannot be removed.'
            );
        }
    }

    public function archive(int $companyId): RedirectResponse
    {
        $this->companyQueries->delete($companyId);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannelQueries->setArchiveCompanyInactive($companyId, false);

        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $externalConnections = $externalConnectionQueries->getAll();

        foreach ($externalConnections as $externalConnection) {
            Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post(
                $externalConnection->url . '/api/external-connection/external-company-archive',
                [
                    'token' => $externalConnection->token,
                    'external_company_id' => $companyId,
                ]
            );
        }

        return to_route('super_admin.companies.index')->with('success', 'Company archived successfully.');
    }

    public function restore(int $companyId): RedirectResponse
    {
        $this->companyQueries->restore($companyId);

        $saleChannelQueries = resolve(SaleChannelQueries::class);
        $saleChannelQueries->setRestoreCompanyActive($companyId, true);

        $externalConnectionQueries = resolve(ExternalConnectionQueries::class);
        $externalConnections = $externalConnectionQueries->getAll();

        foreach ($externalConnections as $externalConnection) {
            Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.http_time_out'))->post(
                $externalConnection->url . '/api/external-connection/external-company-restore',
                [
                    'token' => $externalConnection->token,
                    'external_company_id' => $companyId,
                ]
            );
        }

        return to_route('super_admin.companies.index')->with('success', 'Company restored successfully.');
    }
}
