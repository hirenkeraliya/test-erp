<?php

declare(strict_types=1);

namespace App\Domains\GenuineReceiptVerification\Services;

use App\Domains\Common\Services\ExportService;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\GenuineReceiptVerification\GenuineReceiptVerificationQueries;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReceiptVerificationReportService
{
    public function print(array $filterData, Collection $filterColumns, int $companyId): string
    {
        $genuineReceiptVerificationQueries = resolve(GenuineReceiptVerificationQueries::class);

        $prepareFilterData = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $prepareFilterData->buildFilterData($filterData);

        $genuineReceiptVerificationData = $genuineReceiptVerificationQueries->getReceiptVerificationReportDataForExport(
            $filterData,
            $companyId
        );

        $receiptVerificationsData = $this->preparedData($genuineReceiptVerificationData, $filterColumns);

        return view('prints.receipt_verification_report', [
            'receiptVerificationData' => $receiptVerificationsData,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }

    public function preparedData(Collection $genuineReceiptVerifications, Collection $filterColumns): Collection
    {
        return $genuineReceiptVerifications->map(function ($genuineReceiptVerification) use ($filterColumns): array {
            $genuineReceiptVerificationData = [
                'name' => $genuineReceiptVerification->name,
                'mobile_number' => $genuineReceiptVerification->mobile_number,
                'email' => $genuineReceiptVerification->email,
                'is_genuine' => $genuineReceiptVerification->is_genuine ? 'Genuine' : 'Fake',
                'receipt_number' => $genuineReceiptVerification->receipt_number,
                'created_at' => $genuineReceiptVerification->created_at->format('d-m-Y D h:s:i A'),
                'remarks' => $genuineReceiptVerification->remarks,
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($genuineReceiptVerificationData, $filterColumns);
        });
    }
}
