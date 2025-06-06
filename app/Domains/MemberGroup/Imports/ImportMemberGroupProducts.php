<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Imports;

use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Interfaces\ImportRecordClassInterface;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\MemberGroup\Enums\ImportMemberGroupProductsColumn;
use App\Domains\MemberGroup\MemberGroupQueries;
use App\Domains\Product\ProductQueries;
use App\Models\ImportRecord;
use Illuminate\Support\Collection;

class ImportMemberGroupProducts implements ImportRecordClassInterface
{
    /**
     * @return mixed[]
     */
    public function validate(array $productDetails, ImportRecord $importRecord): array
    {
        $validationErrors = [];
        $productQueries = resolve(ProductQueries::class);

        if (empty($productDetails['upc'])) {
            $validationErrors[] = 'A upc is required.';

            return $validationErrors;
        }

        $upc = (string) $productDetails['upc'];
        if (! $productQueries->existsByUpc($upc, $importRecord->company_id)) {
            $validationErrors[] = 'The provided upc does not exist in our records.';
        }

        return $validationErrors;
    }

    public function save(array $productDetails, ImportRecord $importRecord): void
    {
        $productQueries = resolve(ProductQueries::class);
        $memberGroupQueries = resolve(MemberGroupQueries::class);

        $memberGroup = $memberGroupQueries->getByIdForImportRecord(
            (int) $importRecord->module_id,
            $importRecord->company_id
        );
        $productId = $productQueries->getIdByUpc((string) $productDetails['upc'], $importRecord->company_id);

        /** @var Collection $products */
        $products = $memberGroup->products;

        if ($productId && ! in_array($productId, $products->pluck('id')->toArray())) {
            $memberGroupQueries->addProductInPivot($productId, $memberGroup);
        }
    }

    public function validateColumns(array $uploadHeaderColumns, array $allowedPermissionLists, int $companyId): array
    {
        $requiredHeaderColumns = collect(ImportMemberGroupProductsColumn::cases())->pluck('value')->toArray();
        $importRecordService = resolve(ImportRecordService::class);

        return [
            'type' => ColumnValidationIssueTypes::COLUMN_ISSUE->value,
            'status' => $importRecordService->validateColumn($requiredHeaderColumns, $uploadHeaderColumns),
        ];
    }
}
