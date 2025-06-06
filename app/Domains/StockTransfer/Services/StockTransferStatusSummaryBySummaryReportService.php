<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Services;

use App\Domains\Company\CompanyQueries;
use App\Domains\CustomReport\Services\CustomReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Exports\StockTransferStatusSummaryExportExport;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockTransferStatusSummaryBySummaryReportService
{
    public function renderPreparedBySummary(array $filterData, Company $company, Collection $locations): string
    {
        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfers = $stockTransferQueries->getStockTransferByStatusSummary($filterData, $company->id);

        $stockTransfersData = $this->preparedByDetails($stockTransfers, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return view('prints.stock_transfer_status_by_summary', [
            'stockTransfers' => $stockTransfersData,
            'dateRange' => $customReportService->prepareDateRange($filterData),
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'company' => $company,
            'locations' => $locations->implode('name', ', '),
            'manDaysStatus' => StatusTypes::getCaseNameByValue($filterData['status']),
        ])->render();
    }

    public function exportStockTransferStatusSummaryReportExport(
        int $companyId,
        array $filterData,
        string $filename,
        Collection $locations,
    ): BinaryFileResponse {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $stockTransferQueries = resolve(StockTransferQueries::class);
        $stockTransfers = $stockTransferQueries->getStockTransferByStatusSummary($filterData, $company->id);

        $stockTransfersData = $this->preparedByDetails($stockTransfers, $filterData);

        $customReportService = resolve(CustomReportService::class);

        return Excel::download(
            new StockTransferStatusSummaryExportExport(
                $stockTransfersData,
                $customReportService->prepareDateRange($filterData),
                $company,
                $locations,
                $filterData,
            ),
            $filename
        );
    }

    public function preparedByDetails(Collection $stockTransfers, array $filterData): array
    {
        $data = [];

        foreach ($stockTransfers as $stockTransfer) {
            $totalManDays = 0;
            $data[$stockTransfer->id] = [
                'transfer_order_number' => $stockTransfer->transfer_order_number,
                'request_order_number' => $stockTransfer->request_order_number,
                'transfer_out_number' => $stockTransfer->transfer_out_number,
                'transfer_in_number' => $stockTransfer->transfer_in_number,
                'destination_location' => $stockTransfer->destinationLocation->name .' ('.LocationTypes::getCaseNameByValue(
                    $stockTransfer->destinationLocation->type_id
                ) . ')',
            ];
            $latestTransaction = $stockTransfer->transactions->last();

            if ((int) $filterData['status'] === StatusTypes::DRAFT->value) {
                $oldestTransaction = $stockTransfer;
            } else {
                $oldestTransaction = $stockTransfer->transactions->where('new_status', $filterData['status'])->first();
            }

            if ($latestTransaction && $oldestTransaction) {
                $latestTransactionDate = Carbon::createFromFormat('Y-m-d H:i:s', $latestTransaction->created_at);
                $oldestTransactionDate = Carbon::createFromFormat('Y-m-d H:i:s', $oldestTransaction->created_at);
                if (false !== $latestTransactionDate && false !== $oldestTransactionDate) {
                    $totalManDays = $latestTransactionDate->diffInDays($oldestTransactionDate);
                }
            }

            $data[$stockTransfer->id]['total_man_days'] = $totalManDays;
            $data[$stockTransfer->id]['transactions'] = [];
            $previousTimestamp = $stockTransfer->created_at;

            foreach ($stockTransfer->transactions as $transaction) {
                $transactionDate = Carbon::createFromFormat('Y-m-d H:i:s', $transaction->created_at);
                $previousDate = Carbon::createFromFormat('Y-m-d H:i:s', $previousTimestamp);

                if (false !== $transactionDate && false !== $previousDate) {
                    $data[$stockTransfer->id]['transactions'][] = [
                        'label' => StatusTypes::getCaseNameByValue(
                            $transaction->old_status
                        ) . ' to ' . StatusTypes::getCaseNameByValue($transaction->new_status),
                        'date' => $transactionDate->format('d-m-Y h:i:s A'),
                        'human_readable_date' => $previousDate->longAbsoluteDiffForHumans($transactionDate),
                    ];
                    $previousTimestamp = $transaction->created_at;
                }
            }
        }

        return $data;
    }
}
