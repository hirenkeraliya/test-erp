<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\Services;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Director\DirectorQueries;
use App\Domains\HappyHourDiscount\DataObjects\HappyHourDiscountDataForPos;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Domains\Style\StyleQueries;
use App\Models\Cashier;
use App\Models\CounterUpdate;
use App\Models\Employee;
use Illuminate\Support\Collection;

class HappyHourDiscountCheckService
{
    public Collection $happyHourDiscountMismatches;

    public function setDetails(): void
    {
        $this->happyHourDiscountMismatches = collect([]);
    }

    public function validateHappyHourData(
        HappyHourDiscountDataForPos $happyHourDiscountDataForPos,
        int $companyId,
        Cashier $cashier
    ): void {
        $this->checkAllowHappyHourDiscount($companyId);
        $this->checkAuthorized($happyHourDiscountDataForPos, $companyId);
        $this->checkProductTypeIds($happyHourDiscountDataForPos, $companyId);
        $this->checkHappenedAtDate($happyHourDiscountDataForPos, $cashier);
    }

    private function checkHappenedAtDate(
        HappyHourDiscountDataForPos $happyHourDiscountData,
        Cashier $cashier
    ): void {
        /** @var CounterUpdate $counterUpdate */
        $counterUpdate = $cashier->getCounterUpdate();

        /** @var string $posOpenDate */
        $posOpenDate = $counterUpdate->opened_by_pos_at ?? $counterUpdate->created_at?->format('Y-m-d H:i:s');

        if ($happyHourDiscountData->happened_at <= $posOpenDate) {
            CommonFunctions::addMismatchOrAbort(
                $this->happyHourDiscountMismatches,
                'happened_at date can not be past compared to counter opened_at date'
            );
        }
    }

    private function checkProductTypeIds(
        HappyHourDiscountDataForPos $happyHourDiscountDataForPos,
        int $companyId
    ): void {
        if (
            ProductTypes::BRAND->value === $happyHourDiscountDataForPos->product_type_id
            && $happyHourDiscountDataForPos->brand_ids
        ) {
            $brandQueries = resolve(BrandQueries::class);
            $doAllBrandExist = $brandQueries->doExistsById(
                $companyId,
                array_unique($happyHourDiscountDataForPos->brand_ids)
            );

            if (! $doAllBrandExist) {
                abort(412, 'Some of the brands are not available in over records');
            }
        }

        if (
            ProductTypes::CATEGORY->value === $happyHourDiscountDataForPos->product_type_id
            && $happyHourDiscountDataForPos->category_ids
        ) {
            $categoryQueries = resolve(CategoryQueries::class);
            $doAllCategoryExist = $categoryQueries->doAllCategoriesExist(
                $companyId,
                array_unique($happyHourDiscountDataForPos->category_ids)
            );

            if (! $doAllCategoryExist) {
                abort(412, 'Some of the categories are not available in over records');
            }
        }

        if (
            ProductTypes::STYLE->value === $happyHourDiscountDataForPos->product_type_id
            && $happyHourDiscountDataForPos->style_ids
        ) {
            $styleQueries = resolve(StyleQueries::class);
            $doAllStyleExist = $styleQueries->doAllStylesExist(
                $companyId,
                array_unique($happyHourDiscountDataForPos->style_ids)
            );

            if (! $doAllStyleExist) {
                abort(412, 'Some of the styles are not available in over records');
            }
        }

        if (
            ProductTypes::DEPARTMENTS->value === $happyHourDiscountDataForPos->product_type_id
            && $happyHourDiscountDataForPos->department_ids
        ) {
            $departmentQueries = resolve(DepartmentQueries::class);
            $doAllStyleExist = $departmentQueries->doAllDepartmentExist(
                $companyId,
                array_unique($happyHourDiscountDataForPos->department_ids)
            );

            if (! $doAllStyleExist) {
                abort(412, 'Some of the departments are not available in over records');
            }
        }
    }

    private function checkAuthorized(HappyHourDiscountDataForPos $happyHourDiscountDataForPos, int $companyId): void
    {
        if ($this->hasBothRolesAuthorized($happyHourDiscountDataForPos)) {
            CommonFunctions::addMismatchOrAbort(
                $this->happyHourDiscountMismatches,
                'At a time storeManager and director both are not allowed'
            );
        }

        if (! $this->hasStoreManagerOrDirectorAuthorized($happyHourDiscountDataForPos)) {
            CommonFunctions::addMismatchOrAbort(
                $this->happyHourDiscountMismatches,
                'storeManager or director required to authorized'
            );
        }

        if ($this->hasStoreManagerAuthorized($happyHourDiscountDataForPos)) {
            $this->checkStoreManager($happyHourDiscountDataForPos, $companyId);

            return;
        }

        if ($this->hasDirectorAuthorized($happyHourDiscountDataForPos)) {
            /** @var int directorId */
            $directorId = $happyHourDiscountDataForPos->director_id;

            /** @var string directorPasscode */
            $directorPasscode = $happyHourDiscountDataForPos->director_passcode;

            $this->checkDirector($directorId, $directorPasscode, $companyId);

            return;
        }
    }

