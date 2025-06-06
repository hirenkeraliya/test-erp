<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Jobs;

use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecordFailedRow\ImportRecordFailedRowQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\Product\Enums\ProductUploadTypes;
use App\Domains\Product\Events\EcommerceProductUpdateEvent;
use App\Domains\Product\ProductQueries;
use App\Domains\Storage\Enums\StorageTypes;
use App\Models\ImportRecord;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class ProductBulkMediaUploadJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected readonly ImportRecord $importRecord,
        protected readonly int $productUploadType,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $zipArchive = resolve(ZipArchive::class);

        $fileFullPath = $importRecordQueries->getFilePath($this->importRecord);
        $directoryPath = '/extract/' . $this->importRecord->getKey() . '/';

        try {
            $this->prepareDirectory($directoryPath);
            $this->extractZipFile($zipArchive, $fileFullPath, $directoryPath);

            $files = Storage::disk(StorageTypes::PUBLIC->value)->files('extract/' . $this->importRecord->getKey());
            $allValidationErrors = $this->processFiles($files, $productQueries, $importRecordQueries);

            $this->saveFailedRecordDetails($allValidationErrors);

            $this->updateImportRecordCompleted($importRecordQueries);
        } catch (Throwable $throwable) {
            Log::error('Product Bulk Media Upload Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }

    private function prepareDirectory(string $directoryPath): void
    {
        if (Storage::disk(StorageTypes::PUBLIC->value)->exists($directoryPath)) {
            Storage::disk(StorageTypes::PUBLIC->value)->deleteDirectory($directoryPath);
        }

        Storage::disk(StorageTypes::PUBLIC->value)->makeDirectory($directoryPath);
    }

    private function extractZipFile(ZipArchive $zipArchive, string $fileFullPath, string $directoryPath): void
    {
        if ($zipArchive->open($fileFullPath) === true) {
            $numFiles = $zipArchive->numFiles;

            for ($i = 0; $i < $numFiles; $i++) {
                $filename = (string) $zipArchive->getNameIndex($i);

                if (! str_ends_with($filename, '/')) {
                    $targetPath = $directoryPath . '/' . basename($filename);
                    $fileContent = (string) $zipArchive->getFromIndex($i);
                    Storage::disk(StorageTypes::PUBLIC->value)->put($targetPath, $fileContent);
                }
            }

            $zipArchive->close();
        }
    }

    private function processFiles(
        array $files,
        ProductQueries $productQueries,
        ImportRecordQueries $importRecordQueries
    ): array {
        $allValidationErrors = [];

        foreach ($files as $file) {
            $validationErrors = $this->validateFile($file);

            if ([] === $validationErrors) {
                $validationErrors = $this->handleValidFile($file, $productQueries, $importRecordQueries);
            }

            if ([] !== $validationErrors) {
                $importRecordQueries->incrementFailedRecordsCount($this->importRecord);
                $allValidationErrors = array_merge($allValidationErrors, $validationErrors);
            }
        }

        return $allValidationErrors;
    }

    private function validateFile(string $file): array
    {
        $validationErrors = [];
        $mimeType = Storage::disk(StorageTypes::PUBLIC->value)->mimeType($file);

        if (in_array(
            $this->productUploadType,
            [ProductUploadTypes::THUMBNAIL->value, ProductUploadTypes::IMAGES->value])) {
            $this->isValidImageMimeTypes($mimeType, basename($file), $validationErrors);
            [$width, $height] = $this->getImageSize($file);

            if ($this->productUploadType === ProductUploadTypes::THUMBNAIL->value) {
                $this->checkThumbnailSize($width, $height, basename($file), $validationErrors);
            } else {
                $this->checkImageSize($width, $height, basename($file), $validationErrors);
            }
        }

        if ($this->productUploadType === ProductUploadTypes::VIDEOS->value) {
            $this->isValidVideoMimeTypes($mimeType, basename($file), $validationErrors);
            $fileSize = $this->getVideoSize($file);
            $this->checkVideoSize($fileSize, basename($file), $validationErrors);
        }

        return $validationErrors;
    }

    private function handleValidFile(
        string $file,
        ProductQueries $productQueries,
        ImportRecordQueries $importRecordQueries
    ): array {
        $validationErrors = [];
        $filenameWithoutExtension = Str::beforeLast(basename($file), '.');
        $upc = $this->getUpcFromFilename($filenameWithoutExtension);
        $collection = $this->getCollectionType();

        $product = $productQueries->getByUpcAndCompanyId(trim($upc), $this->importRecord->company_id);
        if ($product instanceof Product) {
            $importRecordQueries->incrementImportedRecordsCount($this->importRecord);
            $product->addMedia(Storage::disk(StorageTypes::PUBLIC->value)->path($file))->toMediaCollection($collection);
            Storage::disk(StorageTypes::PUBLIC->value)->delete($file);

            $product->refresh();

            event(new EcommerceProductUpdateEvent($product));
        } else {
            $validationErrors[] = 'No product found for ' . $this->productUploadType . ' filename:' . basename($file);
        }

        return $validationErrors;
    }

    private function getUpcFromFilename(string $filenameWithoutExtension): string
    {
        if ($this->productUploadType === ProductUploadTypes::THUMBNAIL->value) {
            return $filenameWithoutExtension;
        }

        $explodeString = explode('_', $filenameWithoutExtension);

        return $explodeString[0] ?? '';
    }

    private function getCollectionType(): string
    {
        if ($this->productUploadType === ProductUploadTypes::THUMBNAIL->value) {
            return 'thumbnail';
        }

        return $this->productUploadType === ProductUploadTypes::VIDEOS->value ? 'videos' : 'images';
    }

    private function isValidImageMimeTypes(string|false $mimeType, string $imageName, array &$validationErrors): void
    {
        $validMimeTypes = ['image/jpeg', 'image/gif', 'image/png'];
        if (! in_array($mimeType, $validMimeTypes, true)) {
            $validationErrors[] = $imageName . ' has an invalid mime type. Allowed types are: ' . implode(
                ', ',
                $validMimeTypes
            );
        }
    }

    private function isValidVideoMimeTypes(string|false $mimeType, string $imageName, array &$validationErrors): void
    {
        $validMimeTypes = ['video/mp4', 'video/avi', 'video/mpeg'];
        if (! in_array($mimeType, $validMimeTypes, true)) {
            $validationErrors[] = $imageName . ' has an invalid mime type. Allowed types are: ' . implode(
                ', ',
                $validMimeTypes
            );
        }
    }

    private function getImageSize(string $image): array
    {
        $imageSize = getimagesize(Storage::disk(StorageTypes::PUBLIC->value)->path($image));

        $width = 0;
        $height = 0;
        if (is_array($imageSize)) {
            return [$width, $height] = $imageSize;
        }

        return [$width, $height];
    }

    private function checkThumbnailSize(int $width, int $height, string $image, array &$validationErrors): void
    {
        $desiredWidth = 343;
        $desiredHeight = 260;

        if ($width <= 0 || $height <= 0 || $width > $desiredWidth || $height > $desiredHeight) {
            $validationErrors[] = 'Thumbnail ' . $image . ' does not have dimensions of 343x260 pixels.';
        }
    }

    private function checkImageSize(int $width, int $height, string $image, array &$validationErrors): void
    {
        $desiredWidth = 500;
        $desiredHeight = 500;

        if ($width <= 0 || $height <= 0 || $width > $desiredWidth || $height > $desiredHeight) {
            $validationErrors[] = 'Image ' . $image . ' does not have dimensions of 500x500 pixels.';
        }
    }

    private function getVideoSize(string $video): float
    {
        $filePath = Storage::disk(StorageTypes::PUBLIC->value)->path($video);
        $fileSizeInBytes = File::size($filePath);

        return $fileSizeInBytes / 1024 / 1024;
    }

    private function checkVideoSize(float $fileSize, string $video, array &$validationErrors): void
    {
        $desiredSizeInMB = 15;

        if ($fileSize > $desiredSizeInMB) {
            $validationErrors[] = 'Video ' . $video . ' exceeds the maximum allowed size of '.$desiredSizeInMB.' MB.';
        }
    }

    private function saveFailedRecordDetails(array $validationErrors): void
    {
        $importRecordFailedRowQueries = resolve(ImportRecordFailedRowQueries::class);
        $importRecordFailedRowQueries->addNew([], $validationErrors, $this->importRecord->id);
    }

    private function updateImportRecordCompleted(ImportRecordQueries $importRecordQueries): void
    {
        $importRecordQueries->markAsCompleted($this->importRecord);

        Bus::chain([
            new GenerateFailedRecordsFileJob($this->importRecord->id, $this->importRecord->company_id),
            new SendImportRecordsCompletionEmailJob($this->importRecord->id, $this->importRecord->company_id),
        ])->onQueue(config('horizon.default_queue_name'))->dispatch();

        $this->addNotification($this->importRecord);
    }

    private function addNotification(ImportRecord $importRecord): void
    {
        $notificationQueries = resolve(NotificationQueries::class);
        $notificationQueries->addNew(
            companyId: $importRecord->company_id,
            sourceUser: null,
            fromUserId: null,
            destinationUser: $importRecord->created_by_type,
            toUserId: $importRecord->created_by_id,
            message: ImportTypes::getFormattedCaseName($importRecord->type_id).' import completed.',
            textMessage: ImportTypes::getFormattedCaseName($importRecord->type_id).' import completed.'
        );
    }
}
