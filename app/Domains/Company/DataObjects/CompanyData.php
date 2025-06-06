<?php

declare(strict_types=1);

namespace App\Domains\Company\DataObjects;

use App\Domains\Company\Enums\BookingPaymentRefundTypes;
use App\Domains\Company\Enums\BookingPaymentUseTypes;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Company\Enums\LocationAssignmentTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class CompanyData extends Data
{
    public function __construct(
        public string $name,
        public ?string $code,
        public string $grn_format,
        public ?string $legal_name,
        public ?string $website,
        public string $email,
        public ?string $fax,
        public ?string $address,
        public ?string $employer_identification_number,
        public ?string $social_security_number,
        public string $void_sale_number_prefix,
        public bool $send_sale_email_to_member,
        public int $new_member_free_loyalty_points,
        public int $number_of_receipts,
        public int $commission_type_id,
        public int $min_promoters_per_item,
        public bool $is_bill_reference_number_mandatory,
        public ?UploadedFile $light_logo,
        public ?UploadedFile $dark_logo,
        public ?UploadedFile $email_footer_logo,
        public ?bool $allow_exchange_to_different_store,
        public bool $allow_price_override_cart_level,
        public bool $allow_negative_inventory,
        public bool $is_employee_booking_payment_allowed,
        public bool $allow_only_return,
        public bool $allow_credit_sale,
        public bool $allow_employee_credit_sale,
        public bool $auto_birthday_voucher_generation,
        public ?float $yearly_target,
        public array $brand_ids,
        public int $discount_applicable_type,
        public int $booking_payment_use_type,
        public int $booking_payment_refund_type,
        public bool $enable_ioi_city_mall_integration,
        public bool $enable_trx_mall_integration,
        public bool $allow_happy_hour_discount,
        public bool $auto_include_in_collections,
        public bool $auto_include_in_member_group,
        public bool $creator_can_approve_draft_product,
        public bool $enable_e_invoice,
        public bool $show_e_invoice_qr_on_receipt,
        public ?int $default_location_id,
        public int $location_assignment_type,
        public int $default_country_id,
        public string $order_picking_list_prefix,
        public array $country_ids,
        public int $loyalty_point_expiration_days,

        public ?array $company_setting,
    ) {
    }

    /**
     * @return array<string, array<(Unique|string)>>
     */
    public static function rules(Request $request): array
    {
        $companyId = null;

        if ('super_admin.companies.update_company' === $request->route()?->getName()) {
            /** @var string $companyId */
            $companyId = $request->route()->parameter('companyId');
        }

        $logoRule = $companyId ? 'nullable' : 'required';
        $locationQueries = resolve(LocationQueries::class);

        return [
            'name' => ['required', 'string', 'max:255', new Unique('companies', 'name', ignore: $companyId)],
            'code' => ['nullable', 'string', 'max:255', new Unique('companies', 'code', ignore: $companyId)],
            'grn_format' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', new Unique('companies', 'email', ignore: $companyId)],
            'fax' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'employer_identification_number' => ['nullable', 'string', 'max:255'],
            'social_security_number' => ['nullable', 'string', 'max:255'],
            'void_sale_number_prefix' => ['required', 'string', 'max:255'],
            'send_sale_email_to_member' => ['required', 'boolean'],
            'new_member_free_loyalty_points' => ['required', 'integer'],
            'loyalty_point_expiration_days' => ['required', 'integer', 'min:0'],
            'number_of_receipts' => ['required', 'integer'],
            'commission_type_id' => ['required', 'integer', 'in:' . CommissionTypes::getValues()],
            'min_promoters_per_item' => ['required', 'integer', 'max:255'],
            'is_bill_reference_number_mandatory' => ['required', 'boolean'],
            'light_logo' => [
                $logoRule,
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(200)->maxHeight(200)),
                'max:' . config('services.max_upload_size'),
            ],
            'dark_logo' => [
                $logoRule,
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(200)->maxHeight(200)),
                'max:' . config('services.max_upload_size'),
            ],
            'email_footer_logo' => [
                $logoRule,
                'file',
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(1280)->maxHeight(720)),
                'max:' . config('services.max_upload_size'),
            ],
            'brand_ids' => ['required', 'array'],
            'allow_exchange_to_different_store' => ['nullable', 'boolean'],
            'allow_price_override_cart_level' => ['required', 'boolean'],
            'allow_negative_inventory' => ['required', 'boolean'],
            'is_employee_booking_payment_allowed' => ['required', 'boolean'],
            'allow_only_return' => ['required', 'boolean'],
            'allow_credit_sale' => ['required', 'boolean'],
            'allow_employee_credit_sale' => ['required', 'boolean'],
            'auto_birthday_voucher_generation' => ['boolean'],
            'yearly_target' => ['nullable', 'numeric'],
            'discount_applicable_type' => ['required', 'integer', 'in:' . DiscountApplicableTypes::getValues()],
            'booking_payment_use_type' => ['required', 'integer', 'in:' . BookingPaymentUseTypes::getValues()],
            'booking_payment_refund_type' => ['required', 'integer', 'in:' . BookingPaymentRefundTypes::getValues()],
            'enable_ioi_city_mall_integration' => ['boolean'],
            'allow_happy_hour_discount' => ['required', 'boolean'],
            'auto_include_in_collections' => ['required', 'boolean'],
            'auto_include_in_member_group' => ['required', 'boolean'],
            'creator_can_approve_draft_product' => ['required', 'boolean'],
            'enable_e_invoice' => ['required', 'boolean'],
            'show_e_invoice_qr_on_receipt' => ['required', 'boolean'],
            'location_assignment_type' => ['required', 'integer', 'in:' . LocationAssignmentTypes::getValues()],
            'default_location_id' => [
                Rule::requiredIf(
                    fn (): bool => null !== $companyId && $request->input(
                        'location_assignment_type'
                    ) === LocationAssignmentTypes::DEFAULT_LOCATION->value
                ),
                'nullable',
                'integer',
                Rule::exists('locations', 'id')
                    ->where($locationQueries->filterByCompanyAndTypeId((int) $companyId, LocationTypes::STORE->value)),
            ],
            'enable_trx_mall_integration' => ['boolean'],
            'country_ids' => ['required', 'array'],
            'country_ids.*' => ['required', 'integer'],
            'default_country_id' => ['required', 'integer'],
            'order_picking_list_prefix' => ['required', 'string', 'max:255'],

            'company_setting' => ['required', 'array'],
            'company_setting.*' => ['required', 'boolean'],
        ];
    }
}
