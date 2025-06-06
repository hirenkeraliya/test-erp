<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\DataObjects;

use App\Domains\PaymentType\Enums\PaymentRestrictionTypes;
use App\Domains\PaymentType\PaymentTypeQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class PaymentTypeData extends Data
{
    public function __construct(
        public string $name,
        public bool $is_member_required,
        public bool $is_available_for_refund,
        public bool $trigger_card_payment_machine,
        public bool $trigger_qr_code_payment_machine,
        public bool $trigger_card_affin_payment_machine,
        public bool $status,
        public string $image_name,
        public ?string $payment_terminal_key,
        public bool $is_card_payment,
        public bool $trigger_card_bank_rakyat_terminal,
        public ?string $site_key = null,
        public ?string $secret_key = null,
        public ?string $url = null,
        public bool $is_available_in_ecommerce = false,
        public ?array $sale_channel_ids = null,
        public bool $restrict_by_zone = false,
        public ?array $shipping_zone_ids = null,
        public ?PaymentRestrictionTypes $restriction_type = null,
        public bool $is_available_in_pos = true,
    ) {
    }

    /**
     * @return array<string, array<string|Unique|Enum>>
     */
    public static function rules(Request $request): array
    {
        $paymentTypeId = null;
        $paymentTypeQueries = new PaymentTypeQueries();

        if ('admin.payment_types.update' === $request->route()?->getName()) {
            $paymentTypeId = $request->route()->parameter('paymentTypeId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_types', 'name')->ignore($paymentTypeId)
                    ->where($paymentTypeQueries->filterByCompany(session('admin_company_id'))),
            ],
            'is_member_required' => ['required', 'boolean'],
            'is_available_for_refund' => ['required', 'boolean'],
            'trigger_card_payment_machine' => ['required', 'boolean'],
            'trigger_qr_code_payment_machine' => ['required', 'boolean'],
            'trigger_card_affin_payment_machine' => ['required', 'boolean'],
            'is_card_payment' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
            'image_name' => ['required', 'string'],
            'payment_terminal_key' => ['nullable', 'string'],
            'trigger_card_bank_rakyat_terminal' => ['required', 'boolean'],
            'site_key' => ['nullable', 'string'],
            'secret_key' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'url'],
            'is_available_in_ecommerce' => ['required', 'boolean'],
            'sale_channel_ids' => ['required_if:is_available_in_ecommerce,true', 'nullable', 'array'],
            'sale_channel_ids.*' => ['integer'],
            'restrict_by_zone' => ['required', 'boolean'],
            'shipping_zone_ids' => ['required_if:restrict_by_zone,true', 'nullable', 'array'],
            'shipping_zone_ids.*' => ['integer'],
            'restriction_type' => ['nullable', new Enum(PaymentRestrictionTypes::class)],
            'is_available_in_pos' => ['required', 'boolean'],
        ];
    }
}
