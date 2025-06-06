<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\CreditNote\DataObjects\PaginatedListOfActiveCreditNotesDataForPos;
use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNote\Resources\PosCreditNoteDetailsResource;
use App\Domains\CreditNote\Resources\PosCreditNoteResource;
use App\Domains\CreditNote\Services\CheckCreditNoteRefundRequestService;
use App\Domains\CreditNoteRefund\CreditNoteRefundQueries;
use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Domains\Location\LocationQueries;
use App\Domains\PosMismatch\PosMismatchQueries;
use App\Domains\PosMismatch\Services\PosMismatchService;
use App\Domains\StoreManagerAuthorizationCodeUsage\Enum\StoreManagerAuthorizationCodeUsageTypes;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreditNoteController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedListOfActiveCreditNotes(
        Request $request,
        PaginatedListOfActiveCreditNotesDataForPos $paginatedListOfActiveCreditNotesDataForPos
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->counter_update_id);

        $filterData = [
            'per_page' => $paginatedListOfActiveCreditNotesDataForPos->per_page,
            'sort_by' => $paginatedListOfActiveCreditNotesDataForPos->sort_by,
            'sort_direction' => $paginatedListOfActiveCreditNotesDataForPos->sort_direction,
            'search_text' => $paginatedListOfActiveCreditNotesDataForPos->search_text,
            'member_id' => $paginatedListOfActiveCreditNotesDataForPos->member_id,
            'employee_id' => $paginatedListOfActiveCreditNotesDataForPos->employee_id,
            'after_updated_at' => $paginatedListOfActiveCreditNotesDataForPos->after_updated_at,
        ];

        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNote = $creditNoteQueries->getPaginatedListOfActiveCreditNotes($filterData, $companyId, $location->id);

        return [
            'credit_notes' => PosCreditNoteResource::collection($creditNote),
            'total_records' => $creditNote->total(),
            'last_page' => $creditNote->lastPage(),
            'current_page' => $creditNote->currentPage(),
            'per_page' => $creditNote->perPage(),
        ];
    }

    public function refundCreditNote(
        Request $request,
        CreditNoteRefundData $creditNoteRefundData,
        int $creditNoteId
    ): array {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        if (! $cashier->getCounterUpdateId()) {
            abort(412, 'The counter has not been opened yet.');
        }

        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNote = $creditNoteQueries->getById($creditNoteId);

        $checkCreditNoteRefundRequestService = resolve(CheckCreditNoteRefundRequestService::class);
        $checkCreditNoteRefundRequestService->setDetails();

        $checkCreditNoteRefundRequestService->checkRequestDetails(
            $creditNoteRefundData,
            $creditNote,
            $cashier->getCounterUpdateId()
        );

        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $posMismatchQueries = resolve(PosMismatchQueries::class);

        DB::beginTransaction();

        try {
            $creditNoteQueries->markAsRefunded($creditNote);

            $creditNoteRefundQueries->addNew($creditNote->id, $cashier->getCounterUpdateId(), $creditNoteRefundData);

            $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
            $storeManagerAuthorizationCodeUsageService->addStoreManagerAuthorizationCodeUsage(
                StoreManagerAuthorizationCodeUsageTypes::CREDIT_NOTE_REFUND->value,
                $creditNote->id,
                ModelMapping::CREDIT_NOTE->name,
                $creditNoteRefundData->store_manager_authorization_code
            );

            foreach ($checkCreditNoteRefundRequestService->creditNoteMismatches as $creditNoteMismatch) {
                $posMismatchQueries->addNew($creditNote, $creditNoteMismatch);
            }

            DB::commit();

            $creditNote = $creditNoteQueries->loadMismatches($creditNote);

            if ($creditNote->mismatches->isNotEmpty()) {
                $messages = $creditNote->mismatches->pluck('message')->toArray();

                $posMismatchService = resolve(PosMismatchService::class);
                $posMismatchService->logMismatchEntries(
                    'Credit Note Refund Mismatches',
                    $creditNote->id,
                    $messages,
                    null
                );
            }

            return [
                'credit_note' => new PosCreditNoteResource($creditNote),
            ];
        } catch (Throwable $throwable) {
            Log::error('Refund-Credit-Note', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollback();

            abort(412, 'An error occurred. Please try again.');
        }
    }

    /**
     * @return array<string, PosCreditNoteDetailsResource>
     */
    public function getCreditNoteDetails(Request $request, int|string $creditNoteId): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        if (! $cashier->counter_update_id) {
            abort(412, 'The counter has not been opened yet.');
        }

        $locationQueries = resolve(LocationQueries::class);
        $location = $locationQueries->getLocationByCountersCounterUpdateId($cashier->counter_update_id);

        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $creditNote = $creditNoteQueries->getCreditNoteDetails($location->id, $companyId, $creditNoteId);

        return [
            'credit_note' => new PosCreditNoteDetailsResource($creditNote),
        ];
    }

    public function getStatuses(): array
    {
        return [
            'statuses' => CreditNoteStatuses::getList(),
        ];
    }
}
