<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\DataObjects;

use App\CommonFunctions;
use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Member\MemberQueries;
use App\Models\Cashier;
use App\Rules\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class BookingPaymentData extends Data
{
    public function __construct(
        public string $offline_id,
        public float $amount,
        public ?int $payment_type_id,
        public ?array $products,
        public ?string $remarks = null,
        public ?string $bill_reference_number = null,
        public ?string $happened_at = null,
        public ?array $promoter_ids = [],
        public ?int $member_id = null,
        public ?array $member = [],
        public ?int $store_manager_id = null,
        public ?string $store_manager_passcode = null,
        public ?string $store_manager_authorization_code = null,
        public ?array $payments = [],
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $memberQueries = new MemberQueries();

        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        return [
            'offline_id' => ['required', 'string', 'unique:booking_payments,offline_id'],
            'member_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('members', 'id')
                    ->where($memberQueries->filterByCompany($companyId)),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_type_id' => ['sometimes', 'nullable', 'integer', 'exists:payment_types,id'],
            'promoter_ids' => ['sometimes', 'nullable', 'array'],
            'promoter_ids.*' => ['required', 'integer'],
            'store_manager_id' => ['sometimes', 'nullable', 'integer'],
            'store_manager_passcode' => ['sometimes', 'nullable', 'string'],
            'store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],

            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['required', 'integer'],
            'products.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'products.*.price' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'products.*.box_product_id' => ['sometimes', 'nullable', 'integer'],
            'products.*.product_bundle_id' => ['sometimes', 'nullable', 'integer'],
            'products.*.promoter_ids' => ['sometimes', 'nullable', 'array'],
            'products.*.promoter_ids.*' => ['required', 'integer'],

            'remarks' => ['sometimes', 'nullable', 'string'],
            'bill_reference_number' => ['sometimes', 'nullable', 'string'],
            'happened_at' => ['sometimes', 'date_format:Y-m-d H:i:s'],

            'member' => ['sometimes', 'array'],
            'member.type_id' => ['sometimes', 'integer', 'in:' . Types::getValues()],
            'member.title_id' => ['nullable', 'integer', 'in:' . Titles::getValues()],
            'member.race_id' => ['nullable', 'integer', 'in:' . Races::getValues()],
            'member.gender_id' => ['nullable', 'integer', 'in:' . Genders::getValues()],
            'member.first_name' => ['sometimes', 'string', 'max:255'],
            'member.last_name' => ['nullable', 'string', 'max:255'],
            'member.email' => ['nullable', 'email', 'max:255'],
            'member.mobile_number' => ['sometimes', 'nullable', new MobileNumber()],
            'member.address_line_1' => ['nullable', 'string', 'max:255'],
            'member.address_line_2' => ['nullable', 'string', 'max:255'],
            'member.city' => ['nullable', 'string', 'max:255'],
            'member.area_code' => ['nullable', 'string', 'max:255'],
            'member.date_of_birth' => ['nullable', 'date', 'max:255'],
            'member.company_name' => ['nullable', 'string', 'max:255'],
            'member.company_registration_number' => ['nullable', 'string', 'max:255'],
            'member.company_tax_number' => ['nullable', 'string', 'max:255'],
            'member.company_phone' => ['nullable', 'string', 'max:255'],
            'member.notes' => ['nullable', 'string', 'max:255'],
            'member.card_number' => ['nullable', 'string'],

            'payments' => ['sometimes', 'nullable', 'array'],
            'payments.*.payment_type_id' => ['required', 'integer'],
            'payments.*.currency_id' => ['sometimes', 'nullable', 'integer'],
            'payments.*.current_currency_rate' => ['sometimes', 'nullable', 'numeric'],
            'payments.*.currency_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.00'],
            'payments.*.credit_note_id' => ['sometimes', 'integer'],
            'payments.*.gift_card_id' => ['sometimes', 'integer'],
            'payments.*.amount' => ['required', 'numeric'],
            'payments.*.extra_details' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
