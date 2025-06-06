<?php

declare(strict_types=1);

namespace App\Domains\PosModules\Services;

use App\Domains\Storage\Enums\StorageTypes;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class PosModuleZipService
{
    public function createModuleZip(string $moduleName): void
    {
        $defaultFileSystem = config('filesystems.default');

        $zipModuleFilePath = $defaultFileSystem === StorageTypes::PUBLIC->value ? 'pos_modules' : 'public/pos_modules';

        $directoryName = $zipModuleFilePath . '/' . $moduleName;

        $directories = Storage::directories($directoryName);
        $zipArchive = resolve(ZipArchive::class);

        foreach ($directories as $directory) {
            $companyFolderName = str_replace(sprintf('%s/%s/', $zipModuleFilePath, $moduleName), '', $directory);
            $zipFileName = Carbon::now()->format('d-m-Y-H-i-s') . '.zip';
            $zipPath = sprintf('%s/%s/%s/%s', $zipModuleFilePath, $moduleName, $companyFolderName, $zipFileName);
            $localPublicPath = sprintf('pos_modules/%s/%s', $moduleName, $companyFolderName);

            $localDirectoryPath = storage_path(
                sprintf('app/public/pos_modules/%s/%s', $moduleName, $companyFolderName)
            );
            $filesDirectoryPath = sprintf('%s/%s/%s/files', $zipModuleFilePath, $moduleName, $companyFolderName);

            $publicDisk = StorageTypes::PUBLIC->value;
            $ociDisk = StorageTypes::OCI->value;

            if (! Storage::disk($publicDisk)->exists($localPublicPath)) {
                Storage::disk($publicDisk)->makeDirectory($localPublicPath);
            }

            if ($defaultFileSystem === StorageTypes::OCI->value) {
                $filesToCopy = Storage::disk($ociDisk)->files($filesDirectoryPath);
                foreach ($filesToCopy as $file) {
                    $fileName = basename($file);
                    /** @var string $fileContents */
                    $fileContents = Storage::disk($ociDisk)->get($file);
                    Storage::disk($publicDisk)->put(sprintf('%s/files/%s', $localPublicPath, $fileName), $fileContents);
                }
            }

            $zipFilePath = sprintf('%s/%s', $localDirectoryPath, $zipFileName);

            if ($zipArchive->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = Storage::disk($publicDisk)->files($localPublicPath . '/files');

                foreach ($files as $file) {
                    $zipArchive->addFile(Storage::disk($publicDisk)->path($file), basename($file));
                }

                $zipArchive->close();
            }

            if ($defaultFileSystem === StorageTypes::OCI->value) {
                /** @var string $fileContents */
                $fileContents = Storage::disk($publicDisk)->get($localPublicPath . ('/' . $zipFileName));
                Storage::disk($ociDisk)->put($zipPath, $fileContents);
            }

            if ($defaultFileSystem !== StorageTypes::OCI->value) {
                Storage::disk($publicDisk)->deleteDirectory($localPublicPath . '/files');
            }

            if ($defaultFileSystem === StorageTypes::OCI->value) {
                Storage::disk($publicDisk)->deleteDirectory('pos_modules');
                Storage::disk($ociDisk)->deleteDirectory($filesDirectoryPath);
            }
        }
    }

    public function exportWithJsonGroupByCompanyId(
        Collection $collections,
        int $index,
        string $folderAndFileName,
        callable $resourceConverter
    ): void {
        $jsonFileName = Str::slug($folderAndFileName, '-') . '-' . $index . '.json';

        $defaultFileSystem = config('filesystems.default');

        $zipModuleFilePath = $defaultFileSystem === StorageTypes::PUBLIC->value ? 'pos_modules' : 'public/pos_modules';

        foreach ($collections->groupBy('company_id') as $companyId => $companyCollection) {
            $companyFolderPath = sprintf('%s/%s/%s/files', $zipModuleFilePath, $folderAndFileName, $companyId);
            $jsonDataFilepath = sprintf('%s/%s', $companyFolderPath, $jsonFileName);

            $this->storeJsonRecordsAndUpdateManifest($companyCollection, $jsonDataFilepath, $resourceConverter);

            $this->updateManifestFile($companyFolderPath, $jsonFileName, $folderAndFileName);
        }
    }

    public function removeModuleZipFiles(string $moduleName): void
    {
        $defaultFileSystem = config('filesystems.default');

        $zipModuleFilePath = $defaultFileSystem === StorageTypes::PUBLIC->value ? 'pos_modules' : 'public/pos_modules';

        $baseDirectory = $zipModuleFilePath . '/' . $moduleName;

        $companyDirectories = Storage::directories($baseDirectory);

        foreach ($companyDirectories as $companyDirectory) {
            $files = Storage::files($companyDirectory);

            $this->deleteFilesExceptLatest($files);
        }
    }

    private function deleteFilesExceptLatest(array $files): void
    {
        rsort($files);
        Storage::delete(array_slice($files, 1));
    }

    private function storeJsonRecordsAndUpdateManifest(
        Collection $collection,
        string $path,
        callable $resourceConverter
    ): void {
        $jsonRecords = call_user_func($resourceConverter, $collection)->toJson(JSON_PRETTY_PRINT);
        Storage::put($path, $jsonRecords);
    }

    private function updateManifestFile(
        string $companyFolderPath,
        string $jsonFileName,
        string $folderAndFileName
    ): void {
        $manifestFilePath = $companyFolderPath . '/manifest.json';
        $manifestContent = [];

        if (Storage::exists($manifestFilePath)) {
            $manifestContent = json_decode((string) Storage::get($manifestFilePath), true, 512, JSON_THROW_ON_ERROR);
        }

        $manifestContent[$folderAndFileName] ??= [];
        $manifestContent[$folderAndFileName][] = $jsonFileName;

        Storage::put($manifestFilePath, json_encode($manifestContent, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
