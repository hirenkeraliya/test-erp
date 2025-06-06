<?php

declare(strict_types=1);

use App\Domains\CreditNote\CreditNoteQueries;
use App\Http\Controllers\Admin\CreditNoteController;
use App\Models\CreditNote;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedListByCompanyWithRelations query method of the CreditNoteQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
            'location_ids' => null,
            'counter_ids' => [],
            'cashier_id' => null,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
            'employee_id' => null,
            'e_invoice_submitted' => null,
            'credit_note_id' => null,
        ];

        $counterQueries = $this->mock(CreditNoteQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedListByCompanyWithRelations')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getSumOfAvailableAmountByCompany')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(777);
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
        $companyId = 1;
        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'location_ids' => null,
            'counter_ids' => [],
            'cashier_id' => null,
            'date_range' => null,
            'member_id' => null,
            'status_id' => null,
            'employee_id' => null,
            'e_invoice_submitted' => null,
            'credit_note_id' => null,
            'export_columns' => null,
        ];

        $counterQueries = $this->mock(CreditNoteQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getCreditNoteListByCompanyWithRelationsForExport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(collect(new CreditNote()));
        });

        $counterController = new CreditNoteController($counterQueries);
        $response = $counterController->exportCreditNotes('filename.csv', new Request($requestParameter));

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);
