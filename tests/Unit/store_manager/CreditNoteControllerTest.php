<?php

declare(strict_types=1);

use App\Domains\CreditNote\CreditNoteQueries;
use App\Http\Controllers\StoreManager\CreditNoteController;
use App\Models\CreditNote;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedListByCompanyWithRelationsForStoreManager query method of the CreditNoteQueries class and returns proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'cashier_id' => null,
            'counter_ids' => null,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
            'credit_note_id' => null,
        ];

        $counterQueries = $this->mock(CreditNoteQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('getPaginatedListByCompanyWithRelationsForStoreManager')
                ->once()
                ->with($requestParameter, $companyId, $locationId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $counterController = new CreditNoteController($counterQueries);
        $response = $counterController->fetchCreditNotes(new Request($requestParameter));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the exportCreditNotes method and returns a proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'counter_ids' => null,
            'cashier_id' => null,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
            'credit_note_id' => null,
            'export_columns' => null,
        ];

        $counterQueries = $this->mock(CreditNoteQueries::class, function ($mock) use (
            $requestParameter,
            $companyId,
            $locationId
        ): void {
            $mock->shouldReceive('getCreditNoteListByCompanyWithRelationsForExportInStoreManagerPanel')
                ->once()
                ->with($requestParameter, $companyId, $locationId)
                ->andReturn(collect(new CreditNote()));
        });

        $counterController = new CreditNoteController($counterQueries);
        $response = $counterController->exportCreditNotes('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);
