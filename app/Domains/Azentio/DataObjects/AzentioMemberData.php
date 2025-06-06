<?php

declare(strict_types=1);

namespace App\Domains\Azentio\DataObjects;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class AzentioMemberData extends Data
{
    public function __construct(
        #[MapName('NAME')]
        public string $name,
        #[MapName('COMPANY_ID')]
        public int $companyId,
        #[MapName('MOBILE')]
        public string $mobile,
        #[MapName('CARD_NUMBER')]
        public string $cardNumber,
        #[MapName('EMAIL')]
        public ?string $email = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'NAME' => ['required', 'string'],
            'COMPANY_ID' => ['required', 'integer'],
            'MOBILE' => ['required', 'integer'],
            'EMAIL' => ['sometimes', 'nullable', 'string', 'email:rfc,dns'],
            'CARD_NUMBER' => ['required', 'string'],
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'company_id' => $this->companyId,
            'mobile_number' => $this->mobile,
            'email' => $this->email,
            'card_number' => $this->cardNumber,
        ];
    }
}
