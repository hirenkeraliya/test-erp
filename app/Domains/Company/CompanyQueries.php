<?php

declare(strict_types=1);

namespace App\Domains\Company;

use App\Domains\Brand\BrandQueries;
use App\Domains\Company\DataObjects\CompanyData;
use App\Domains\Company\Enums\CompanyStatuses;
use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Domains\Country\CountryQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\CurrencyRate\CurrencyRateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Media\MediaQueries;
use App\Models\Company;
use App\Models\CompanySetting;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CompanyQueries
{
    public function listQuery(array $filterData): LengthAwarePaginator
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Company::query()
            ->select('id', 'name', 'code', 'email', 'currency_rate_auto_update', 'deleted_at', 'is_email_verified')
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code', 'email'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->when(CompanyStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                $query->withoutTrashed();
            })
            ->when(CompanyStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                $query->onlyTrashed();
            })
            ->when(CompanyStatuses::ALL->value === $filterData['status'], function ($query): void {
                $query->withTrashed();
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(CompanyData $companyData): int
    {
        $companyValidatedData = $companyData->all();
        $companySettingData = $companyValidatedData['company_setting'];

        unset(
            $companyValidatedData['light_logo'],
            $companyValidatedData['dark_logo'],
            $companyValidatedData['brand_ids'],
            $companyValidatedData['email_footer_logo'],
            $companyValidatedData['country_ids'],
            $companyValidatedData['company_setting'],
        );

        $companyValidatedData['show_e_invoice_qr_on_receipt'] = false == $companyValidatedData['enable_e_invoice'] ? false : $companyValidatedData['show_e_invoice_qr_on_receipt'];
        $companyValidatedData['uuid'] = Str::uuid();
        $company = Company::create($companyValidatedData);

        $companySettingQueries = resolve(CompanySettingQueries::class);
        $companySettingQueries->addNew($companySettingData, $company->id);

        $company->brands()->sync($companyData->brand_ids);
        $company->countries()->sync($companyData->country_ids);
        $this->uploadLogos($company, $companyData);

        return $company->id;
    }

    public function getByIdWithMediaAndBrands(int $companyId): Company
    {
        $brandQueries = new BrandQueries();
        $mediaQueries = resolve(MediaQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $companySettingQueries = resolve(CompanySetting::class);

        return Company::select(
            'id',
            'name',
            'code',
            'grn_format',
            'legal_name',
            'website',
            'email',
            'fax',
            'address',
            'employer_identification_number',
            'social_security_number',
            'void_sale_number_prefix',
            'send_sale_email_to_member',
            'new_member_free_loyalty_points',
            'commission_type_id',
            'min_promoters_per_item',
            'is_bill_reference_number_mandatory',
            'allow_exchange_to_different_store',
            'allow_price_override_cart_level',
            'allow_negative_inventory',
            'is_employee_booking_payment_allowed',
            'allow_only_return',
            'allow_credit_sale',
            'allow_employee_credit_sale',
            'yearly_target',
            'discount_applicable_type',
            'booking_payment_use_type',
            'booking_payment_refund_type',
            'auto_birthday_voucher_generation',
            'enable_ioi_city_mall_integration',
            'enable_trx_mall_integration',
            'allow_happy_hour_discount',
            'auto_include_in_collections',
            'auto_include_in_member_group',
            'creator_can_approve_draft_product',
            'enable_e_invoice',
            'show_e_invoice_qr_on_receipt',
            'default_location_id',
            'location_assignment_type',
            'default_country_id',
            'order_picking_list_prefix',
            'loyalty_point_expiration_days',
            'number_of_receipts',
            'currency_rate_auto_update',
            'is_email_verified'
        )->with(
            'media:' . $mediaQueries->getBasicColumnNames(),
            'brands:' . $brandQueries->getBasicColumnNames(),
            'locations:' . $locationQueries->getNameColumnName(),
            'countries:id,name',
            'companySetting:'. $companySettingQueries->getNameColumnName(),
        )->findOrFail($companyId);
    }

    public function getByIdWithPromoterCommissionDetails(int $companyId): Company
    {
        return Company::select(
            'id',
            'name',
            'code',
            'commission_type_id',
            'min_promoters_per_item'
        )->findOrFail($companyId);
    }

    public function getConfigurationColumnsById(int $companyId): Company
    {
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $currencyRateQueries = resolve(CurrencyRateQueries::class);
        $companySettingQueries = resolve(CompanySettingQueries::class);

        return Company::select(
            'id',
            'min_promoters_per_item',
            'is_bill_reference_number_mandatory',
            'allow_price_override_cart_level',
            'allow_negative_inventory',
            'allow_credit_sale',
            'allow_employee_credit_sale',
            'discount_applicable_type',
            'booking_payment_use_type',
            'booking_payment_refund_type',
            'allow_only_return',
            'default_country_id'
        )
            ->with([
                'defaultCountry:' . $countryQueries->getBasicColumnNames(),
                'countries:' . $countryQueries->getBasicColumnNames(),
                'countries.currency:'. $currencyQueries->getBasicColumnNames(),
                'countries.currency.currencyRate:' . $currencyRateQueries->getBasicColumnNames(),
                'companySetting:'. $companySettingQueries->getNameColumnName(),
            ])
            ->findOrFail($companyId);
    }

    public function getCompanies(): Collection
    {
        $countryQueries = resolve(CountryQueries::class);
        $currencyQueries = resolve(CurrencyQueries::class);
        $currencyRateQueries = resolve(CurrencyRateQueries::class);

        return Company::select('id', 'default_country_id')
            ->where('currency_rate_auto_update', true)
            ->with([
                'defaultCountry:' . $countryQueries->getBasicColumnNames(),
                'defaultCountry.currency:'. $currencyQueries->getBasicColumnNames(),
                'countries:' . $countryQueries->getBasicColumnNames(),
                'countries.currency:'. $currencyQueries->getBasicColumnNames(),
                'countries.currency.currencyRate:' . $currencyRateQueries->getBasicColumnNames(),
            ])
            ->get();
    }

    public function update(CompanyData $companyData, int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        $companyValidatedData = $companyData->all();

        if ($companyValidatedData['location_assignment_type'] === LocationAssignmentTypes::MANUAL_ASSIGNMENT->value || $companyValidatedData['location_assignment_type'] === LocationAssignmentTypes::BASED_ON_FIRST_PURCHASE->value) {
            $companyValidatedData['default_location_id'] = null;
        }

        $companySettingData = $companyValidatedData['company_setting'];

        unset(
            $companyValidatedData['light_logo'],
            $companyValidatedData['dark_logo'],
            $companyValidatedData['brand_ids'],
            $companyValidatedData['email_footer_logo'],
            $companyValidatedData['country_ids'],
            $companyValidatedData['company_setting'],
        );

        $companyValidatedData['show_e_invoice_qr_on_receipt'] = false == $companyValidatedData['enable_e_invoice'] ? false : $companyValidatedData['show_e_invoice_qr_on_receipt'];
        $company->update($companyValidatedData);

        $company->brands()->sync($companyData->brand_ids);
        $company->countries()->sync($companyData->country_ids);

        $companySettingQueries = resolve(CompanySettingQueries::class);
        $companySettingQueries->update($companySettingData, $companyId);

        $this->uploadLogos($company, $companyData);
        $this->setUpdatedAt($company);
    }

    public function setUpdatedAt(Company $company): void
    {
        $company->touch();
    }

    public static function getBasicColumnNames(): string
    {
        return 'id,name,default_country_id';
    }

    public static function getBasicColumnNamesForStockTransferPrint(): string
    {
        return 'id,name,social_security_number,address';
    }

    public static function getBasicColumnNamesForNewMemberBenefitsJob(): string
    {
        return 'id,new_member_free_loyalty_points,default_location_id,location_assignment_type';
    }

    public static function getBasicColumnNamesForPurchaseOrderInvoicePrint(): string
    {
        return 'id,name,social_security_number,address,fax';
    }

    public static function getColumnNamesForMeApiEndPoint(): string
    {
        return 'id,name,email,employer_identification_number,social_security_number';
    }

    public static function getBasicColumnNamesWithCode(): string
    {
        return 'id,name,code';
    }

    public static function getBasicColumnForOrders(): string
    {
        return 'id,name,min_promoters_per_item,is_bill_reference_number_mandatory,allow_price_override_cart_level,discount_applicable_type';
    }

    public function getPromoterColumns(): string
    {
        return 'id,name,commission_type_id,min_promoters_per_item';
    }

    public function getBasicColumnNamesForAdminSaleReports(): string
    {
        return 'id,name,social_security_number,code,default_country_id';
    }

    public function getBasicColumnNamesForOrderReports(): string
    {
        return 'id,name,code,social_security_number,address';
    }

    public function getVoidSaleNumberPrefixColumn(): string
    {
        return 'id,void_sale_number_prefix';
    }

    public function getBasicColumnNamesForStoreConfiguration(): string
    {
        return 'id,new_member_free_loyalty_points,min_promoters_per_item,is_bill_reference_number_mandatory,allow_price_override_cart_level,allow_exchange_to_different_store,is_employee_booking_payment_allowed,allow_only_return,allow_credit_sale,allow_employee_credit_sale,allow_negative_inventory,discount_applicable_type,booking_payment_use_type,booking_payment_refund_type,auto_birthday_voucher_generation,enable_e_invoice,show_e_invoice_qr_on_receipt,number_of_receipts,loyalty_point_expiration_days';
    }

    public function getBasicColumnNamesForEcommerceLocationConfiguration(): string
    {
        return 'id,name,discount_applicable_type';
    }

    public function getIsBillReferenceNumberMandatoryColumn(): string
    {
        return 'id,is_bill_reference_number_mandatory';
    }

    public function getColumnForBookingPayment(): string
    {
        return 'id,is_bill_reference_number_mandatory,booking_payment_refund_type';
    }

    public function getEnableIoiCityMallIntegrationColumn(): string
    {
        return 'id,enable_ioi_city_mall_integration';
    }

    public function getEnableTRXMallIntegrationColumn(): string
    {
        return 'id,enable_trx_mall_integration';
    }

    public function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('name', 'like', '%' . $searchText . '%');
    }

    public function filterById(int $companyId): Closure
    {
        return fn ($query) => $query->select('id')->where('id', $companyId);
    }

    /**
     * @return mixed[]
     */
    public function getWithBasicColumns(): array
    {
        return Company::select('id', 'name')->get()->toArray();
    }

    public function getGrnFormat(int $companyId): string
    {
        return Company::select('id', 'grn_format')->findOrFail($companyId)->grn_format;
    }

    public function getVoidSaleNumberPrefix(int $companyId): string
    {
        return Company::select('id', 'void_sale_number_prefix')->findOrFail($companyId)->void_sale_number_prefix;
    }

    public function getByIdWithBrands(int $companyId): Company
    {
        $brandQueries = new BrandQueries();

        return Company::select('id', 'code')
            ->with('brands:' . $brandQueries->getBasicColumnNames())
            ->findOrFail($companyId);
    }

    public function getById(int $companyId): Company
    {
        return Company::select('id', 'code', 'email')->findOrFail($companyId);
    }

    public function doesCompanyExist(string $uuid): bool
    {
        return Company::select('id', 'uuid')
            ->where('uuid', $uuid)
            ->exists();
    }

    public function getCompanyIdsByUuid(string $uuid): Company
    {
        return Company::select('id', 'uuid')
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function hasAllBrandsAttached(int $companyId, array $brandIds): bool
    {
        $company = $this->getByIdWithBrands($companyId);
        $totalRecords = $company->brands->whereIn('id', $brandIds)->count();

        return count($brandIds) === $totalRecords;
    }

    public function getCountries(int $companyId): Company
    {
        return Company::select('id')
            ->with('countries:id,name')
            ->findOrFail($companyId);
    }

    public function getNewMemberFreeLoyaltyPointsById(int $companyId): Company
    {
        return Company::select('id', 'new_member_free_loyalty_points', 'loyalty_point_expiration_days')->findOrFail(
            $companyId
        );
    }

    public function getNameAndCodeById(int $companyId): Company
    {
        return Company::select('id', 'name', 'code')->findOrFail($companyId);
    }

    public function getAllowPriceOverrideCartLevel(int $companyId): bool
    {
        return Company::select('id', 'allow_price_override_cart_level')->findOrFail(
            $companyId
        )->allow_price_override_cart_level;
    }

    public function getCompanyDetails(int $companyId): Company
    {
        return Company::select('id', 'send_sale_email_to_member', 'allow_exchange_to_different_store')
            ->findOrFail($companyId);
    }

    public function getYearlyTarget(int $companyId): float
    {
        return (float) Company::select('yearly_target')->find($companyId)?->yearly_target;
    }

    public function getPromoterCommissionType(int $companyId): int
    {
        return Company::select('id', 'commission_type_id')->findOrFail($companyId)->commission_type_id->value;
    }

    public function getList(): Collection
    {
        $mediaQueries = resolve(MediaQueries::class);

        return Company::query()
            ->select('id', 'name', 'code', 'email', 'fax', 'address', 'social_security_number')
            ->with('media:' . $mediaQueries->getBasicColumnNames())
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getAll(): Collection
    {
        return Company::select('id', 'uuid')->get();
    }

    public function delete(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        $company->delete();
    }

    public function getWithIdAndName(): Collection
    {
        return Company::select('id', 'name')->get();
    }

    public function getIOICityAndTRXMallConfiguration(int $companyId): ?Company
    {
        return Company::select('id', 'enable_ioi_city_mall_integration', 'enable_trx_mall_integration')
            ->findOrFail($companyId);
    }

    public function getWithIdNameAndIOICityMall(): Collection
    {
        return Company::select('id', 'name')
            ->where('enable_ioi_city_mall_integration', true)
            ->get();
    }

    public function getWithIdNameAndTRXMall(): Collection
    {
        return Company::select('id', 'name')
            ->where('enable_trx_mall_integration', true)
            ->get();
    }

    public function getAllowHappyHourDiscount(int $companyId): ?bool
    {
        return Company::select('id', 'allow_happy_hour_discount')->find($companyId)?->allow_happy_hour_discount;
    }

    public function getWithLocationAssignmentTypeById(int $companyId): ?Company
    {
        return Company::select('id', 'location_assignment_type', 'default_location_id')->find($companyId);
    }

    public function getColumnsForProductCollection(): string
    {
        return 'id,auto_include_in_collections';
    }

    public function getWithAutoIncludeInCollectionsById(int $companyId): Company
    {
        return Company::select('id', 'auto_include_in_collections')->findOrFail($companyId);
    }

    public function getWithCreatorCanApproveDraftProductById(int $companyId): Company
    {
        return Company::select('id', 'creator_can_approve_draft_product')->findOrFail($companyId);
    }

    public function getCountryCurrencySymbol(int $companyId): Company
    {
        $currencyQueries = resolve(CurrencyQueries::class);

        return Company::select('id', 'default_country_id', 'currency_rate_auto_update')
            ->with(['defaultCountry:id', 'defaultCountry.currency:' . $currencyQueries->getBasicColumnNames()])
            ->findOrFail($companyId);
    }

    public function toggleCurrencyUpdateRate(int $companyId): void
    {
        $company = Company::select('id', 'currency_rate_auto_update')
            ->findOrFail($companyId);

        $company->currency_rate_auto_update = ! $company->currency_rate_auto_update;
        $company->save();
    }

    public function getOrderPickingListPrefix(int $companyId): string
    {
        return Company::select('id', 'order_picking_list_prefix')->findOrFail($companyId)->order_picking_list_prefix;
    }

    public function getEnableEInvoiceById(int $companyId): bool
    {
        return Company::select('id', 'enable_e_invoice')->findOrFail($companyId)->enable_e_invoice;
    }

    public function getBasicColumnsForEInvoice(): string
    {
        return 'id,enable_e_invoice';
    }

    public function getByIdForPosAdmin(int $id): Company
    {
        return Company::query()
            ->select('id', 'name', 'code', 'email', 'uuid')
            ->findOrFail($id);
    }

    public function getByIdWithAutoIncludeMemberGroup(int $companyId): Company
    {
        return Company::select('id', 'auto_include_in_member_group')
            ->findOrFail($companyId);
    }

    public function restore(int $companyId): void
    {
        $company = Company::withTrashed()->findOrFail($companyId);
        $company->restore();
    }

    private function uploadLogos(Company $company, CompanyData $companyData): void
    {
        if ($companyData->light_logo instanceof UploadedFile) {
            $company->addMedia($companyData->light_logo)->toMediaCollection('light_logo');
        }

        if ($companyData->dark_logo instanceof UploadedFile) {
            $company->addMedia($companyData->dark_logo)->toMediaCollection('dark_logo');
        }

        if ($companyData->email_footer_logo instanceof UploadedFile) {
            $company->addMedia($companyData->email_footer_logo)->toMediaCollection('email_footer_logo');
        }
    }

    public function getAllCompanies(): Collection
    {
        return Company::select(
            'id',
            'name',
            'code',
            'legal_name',
            'employer_identification_number',
            'social_security_number',
            'address',
            'email'
        )
            ->get();
    }
}
