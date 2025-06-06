<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\DataObjects;

use App\Domains\StockTransfer\Enums\StatusTypes;
use Spatie\LaravelData\Data;

class StockTransferUpdateStatusData extends Data
{
    public function __construct(
        public int $status_id,
        public ?string $remarks,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'status_id' => ['required', 'in:' . StatusTypes::getValues()],
            'remarks' => [
                'required_if:status_id,' . StatusTypes::CANCELLED->value,
                'required_if:status_id,' . StatusTypes::REJECTED->value,
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'remarks.required_if' => 'Remarks is required for cancel/reject the stock transfer.',
        ];
    }
}
