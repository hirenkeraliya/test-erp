<?php

declare(strict_types=1);

namespace App\Policy;

use Spatie\DirectoryCleanup\Policies\CleanupPolicy;
use Symfony\Component\Finder\SplFileInfo;

class LogExtensionFilesCleanup implements CleanupPolicy
{
    public function shouldDelete(SplFileInfo $file): bool
    {
        $path = $file->getPath();

        if (str_contains($path, 'storage/logs')) {
            return $file->getExtension() === 'log';
        }

        if (str_contains($path, 'storage/app')) {
            return $file->getExtension() === 'pdf';
        }

        // Default: Allow cleanup based on age only
        return true;
    }
}
