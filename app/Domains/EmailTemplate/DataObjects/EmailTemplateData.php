<?php

declare(strict_types=1);

namespace App\Domains\EmailTemplate\DataObjects;

use Spatie\LaravelData\Data;

class EmailTemplateData extends Data
{
    public function __construct(
        public string $name,
        public array $template_json,
        public string $html,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required'],
            'template_json' => ['required', 'array:body,counters,schemaVersion'],
            'html' => ['required', 'string'],
        ];
    }
}
