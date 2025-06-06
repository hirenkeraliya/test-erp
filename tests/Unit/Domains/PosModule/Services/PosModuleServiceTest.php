<?php

declare(strict_types=1);

use App\Domains\PosModules\Services\PosModuleZipService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

test('it creates a zip file for a module', function (): void {
    config()->set('filesystems.default', 'public');
    Storage::fake();
    $moduleName = 'example_module';

    $filesystemMock = Mockery::mock(FilesystemAdapter::class);

    $filesystemMock->shouldReceive('exists')
        ->twice()
        ->andReturn(true);

    $filesystemMock->shouldReceive('files')
        ->andReturn(['file1', 'file2']);

    $filesystemMock->shouldReceive('deleteDirectory')
        ->andReturn(true);

    $filesystemMock->shouldReceive('path')
        ->andReturnUsing(fn ($path) => storage_path('app/' . $path));

    Storage::shouldReceive('disk')
        ->with('public')
        ->andReturn($filesystemMock);

    Storage::shouldReceive('directories')
        ->once()
        ->with('pos_modules/' . $moduleName)
        ->andReturn(['pos_modules/example_module/company1', 'pos_modules/example_module/company2']);

    $this->mock(ZipArchive::class, function ($mock): void {
        $mock->shouldReceive('open')->andReturn(true);
        $mock->shouldReceive('addFile')->times(4);
        $mock->shouldReceive('close');
    });

    $this->app->make(PosModuleZipService::class)->createModuleZip($moduleName);
    config()->set('filesystems.default', 'local');
});
