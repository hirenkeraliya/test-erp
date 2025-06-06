<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use App\Domains\Member\Enums\Types;
use App\Domains\Voucher\DataObjects\GenerateVoucherData;
use App\Rules\MobileNumber;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class OrderECommerceData extends Data
{
    public function __construct(
        public ?int $member_id = null,
        public ?string $channel = null,
        public ?string $happened_at = null,
        public int|string|null $external_order_id = null,
        public ?array $order_items = [],
        public ?int $payment_type_id = null,
        public ?float $payment_amount = null,
        public ?string $payment_notes = null,
        public ?array $shipping_address = [],
        public ?array $billing_address = [],
        public ?string $notes = null,
        public ?float $order_round_off_amount = null,
        public ?float $total_tax_amount = null,
        public ?float $delivery_charges = null,
        public ?array $member_details = [],
        #[DataCollectionOf(GenerateVoucherData::class)]
        public ?DataCollection $vouchers = null,
        public ?string $voucher_number = null,
        public ?float $voucher_discount_amount = null,
        public ?array $loyalty_points = [],
        public ?int $cart_loyalty_points = null,
        public ?float $cart_loyalty_point_amount = null,
        public ?int $cart_promotion_id = null,
        public ?float $cart_discount_amount = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'member_id' => ['sometimes', 'nullable', 'integer'],
            'channel' => ['sometimes', 'nullable', 'string'],
            'happened_at' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'external_order_id' => ['sometimes', 'nullable', 'string'],

            'order_items' => ['sometimes', 'nullable', 'array'],
            'order_items.*.id' => ['required_without:order_items.*.upc', 'nullable', 'alpha_num', 'max:255'],
            'order_items.*.upc' => ['required_without:order_items.*.id', 'nullable', 'string', 'max:255'],
            'order_items.*.price' => ['sometimes', 'numeric', 'min:0'],
            'order_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'order_items.*.total_amount' => ['sometimes', 'nullable', 'numeric'],
            'order_items.*.dream_price_id' => ['sometimes', 'nullable', 'integer'],
            'order_items.*.dream_price_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],
            'order_items.*.dream_price_discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],

            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string'],
            'shipping_address.last_name' => ['sometimes', 'nullable', 'string'],
            'shipping_address.phone' => ['required', 'string'],
            'shipping_address.address_line_1' => ['required', 'string'],
            'shipping_address.address_line_2' => ['sometimes', 'nullable', 'string'],
            'shipping_address.country_code' => ['nullable', 'string'],
            'shipping_address.country_id' => ['nullable', 'integer'],
            'shipping_address.state_id' => ['nullable', 'integer'],
            'shipping_address.city_id' => ['nullable', 'integer'],
            'shipping_address.country_name' => ['nullable', 'string', 'max:255'],
            'shipping_address.state_name' => ['nullable', 'string', 'max:255'],
            'shipping_address.city_name' => ['nullable', 'string', 'max:255'],
            'shipping_address.area_code' => ['required', 'string'],

            'billing_address' => ['required', 'array'],
            'billing_address.first_name' => ['required', 'string'],
            'billing_address.last_name' => ['sometimes', 'nullable', 'string'],
            'billing_address.phone' => ['required', 'string'],
            'billing_address.address_line_1' => ['required', 'string'],
            'billing_address.address_line_2' => ['sometimes', 'nullable', 'string'],
            'billing_address.country_code' => ['nullable', 'string'],
            'billing_address.country_id' => ['nullable', 'integer'],
            'billing_address.state_id' => ['nullable', 'integer'],
            'billing_address.city_id' => ['nullable', 'integer'],
            'billing_address.country_name' => ['nullable', 'string', 'max:255'],
            'billing_address.state_name' => ['nullable', 'string', 'max:255'],
            'billing_address.city_name' => ['nullable', 'string', 'max:255'],
            'billing_address.area_code' => ['required', 'string'],

            'payment_type_id' => ['nullable', 'integer'],
            'payment_amount' => ['nullable', 'numeric'],
            'payment_notes' => ['nullable', 'string'],

            'notes' => ['sometimes', 'nullable', 'string'],
            'order_round_off_amount' => ['sometimes', 'nullable', 'numeric'],
            'total_tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'delivery_charges' => ['sometimes', 'nullable', 'numeric'],

            'member_details' => ['required', 'array'],
            'member_details.*.type_id' => ['sometimes', 'integer', 'in:' . Types::getValues()],
            'member_details.*.name' => ['sometimes', 'string', 'max:255'],
            'member_details.*.mobile_number' => ['sometimes', 'string', 'max:255', new MobileNumber()],
            'member_details.*.email' => ['sometimes', 'string', 'email', 'max:255'],

            'vouchers' => ['sometimes', 'nullable', 'array'],
            'vouchers.*.voucher_configuration_id' => ['required', 'integer'],
            'vouchers.*.discount_type' => ['required', 'integer'],
            'vouchers.*.number' => ['required', 'string'],
            'vouchers.*.minimum_spend_amount' => ['required', 'numeric', 'min:0.01'],
            'vouchers.*.percentage' => ['sometimes', 'nullable', 'numeric', 'between:0.01,100'],
            'vouchers.*.flat_amount' => ['sometimes', 'nullable', 'numeric', 'between:0.01,99999999.99'],
            'vouchers.*.expired_at' => ['sometimes', 'nullable', 'date', 'max:255', 'date_format:Y-m-d'],

            'voucher_number' => ['sometimes', 'nullable', 'string'],
            'voucher_discount_amount' => ['sometimes', 'nullable', 'numeric'],

            'loyalty_points' => ['sometimes', 'nullable', 'array'],
            'loyalty_points.*.loyalty_campaign_id' => ['required', 'integer'],
            'loyalty_points.*.minimum_spend_amount' => ['required', 'numeric', 'min:0.00'],
            'loyalty_points.*.points' => ['required', 'integer'],
            'loyalty_points.*.expired_at' => ['nullable', 'date', 'max:255', 'date_format:Y-m-d'],

            'cart_loyalty_points' => ['sometimes', 'nullable', 'integer'],
            'cart_loyalty_point_amount' => ['sometimes', 'nullable', 'numeric'],
            'cart_promotion_id' => ['sometimes', 'nullable', 'integer'],
            'cart_discount_amount' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
