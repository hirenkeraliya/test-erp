<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use App\Domains\Member\Enums\Genders;
use App\Domains\Member\Enums\Races;
use App\Domains\Member\Enums\Titles;
use App\Domains\Member\Enums\Types;
use App\Domains\Voucher\DataObjects\GenerateVoucherData;
use App\Rules\MobileNumber;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class SaleData extends Data
{
    public function __construct(
        public string $offline_sale_id,
        public string $happened_at,
        public ?int $member_id = null,
        public ?int $employee_id = null,
        public ?array $items = [],
        public ?array $payments = [],
        public ?array $return_items = [],
        #[DataCollectionOf(GenerateVoucherData::class)]
        public ?DataCollection $vouchers = null,
        public ?int $cart_promotion_id = null,
        public ?int $cashback_id = null,
        public ?float $cashback_amount = null,
        public ?float $cashback_round_off_amount = null,
        public ?string $sale_notes = null,
        public ?string $bill_reference_number = null,
        public ?float $change_due = null,
        public ?bool $is_layaway = false,
        public ?float $layaway_pending_amount = null,
        public ?float $sale_round_off_amount = null,
        public ?float $sale_return_round_off_amount = null,
        public ?float $total_tax_amount = null,
        public ?float $cart_discount_amount = null,
        public ?string $voucher_number = null,
        public ?float $voucher_discount_amount = null,
        public ?array $member = [],
        public ?array $extra_details = [],
        public ?int $cashier_id = null,
        public ?int $store_manager_id = null,
        public ?string $store_manager_passcode = null,
        public ?string $store_manager_authorization_code = null,
        public ?int $director_id = null,
        public ?string $director_passcode = null,
        public ?float $cart_price_override_amount = null,
        public ?float $cart_price_override_discount_amount = null,
        public ?array $loyalty_points = [],
        public ?int $layaway_store_manager_id = null,
        public ?string $layaway_store_manager_passcode = null,
        public ?string $layaway_store_manager_authorization_code = null,
        public ?bool $is_credit_sale = false,
        public ?float $credit_pending_amount = null,
        public ?int $credit_store_manager_id = null,
        public ?string $credit_store_manager_passcode = null,
        public ?string $credit_store_manager_authorization_code = null,
        public ?string $cart_promo_code = null,
        public ?int $cart_loyalty_points = null,
        public ?float $cart_loyalty_point_amount = null,
        public ?bool $is_employee = false,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'offline_sale_id' => ['required', 'string'],
            'member_id' => ['sometimes', 'nullable', 'exists:members,id'],
            'employee_id' => ['sometimes', 'nullable', 'exists:employees,id'],
            'is_employee' => ['sometimes', 'boolean'],

            'items' => ['sometimes', 'nullable', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.box_product_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.product_bundle_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.serial_number_details.*.serial_number' => ['sometimes', 'nullable', 'string'],
            'items.*.derivative_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.price_based_on_derivative' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'items.*.quantity_of_derivative' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'items.*.price_paid_of_derivative' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'items.*.open_price' => ['sometimes', 'numeric', 'min:0'],
            'items.*.price' => ['sometimes', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.loyalty_points' => ['sometimes', 'nullable', 'integer'],
            'items.*.loyalty_point_item_discount' => ['sometimes', 'nullable', 'numeric'],

            'items.*.batch_details.*.quantity' => ['sometimes', 'numeric'],
            'items.*.batch_details.*.batch_number' => ['sometimes', 'nullable', 'string'],
            'items.*.batch_details.*.batch_expiry_date' => [
                'sometimes',
                'nullable',
                'date',
                'max:255',
                'date_format:Y-m-d',
            ],
            'items.*.is_exchange' => ['sometimes', 'boolean'],

            'items.*.happy_hours_offline_id' => ['sometimes', 'nullable', 'string'],
            'items.*.happy_hours_discount_amount' => ['sometimes', 'nullable', 'numeric'],

            'items.*.promotion_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.promo_code' => ['sometimes', 'nullable', 'string'],
            'items.*.is_gift_with_purchase' => ['sometimes', 'boolean'],
            'items.*.item_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'items.*.group_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.discount_item_sequence' => ['sometimes', 'nullable', 'integer'],
            'items.*.dream_price_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.dream_price_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],
            'items.*.complimentary_item_reason_id' => ['sometimes', 'nullable', 'integer'],
            // TODO: Temporary add min:0.00 due to pos is not able create sale
            'items.*.complimentary_item_discount' => ['sometimes', 'nullable', 'numeric', 'min:0.00'],
            'items.*.cart_discount_item_sequence' => ['sometimes', 'nullable', 'integer'],

            'items.*.promoter_ids' => ['sometimes', 'nullable', 'array'],
            'items.*.promoter_ids.*' => ['required', 'integer'],

            'items.*.cashier_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.store_manager_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.store_manager_passcode' => ['sometimes', 'nullable', 'string'],
            'items.*.store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],
            'items.*.director_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.director_passcode' => ['sometimes', 'nullable', 'string'],
            'items.*.price_override_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.0'],
            'items.*.price_override_discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.0'],
            'items.*.total_price_paid' => ['sometimes', 'nullable', 'numeric'],

            'payments' => ['sometimes', 'nullable', 'array'],
            'payments.*.type_id' => ['required', 'integer'],
            'payments.*.booking_payment_id' => ['sometimes', 'integer'],
            'payments.*.credit_note_id' => ['sometimes', 'integer'],
            'payments.*.gift_card_id' => ['sometimes', 'integer'],
            'payments.*.amount' => ['required', 'numeric'],
            'payments.*.currency_id' => ['sometimes', 'nullable', 'integer'],
            'payments.*.current_currency_rate' => ['sometimes', 'nullable', 'numeric'],
            'payments.*.currency_amount' => ['sometimes', 'nullable', 'numeric'],
            'payments.*.loyalty_points' => ['sometimes', 'nullable', 'integer'],
            'payments.*.extra_details' => ['sometimes', 'nullable', 'array'],

            'return_items' => ['sometimes', 'nullable', 'array'],
            'return_items.*.sale_item_id' => ['required', 'integer'],
            'return_items.*.price_paid_per_unit' => ['required', 'numeric'],
            'return_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'return_items.*.sale_return_details' => ['required', 'array'],
            'return_items.*.sale_return_details.*.quantity' => ['required', 'numeric'],
            'return_items.*.sale_return_details.*.batch_number' => ['sometimes', 'nullable', 'string'],
            'return_items.*.sale_return_details.*.serial_number' => ['sometimes', 'nullable', 'string'],
            'return_items.*.sale_return_details.*.sale_return_reason_id' => ['required', 'integer'],

            'vouchers' => ['sometimes', 'nullable', 'array'],
            'vouchers.*.voucher_configuration_id' => ['required', 'integer'],
            'vouchers.*.discount_type' => ['required', 'integer'],
            'vouchers.*.number' => ['required', 'string'],
            'vouchers.*.minimum_spend_amount' => ['required', 'numeric', 'min:0.01'],
            'vouchers.*.percentage' => ['sometimes', 'nullable', 'numeric', 'between:0.01,100'],
            'vouchers.*.flat_amount' => ['sometimes', 'nullable', 'numeric', 'between:0.01,99999999.99'],
            'vouchers.*.expired_at' => ['sometimes', 'nullable', 'date', 'max:255', 'date_format:Y-m-d'],

            'cart_promotion_id' => ['sometimes', 'nullable', 'integer'],
            'cart_promo_code' => ['sometimes', 'nullable', 'string'],
            'voucher_number' => ['sometimes', 'nullable', 'string'],
            'voucher_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'cashback_id' => ['sometimes', 'nullable', 'integer'],
            'cashback_amount' => ['sometimes', 'nullable', 'numeric'],
            'cashback_round_off_amount' => ['sometimes', 'nullable', 'numeric'],
            'sale_notes' => ['sometimes', 'nullable', 'string'],
            'bill_reference_number' => ['sometimes', 'nullable', 'string'],
            'change_due' => ['sometimes', 'nullable', 'numeric'],
            'happened_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'is_layaway' => ['sometimes', 'boolean'],
            'layaway_pending_amount' => ['sometimes', 'nullable', 'numeric'],
            'sale_round_off_amount' => ['sometimes', 'nullable', 'numeric'],
            'sale_return_round_off_amount' => ['sometimes', 'nullable', 'numeric'],
            'total_tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cart_discount_amount' => ['sometimes', 'nullable', 'numeric'],

            'is_credit_sale' => ['sometimes', 'boolean'],
            'credit_pending_amount' => ['sometimes', 'nullable', 'numeric'],
            'credit_store_manager_id' => ['sometimes', 'nullable', 'integer'],
            'credit_store_manager_passcode' => ['sometimes', 'nullable', 'string'],
            'credit_store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],

            'cashier_id' => ['sometimes', 'nullable', 'integer'],
            'store_manager_id' => ['sometimes', 'nullable', 'integer'],
            'store_manager_passcode' => ['sometimes', 'nullable', 'string'],
            'store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],
            'director_id' => ['sometimes', 'nullable', 'integer'],
            'director_passcode' => ['sometimes', 'nullable', 'string'],
            'cart_price_override_amount' => ['sometimes', 'nullable', 'numeric'],
            'cart_price_override_discount_amount' => ['sometimes', 'nullable', 'numeric'],

            'member' => ['sometimes', 'array'],
            'member.type_id' => ['sometimes', 'integer', 'in:' . Types::getValues()],
            'member.title_id' => ['nullable', 'integer', 'in:' . Titles::getValues()],
            'member.race_id' => ['nullable', 'integer', 'in:' . Races::getValues()],
            'member.gender_id' => ['nullable', 'integer', 'in:' . Genders::getValues()],
            'member.first_name' => ['sometimes', 'string', 'max:255'],
            'member.last_name' => ['nullable', 'string', 'max:255'],
            'member.email' => ['nullable', 'email', 'max:255'],
            'member.mobile_number' => ['sometimes', new MobileNumber()],
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

            'extra_details' => ['sometimes', 'nullable', 'array'],

            'loyalty_points' => ['sometimes', 'nullable', 'array'],
            'loyalty_points.*.loyalty_campaign_id' => ['required', 'integer'],
            'loyalty_points.*.minimum_spend_amount' => ['required', 'numeric', 'min:0.00'],
            'loyalty_points.*.points' => ['required', 'integer'],
            'loyalty_points.*.expired_at' => ['nullable', 'date', 'max:255', 'date_format:Y-m-d'],

            'layaway_store_manager_id' => ['sometimes', 'nullable', 'integer'],
            'layaway_store_manager_passcode' => ['sometimes', 'nullable', 'string'],
            'layaway_store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],

            'cart_loyalty_points' => ['sometimes', 'nullable', 'integer'],
            'cart_loyalty_point_amount' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
