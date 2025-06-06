<?php

declare(strict_types=1);

namespace App\Domains\PosAdvertisement\DataObjects;

use App\Domains\PosAdvertisement\Enums\PosAdvertisementTypes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Spatie\LaravelData\Data;

class PosAdvertisementData extends Data
{
    public function __construct(
        public int $type_id,
        public ?UploadedFile $photo,
        public ?UploadedFile $video,
        public string $name,
        public array $location_ids,
        public bool $status,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        return [
            'type_id' => ['required', 'integer', 'in:' . PosAdvertisementTypes::getValues()],
            'name' => ['required', 'string', 'max:255'],
            'photo' => [
                Rule::requiredIf(
                    fn (): bool => (int) $request->input(
                        'type_id'
                    ) === PosAdvertisementTypes::IMAGE->value && null === $request->photo_url
                ),
                'nullable',
                'file',
                'max:' . config('services.max_upload_size'),
                'mimetypes:image/jpeg,image/gif,image/png',
                File::image()->dimensions(Rule::dimensions()->maxWidth(1920)->maxHeight(1280)),
            ],
            'video' => [
                Rule::requiredIf(
                    fn (): bool => (int) $request->input(
                        'type_id'
                    ) === PosAdvertisementTypes::VIDEO->value && null === $request->video_url
                ),
                'nullable',
                'file',
                'mimetypes:video/mp4,video/avi,video/mpeg',
                'max:' . config('services.max_upload_size'),
            ],
            'location_ids' => ['required', 'array'],
            'location_ids.*' => ['required', 'integer'],
            'status' => ['required', 'boolean'],
        ];
    }
}
