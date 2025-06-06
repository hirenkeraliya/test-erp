<?php

declare(strict_types=1);

namespace App\Domains\SubPaymentType\DataObjects;

use App\Domains\SubPaymentType\SubPaymentTypeQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Spatie\LaravelData\Data;

class SubPaymentTypeData extends Data
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
        public bool $is_available_in_pos = true,
    ) {
    }

    /**
     * @return array<string, array<string|Unique>>
     */
    public static function rules(Request $request): array
    {
        $subPaymentTypeId = null;
        $subPaymentTypeQueries = new SubPaymentTypeQueries();

        if ('admin.sub_payment_types.update' === $request->route()?->getName()) {
            $subPaymentTypeId = $request->route()->parameter('subPaymentTypeId');
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_types', 'name')->ignore($subPaymentTypeId)
                    ->where($subPaymentTypeQueries->filterByCompany(session('admin_company_id'))),
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
            'is_available_in_pos' => ['required', 'boolean'],
        ];
    }
}
