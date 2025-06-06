<?php

declare(strict_types=1);

use App\Domains\PosAdvertisement\DataObjects\PosAdvertisementData;
use App\Domains\PosAdvertisement\PosAdvertisementQueries;
use App\Http\Controllers\Admin\PosAdvertisementController;
use App\Models\PosAdvertisement;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the pos advertisement queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $posAdvertisementQueries = $this->mock(PosAdvertisementQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $posAdvertisementController = new PosAdvertisementController($posAdvertisementQueries);

        $response = $posAdvertisementController->fetchPosAdvertisement(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the addNew method of the pos advertisement queries class and returns proper response',
    function (): void {
        Storage::fake('public');

        $companyId = 1;

        $posAdvertisementRecord = PosAdvertisement::factory()->make([
            'company_id' => $companyId,
        ])->toArray();
        $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
        $posAdvertisementRecord['photo'] = $uploadedFile;
        $posAdvertisementRecord['video'] = null;
        $posAdvertisementRecord['location_ids'] = [1];
        unset($posAdvertisementRecord['company_id']);
        $posAdvertisementData = new PosAdvertisementData(...$posAdvertisementRecord);

        setCompanyIdInSession($companyId);

        $posAdvertisementQueries = $this->mock(PosAdvertisementQueries::class, function ($mock) use (
            $posAdvertisementData
        ): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($posAdvertisementData, 1);
        });

        $posAdvertisementController = new PosAdvertisementController($posAdvertisementQueries);
        $redirectResponse = $posAdvertisementController->store($posAdvertisementData);
        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Advertisement added successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/pos-advertisements', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the update method of the pos advertisement queries class and returns proper response',
    function (): void {
        $companyId = 1;

        $posAdvertisementRecord = PosAdvertisement::factory()->make([
            'company_id' => $companyId,
        ])->toArray();

        $posAdvertisementRecord['photo'] = null;
        $posAdvertisementRecord['location_ids'] = [2];
        $posAdvertisementRecord['video'] = null;
        unset($posAdvertisementRecord['company_id']);
        $posAdvertisementData = new PosAdvertisementData(...$posAdvertisementRecord);

        setCompanyIdInSession($companyId);

        $posAdvertisementQueries = $this->mock(PosAdvertisementQueries::class, function ($mock) use (
            $posAdvertisementData
        ): void {
            $mock->shouldReceive('update')
                ->once()
                ->with($posAdvertisementData, 1, 1);
        });

        $posAdvertisementController = new PosAdvertisementController($posAdvertisementQueries);

        $redirectResponse = $posAdvertisementController->update($posAdvertisementData, 1);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Advertisement updated successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/pos-advertisements', $redirectResponse->getTargetUrl());
    }
);

test('it calls the adminSetStatus method of posAdvertisementQueries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $posAdvertisement = PosAdvertisement::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);
    $posAdvertisementQueries = $this->mock(PosAdvertisementQueries::class, function ($mock) use (
        $posAdvertisement
    ): void {
        $mock->shouldReceive('adminSetStatus')
            ->once()
            ->with($posAdvertisement->id, 1, false);
    });

    $posAdvertisementController = new PosAdvertisementController($posAdvertisementQueries);

    $response = $posAdvertisementController->setStatus($posAdvertisement->id, false);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('admin/pos-advertisements', $response->getTargetUrl());
});

test('It calls the exportPosAdvertisement method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $posAdvertisementQueries = $this->mock(PosAdvertisementQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getPosAdvertisementExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new PosAdvertisement()));
    });

    $posAdvertisementController = new PosAdvertisementController($posAdvertisementQueries);

    $response = $posAdvertisementController->exportPosAdvertisement('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
