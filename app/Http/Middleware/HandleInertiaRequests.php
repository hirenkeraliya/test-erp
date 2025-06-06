<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\CommonFunctions;
use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Media\MediaQueries;
use App\Domains\Panel\Service\PanelManagementService;
use App\Domains\Permission\PermissionQueries;
use App\Domains\Role\RoleQueries;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\SiteConfiguration\SiteConfigurationQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\StoreManager;
use App\Models\SuperAdmin;
use App\Models\WarehouseManager;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(Request $request)
    {
        $this->rootView = 'admin.index';

        if (PanelManagementService::requestForSuperAdmin($request)) {
            $this->rootView = 'super_admin.index';
        }

        if (PanelManagementService::requestForStoreManager($request)) {
            $this->rootView = 'store_manager.index';
        }

        if (PanelManagementService::requestForWarehouseManager($request)) {
            $this->rootView = 'warehouse_manager.index';
        }
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return mixed[]
     */
    public function share(Request $request): array
    {
        $guard = null;
        if (PanelManagementService::requestForSuperAdmin($request)) {
            $guard = 'super_admin';
        }

        if (PanelManagementService::requestForAdmin($request)) {
            $guard = 'admin';
        }

        if (PanelManagementService::requestForStoreManager($request)) {
            $guard = 'store_manager';
        }

        if (PanelManagementService::requestForWarehouseManager($request)) {
            $guard = 'warehouse_manager';
        }

        $employeeQueries = resolve(EmployeeQueries::class);
        $siteConfigurationQueries = resolve(SiteConfigurationQueries::class);
        $getSiteConfigurationTheme = $siteConfigurationQueries->getCachedThemeConfiguration();
        $getSiteConfigurationLoginPageLogo = $siteConfigurationQueries->getCachedLoginPageLogoConfiguration();
        $getSiteConfigurationFavIcon = $siteConfigurationQueries->getCachedFavIconConfiguration();
        $getSiteConfigurationLoginPageTagline = $siteConfigurationQueries->getCachedLoginPageTaglineConfiguration();
        $getSiteConfigurationLoginPageSubTagline = $siteConfigurationQueries->getCachedLoginPageSubTaglineConfiguration();
        $getSiteConfigurationNavbarLogo = $siteConfigurationQueries->getCachedNavbarLogoConfiguration();

        /** @var User|null $user */
        $user = $request->user($guard);
        $route = $request->route();
        $loggedInUsername = $user instanceof User ? $user->only('username')['username'] : null;
        $loggedInUserStaffId = null;

        $permissions = null;

        if ('super_admin' === $guard && $user) {
            /** @var SuperAdmin $superAdmin */
            $superAdmin = $user;
            $loggedInUsername = $superAdmin->getName();
        }

        if ('admin' === $guard && $user) {
            /** @var Admin $admin */
            $admin = $user;

            $employee = $employeeQueries->getColumnsForPanelHeader($admin->employee_id);
            $loggedInUsername = $employee->getFullName();
            $loggedInUserStaffId = $employee->staff_id;

            if (config('app.env') !== 'local') {
                $roleQueries = resolve(RoleQueries::class);
                $permissionQueries = resolve(PermissionQueries::class);

                $admin->load(
                    'roles:' . $roleQueries->getBasicColumns(),
                    'roles.permissions:' . $permissionQueries->getBasicColumns()
                );

                $permissions = $admin->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
            }

            [$companyName, $companyLogo, $currencySymbol] = $this->getCompanyNameAndLogo($admin);
        }

        if ('store_manager' === $guard && $user) {
            /** @var StoreManager $storeManager */
            $storeManager = $user;

            if (config('app.env') !== 'local') {
                $roleQueries = resolve(RoleQueries::class);
                $permissionQueries = resolve(PermissionQueries::class);

                $storeManager->load(
                    'roles:' . $roleQueries->getBasicColumns(),
                    'roles.permissions:' . $permissionQueries->getBasicColumns()
                );

                $permissions = $storeManager->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
            }

            [$companyName, $companyLogo, $currencySymbol] = $this->getCompanyNameAndLogo($storeManager);
            $employee = $employeeQueries->getColumnsForPanelHeader($storeManager->employee_id);
            $loggedInUsername = $employee->getFullName();
            $loggedInUserStaffId = $employee->staff_id;
        }

        if ('warehouse_manager' === $guard && $user) {
            /** @var WarehouseManager $warehouseManager */
            $warehouseManager = $user;

            if (config('app.env') !== 'local') {
                $roleQueries = resolve(RoleQueries::class);
                $permissionQueries = resolve(PermissionQueries::class);

                $warehouseManager->load(
                    'roles:' . $roleQueries->getBasicColumns(),
                    'roles.permissions:' . $permissionQueries->getBasicColumns()
                );

                $permissions = $warehouseManager->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
            }

            [$companyName, $companyLogo, $currencySymbol] = $this->getCompanyNameAndLogo($warehouseManager);
            $employee = $employeeQueries->getColumnsForPanelHeader($warehouseManager->employee_id);
            $loggedInUsername = $employee->getFullName();
            $loggedInUserStaffId = $employee->staff_id;
        }

        return array_merge(parent::share($request), [
            'logged_in_user_name' => $loggedInUsername,
            'logged_in_user_staff_id' => $loggedInUserStaffId,
            'company_logo' => $companyLogo ?? '',
            'company_name' => $companyName ?? '',
            'settings' => [
                'color' => $getSiteConfigurationTheme ? CommonFunctions::stringToKebabCase(
                    ThemeColors::getFormattedCaseName(
                        $getSiteConfigurationTheme->value
                    ) . '-' . $getSiteConfigurationTheme->type_id->name
                ) : 'dark-purple-theme',
                'login_page_logo' => $getSiteConfigurationLoginPageLogo ?
                    $getSiteConfigurationLoginPageLogo->getDiskBasedFirstMediaUrl('login_page_logo') : null,
                'fav_icon' => $getSiteConfigurationFavIcon ?
                    $getSiteConfigurationFavIcon->getDiskBasedFirstMediaUrl('favicon_icon') : null,
                'login_page_tagline' => $getSiteConfigurationLoginPageTagline ?
                    $getSiteConfigurationLoginPageTagline->value : 'Just a few clicks away from accessing your account.',
                'login_page_sub_tagline' => $getSiteConfigurationLoginPageSubTagline ?
                    $getSiteConfigurationLoginPageSubTagline->value : 'Seamlessly manage your retail operations from here.',
                'navbar_logo' => $getSiteConfigurationNavbarLogo ?
                    $getSiteConfigurationNavbarLogo->getDiskBasedFirstMediaUrl('navbar_logo') : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'current_route_name' => fn (): ?string => null !== $route
                ? $route->getName()
                : null,
            'currency_symbol' => $currencySymbol ?? '',
            'permissions' => $permissions,
            'barcode_count_threshold_for_async_print' => config('app.barcode_count_threshold_for_async_print'),
            'ioi_city_mall_sales_file_notification_email' => config('app.ioi_city_mall_sales_file_notification_email'),
            'demand_forecasting_dashboard_visibility' => config('app.demand_forecasting_dashboard_visibility'),
            'loyalty_campaign_configurations_visibility' => config('app.loyalty_campaign_configurations_visibility'),
            'product_variant' => config('app.product_variant'),
            'allow_different_color_in_chart' => config('app.allow_different_color_in_chart'),
            'environment' => config('app.env') !== 'production' ? config('app.env') : '',
        ]);
    }

    /**
     * @return mixed[]
     */
    private function getCompanyNameAndLogo(Admin|StoreManager|WarehouseManager $user): array
    {
        $user->load(
            [
                'employee:' . EmployeeQueries::getStatusAndCompanyIdColumns(),
                'employee.company:' . CompanyQueries::getBasicColumnNames(),
                'employee.company.media:' . MediaQueries::getBasicColumnNames(),
                'employee.company.media:' . MediaQueries::getBasicColumnNames(),
                'employee.company.defaultCountry:id',
                'employee.company.defaultCountry.currency:id,symbol,country_id',
            ]
        );

        /** @var Employee $employee */
        $employee = $user->employee;

        /** @var Company $company */
        $company = $employee->company;

        $companyName = $company->name;

        $companyLogo = $company->getDiskBasedFirstMediaUrl('light_logo');

        $currencySymbol = null;
        $country = $company->defaultCountry;
        $currency = $country?->currency;
        if ($currency instanceof Currency) {
            $currencySymbol = $currency->getSymbol();
        }

        return [$companyName, $companyLogo, $currencySymbol];
    }
}
