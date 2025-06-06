<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Imports;

use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotification\Enums\AutomatedNotificationProductImportColumns;
use App\Domains\AutomatedNotificationProduct\AutomatedNotificationProductQueries;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\Product\ProductQueries;
use App\Models\ImportRecord;

class ImportAutomatedNotificationProducts implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $productQueries = resolve(ProductQueries::class);

        if (! array_key_exists('upc', $productDetails) || ! $productDetails['upc']) {
            $validationErrors[] = 'A upc is required.';
        } else {
            $upc = (string) $productDetails['upc'];
            $product = $productQueries->checkProductByUpc($upc, $importRecord->company_id);

            if (! $product) {
                $validationErrors[] = 'The provided UPC does not exist in our records.';
            }
        }

        if (! array_key_exists(
            'low_stock_alert_threshold',
            $productDetails
        ) || ! $productDetails['low_stock_alert_threshold']) {
            $validationErrors[] = 'A low_stock_alert_threshold is required.';
        }

        $productId = $productQueries->getIdByUpc((string) $productDetails['upc'], $importRecord->company_id);
        if ($productId) {
            $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);

            $automatedNotification = $automatedNotificationQueries->getByIdWithStores(
                (int) $importRecord->module_id,
                $importRecord->company_id
            );
            $locations = $automatedNotification->locations;

            foreach ($locations as $location) {
                $automatedNotification = $automatedNotificationQueries->checkExistsByProductLocationAndCompany(
                    $importRecord->company_id,
                    $productId,
                    $location->id
                );

                if ($automatedNotification) {
                    $validationErrors[] = 'The provided UPC with location '.$location->name.' already in our records';
                }
            }
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);
        $automatedNotificationProductQueries = resolve(AutomatedNotificationProductQueries::class);
        $productQueries = resolve(ProductQueries::class);

        $productId = $productQueries->getIdByUpc((string) $productDetails['upc'], $importRecord->company_id);

        $automatedNotification = $automatedNotificationQueries->getByIdWithStores(
            (int) $importRecord->module_id,
            $importRecord->company_id
        );
        $locations = $automatedNotification->locations;

        foreach ($locations as $location) {
            $data = [
                'automated_notification_id' => $automatedNotification->id,
                'location_id' => $location->id,
                'product_id' => $productId,
                'low_stock_alert_threshold' => (int) $productDetails['low_stock_alert_threshold'],
            ];

            $automatedNotificationProductQueries->addNewOrUpdate($data);
        }
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(AutomatedNotificationProductImportColumns::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
