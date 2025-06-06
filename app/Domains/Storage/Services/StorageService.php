<?php

declare(strict_types=1);

namespace App\Domains\Storage\Services;

use App\Domains\Storage\Enums\StorageTypes;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    public function getPublicUrl(string $filePath): string
    {
        if (config('filesystems.default') === StorageTypes::OCI->value) {
            return Storage::temporaryUrl($filePath, now()->addMinutes(5));
        }

        return Storage::url($filePath);
    }

    public function getLocalFilePath(string $filePath): string
    {
        if (config('filesystems.default') === StorageTypes::OCI->value) {
            $fileUrl = Storage::temporaryUrl($filePath, now()->addMinutes(5));
            $fullFilePath = $this->saveFileToLocalStorage($fileUrl);

            return Storage::disk(StorageTypes::PUBLIC->value)->path($fullFilePath);
        }

        return Storage::path($filePath);
    }

    public function saveFileToLocalStorage(string $fileUrl): string
    {
        $client = new Client();
        $response = $client->get($fileUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        /** @var string $basePath */
        $basePath = parse_url($fileUrl, PHP_URL_PATH);
        $filename = Str::random(20) . '-' . basename($basePath);
        $fullFilePath = 'temporary_files/' . $filename;

        Storage::disk(StorageTypes::PUBLIC->value)->put($fullFilePath, $response->getBody());

        return $fullFilePath;
    }
}
