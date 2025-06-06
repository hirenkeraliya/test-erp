<?php

declare(strict_types=1);

namespace App\Domains\StoreManager\Services;

use App\Domains\Brand\BrandQueries;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Role\RoleQueries;
use App\Domains\StoreManager\DataObjects\StoreManagerData;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Models\ImportRecord;

class StoreManagerService
{
    public static function checkAuthorizationForStoreManager(int $storeManagerId, int $locationId): void
    {
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $storeManagerWithStoreExists = $storeManagerQueries->existsByIdAndStoreId($storeManagerId, $locationId);

        if (! $storeManagerWithStoreExists) {
            abort(412, 'You do not have authorization for the selected location.');
        }
    }

    public function preparedImportRecords(
        array $storeManagerDetails,
        ImportRecord $importRecord,
        int $employeeId
    ): StoreManagerData {
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $companyQueries = resolve(CompanyQueries::class);
        $roleQueries = resolve(RoleQueries::class);

        $companyConfiguration = $companyQueries->getConfigurationColumnsById($importRecord->company_id);

        $priceOverrideLimitPercentageForCart = 0;

        if ($companyConfiguration->allow_price_override_cart_level) {
            $priceOverrideLimitPercentageForCart = $storeManagerDetails['price_override_limit_percentage_for_cart'];
        }

        $priceOverrideType = (int) PriceOverrideTypes::getValueByCaseName($storeManagerDetails['price_override_type']);

        $storeManagerLocations = explode(',', $storeManagerDetails['locations']);
        $storeManagerRoles = explode(',', $storeManagerDetails['roles']);

        $brandIds = [];
        if (array_key_exists('brands', $storeManagerDetails) && null !== $storeManagerDetails['brands']) {
            $storeManagerBrands = explode(',', $storeManagerDetails['brands']);
            $brands = $brandQueries->existsByNames(array_map('trim', $storeManagerBrands), $importRecord->company_id);
            $brandIds = $brands->pluck('id')->toArray();
        }

        $locations = $locationQueries->getIdAndNameByNames(
            array_map('trim', $storeManagerLocations),
            $importRecord->company_id
        );
        $locationIds = $locations->map(fn ($location) => $location->id)->toArray();

        $roles = $roleQueries->getIdAndNameByNames(array_map('trim', $storeManagerRoles), 'store_manager');
        $roleIds = $roles->map(fn ($role) => $role->id)->toArray();

        $canManageWholesale = false;
        if (array_key_exists(
            'can_manage_wholesale',
            $storeManagerDetails
        ) && 'Yes' === $storeManagerDetails['can_manage_wholesale']) {
            $canManageWholesale = true;
        }

        return new StoreManagerData(
            employee_id: $employeeId,
            username: (string) $storeManagerDetails['username'],
            password: $storeManagerDetails['password'],
            passcode: (string) $storeManagerDetails['passcode'],
            price_override_type: $priceOverrideType,
            price_override_limit_percentage_for_item: ($priceOverrideType === PriceOverrideTypes::PERCENTAGE->value) ? (float) $storeManagerDetails['price_override_limit_percentage_for_item'] : null,
            price_override_limit_percentage_for_cart: $priceOverrideLimitPercentageForCart,
            can_manage_wholesale: $canManageWholesale,
            location_ids: $locationIds,
            role_ids: $roleIds,
            brand_ids: $brandIds,
        );
    }
}