    private function checkAllowHappyHourDiscount(int $companyId): void
    {
        $companyQueries = resolve(CompanyQueries::class);
        $allowHappyHourDiscount = $companyQueries->getAllowHappyHourDiscount($companyId);
        if (! $allowHappyHourDiscount) {
            CommonFunctions::addMismatchOrAbort(
                $this->happyHourDiscountMismatches,
                'The company does not allow to perform happy hour discount.'
            );
        }
    }

    private function checkStoreManager(HappyHourDiscountDataForPos $happyHourDiscountDataForPos, int $companyId): void
    {
        if (! $happyHourDiscountDataForPos->store_manager_id) {
            $saleMismatchMessage = 'Store manager id is required to authorized happy hour discount.';
            CommonFunctions::addMismatchOrAbort($this->happyHourDiscountMismatches, $saleMismatchMessage);

            return;
        }

        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManager = $storeManagerQueries->getByIdWithEmployee(
            $happyHourDiscountDataForPos->store_manager_id,
            $companyId
        );

        if (! $storeManager) {
            abort(412, 'Specified Store Manager does not correspond with our records.');
        }

        /** @var Employee $employee */
        $employee = $storeManager->employee;
        if (! $employee->getStatus()) {
            CommonFunctions::addMismatchOrAbort(
                $this->happyHourDiscountMismatches,
                'Specified Store Manager : ' . $employee->getFullName() . ' account is inactive. Please contact admin.'
            );
        }

        if ($storeManager->passcode !== $happyHourDiscountDataForPos->store_manager_passcode) {
            CommonFunctions::addMismatchOrAbort(
                $this->happyHourDiscountMismatches,
                'Store Manager passcode not correspond with our records.'
            );
        }

        $this->checkStoreManagerAuthorizationCode($happyHourDiscountDataForPos);
    }

    public function checkStoreManagerAuthorizationCode(HappyHourDiscountDataForPos $happyHourDiscountDataForPos): void
    {
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $this->happyHourDiscountMismatches,
            (int) $happyHourDiscountDataForPos->store_manager_id,
            $happyHourDiscountDataForPos->store_manager_authorization_code,
            $happyHourDiscountDataForPos->happened_at
        );
    }

    private function checkDirector(int $directorId, string $passcode, int $companyId): void
    {
        $directorQueries = resolve(DirectorQueries::class);
        $director = $directorQueries->getByIdWithEmployee($directorId, $companyId);

        if (! $director) {
            abort(412, 'Specified Director does not correspond with our records.');
        }

        /** @var Employee $employee */
        $employee = $director->employee;
        if (! $employee->getStatus()) {
            CommonFunctions::addMismatchOrAbort(
                $this->happyHourDiscountMismatches,
                'Specified Director : ' . $employee->getFullName() . ' account is inactive. Please contact admin.'
            );
        }

        if ($director->passcode !== $passcode) {
            CommonFunctions::addMismatchOrAbort(
                $this->happyHourDiscountMismatches,
                'Director passcode not correspond with our records.'
            );
        }
    }

    private function hasBothRolesAuthorized(HappyHourDiscountDataForPos $happyHourDiscountDataForPos): bool
    {
        return $this->hasStoreManagerAuthorized($happyHourDiscountDataForPos)
            && $this->hasDirectorAuthorized($happyHourDiscountDataForPos);
    }

    private function hasStoreManagerOrDirectorAuthorized(HappyHourDiscountDataForPos $happyHourDiscountDataForPos): bool
    {
        if ($this->hasStoreManagerAuthorized($happyHourDiscountDataForPos)) {
            return true;
        }

        return $this->hasDirectorAuthorized($happyHourDiscountDataForPos);
    }

    private function hasStoreManagerAuthorized(HappyHourDiscountDataForPos $happyHourDiscountDataForPos): bool
    {
        return $happyHourDiscountDataForPos->store_manager_id && $happyHourDiscountDataForPos->store_manager_passcode;
    }

    private function hasDirectorAuthorized(HappyHourDiscountDataForPos $happyHourDiscountDataForPos): bool
    {
        return $happyHourDiscountDataForPos->director_id && $happyHourDiscountDataForPos->director_passcode;
    }
}
