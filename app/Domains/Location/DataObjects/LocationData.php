<?php

declare(strict_types=1);

namespace App\Domains\Location\DataObjects;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Region\RegionQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class LocationData extends Data
{
    public function __construct(
        public string $name,
        public int $type_id,
        public string $code,
        public string $registration_number,
        public string $sst_number,
        public string $email,
        public ?string $phone,
        public ?string $mobile,
        public ?string $fax,
        public string $address_line_1,
        public ?string $address_line_2,
        public int $city_id,
        public int $state_id,
        public string $area_code,
        public ?string $web_site,
        public ?float $sales_tax_percentage,
        public ?int $sales_return_days_limit,
        public ?int $credit_note_expiration_days,
        public ?int $loyalty_point_expiration_days,
        public ?string $receipt_footer,
        public ?string $disclaimer,
        public ?array $brand_ids,
        public ?bool $is_automatic_day_close,
        public bool $share_inventory_to_external_companies,
        public ?string $automatic_day_close_time,
        public ?float $cash_out_limit_info,
        public ?float $cash_out_limit_warning,
        public ?float $cash_out_limit_restrict,
        public ?float $price_fall_down_percentage,
        public ?string $open_time,
        public ?string $close_time,
        public ?int $country_id,
        public ?int $region_id = null,
        public ?array $sale_channel_ids = [],
        public ?float $minimum_stock_threshold = null,
        public ?float $maximum_stock_threshold = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $companyId = session('admin_company_id');
        $locationId = null;
        $locationQueries = new LocationQueries();
        $regionQueries = new RegionQueries();

        if ('admin.locations.update' === $request->route()?->getName()) {
            $locationId = $request->route()->parameter('locationId');
        }

        return [
            'type_id' => ['required', 'integer', 'in:' . LocationTypes::getValues()],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations', 'name')->ignore($locationId)
                    ->where($locationQueries->filterByCompanyAndTypeId($companyId, $request->type_id)),
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations', 'code')->ignore($locationId)
                    ->where($locationQueries->filterByCompanyAndTypeId($companyId, $request->type_id)),
            ],
            'registration_number' => ['required', 'string', 'max:255'],
            'sst_number' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'phone' => [
                'nullable',
                'integer',
                'digits_between:8,12',
                Rule::unique('locations', 'phone')->ignore($locationId)
                    ->where($locationQueries->filterByCompanyAndTypeId($companyId, $request->type_id)),
            ],
            'mobile' => ['nullable', 'integer', 'digits_between:8,12'],
            'fax' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'area_code' => ['required', 'integer', 'digits_between:1,20'],
            'web_site' => ['nullable', 'url', 'max:255'],
            'sales_tax_percentage' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'numeric',
                'between:0,100.00',
            ],
            'sales_return_days_limit' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'integer',
            ],
            'credit_note_expiration_days' => ['nullable', 'integer'],
            'loyalty_point_expiration_days' => ['nullable', 'integer'],
            'receipt_footer' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'string',
                'max:255',
            ],
            'disclaimer' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'string',
                'max:255',
            ],
            'brand_ids' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'array',
            ],
            'brand_ids.*' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'integer',
            ],
            'is_automatic_day_close' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'boolean',
            ],
            'share_inventory_to_external_companies' => ['required', 'boolean'],
            'automatic_day_close_time' => ['required_if:is_automatic_day_close,true', 'nullable', 'date_format:H:i:s'],
            'region_id' => [
                'nullable',
                'integer',
                Rule::exists('regions', 'id')
                    ->where($regionQueries->filterByCompany($companyId)),
            ],
            'cash_out_limit_info' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'cash_out_limit_warning' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'cash_out_limit_restrict' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'price_fall_down_percentage' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'numeric',
                'between:0,100.00',
            ],
            'open_time' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'string',
            ],
            'close_time' => [
                'sometimes',
                'nullable',
                'required_if:type_id,' . LocationTypes::STORE->value,
                'string',
                'after:open_time',
            ],
            'country_id' => [
                'sometimes',
                'nullable',
                'integer',
                'required_if:type_id,' . LocationTypes::STORE->value,
            ],
            'sale_channel_ids' => ['sometimes', 'nullable', 'array'],
            'sale_channel_ids.*' => ['sometimes', 'nullable', 'integer'],
            'state_id' => ['required', 'integer'],
            'city_id' => ['required', 'integer'],
            'minimum_stock_threshold' => ['sometimes', 'nullable', 'numeric', 'between:0,99999999.99'],
            'maximum_stock_threshold' => ['sometimes', 'nullable', 'numeric', 'between:0,99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'phone.digits' => 'Please enter a valid phone number.',
            'brand_ids' => 'The brands field is required.',
            'automatic_day_close_time' => 'The field Automatic Day Close Time is required.',
        ];
    }
}
