<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\DataObjects;

use Spatie\LaravelData\Data;

class EcommerceMemberGroupData extends Data
{
    public function __construct(
        public int $external_member_group_id,
        public string $name,
        public string $code,
    ) {
    }

    public static function rules(): array
    {
        return [
            'external_member_group_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
        ];
    }
}
