<?php

declare(strict_types=1);

use App\Http\Controllers\SuperAdmin\AdminController;
use App\Http\Controllers\SuperAdmin\Auth\ForgotPasswordController;
use App\Http\Controllers\SuperAdmin\Auth\LoginController;
use App\Http\Controllers\SuperAdmin\Auth\ResetPasswordController;
use App\Http\Controllers\SuperAdmin\BrandController;
use App\Http\Controllers\SuperAdmin\ChangePasswordController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Controllers\SuperAdmin\CourierController;
use App\Http\Controllers\SuperAdmin\DesignationController;
use App\Http\Controllers\SuperAdmin\EmployeeController;
use App\Http\Controllers\SuperAdmin\EmployeeGroupController;
use App\Http\Controllers\SuperAdmin\ExternalConnectionController;
use App\Http\Controllers\SuperAdmin\IntegrationController;
use App\Http\Controllers\SuperAdmin\LocationController;
use App\Http\Controllers\SuperAdmin\NotificationController;
use App\Http\Controllers\SuperAdmin\RoleController;
use App\Http\Controllers\SuperAdmin\SaleChannelController;
use App\Http\Controllers\SuperAdmin\SiteConfigurationController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\SuperAdmin\TwoFactorController;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

Route::prefix('super-admin')->name('super_admin.')->group(function (): void {
    Route::inertia('menu/{pageUrl}', 'menu/Index')->name('menu_page');
    Route::group([
        'middleware' => 'guest',
    ], function (): void {
        Route::controller(LoginController::class)->group(function (): void {
            Route::post('login', 'login')->name('login_user');
        });
        Route::inertia('', 'guest/Login')->name('login');
        Route::inertia('forgot-password', 'guest/ForgotPassword')->name('forgot_password');
        Route::controller(ForgotPasswordController::class)->group(function (): void {
            Route::post('forgot-password', 'forgotPassword')->name('send_forgot_password_email');
        });
        Route::controller(ResetPasswordController::class)->group(function (): void {
            Route::get('reset-password/{token}', 'index')->name('reset_password');
            Route::post('reset-password', 'resetPassword')->name('password_update');
        });
    });

    Route::prefix('2fa')->name('2fa.')->group(function (): void {
        Route::get('show-validation-page', [TwoFactorController::class, 'showValidationPage'])->name(
            'show_validation_page'
        );
        Route::post('validate', [TwoFactorController::class, 'validateOtp'])->name('validateOTP');

        Route::post('verify2fa/{id}', [TwoFactorController::class, 'confirm2FA'])->name('verify2fa');
    });

    Route::group([
        'middleware' => ['auth:super_admin', 'twoFactor'],
    ], function (): void {
        Route::post('/generate2fa/{superAdminId}', [TwoFactorController::class, 'generate2FA'])->name(
            'generate2fa'
        );
        Route::post('/disable2fa/{superAdminId}', [TwoFactorController::class, 'disable2FA'])->name('disable2fa');

        Route::get('health', HealthCheckResultsController::class);
        Route::inertia('dashboard', 'Dashboard')->name('dashboard');
        Route::post('change-password', [ChangePasswordController::class, 'update'])->name('change_password_update');
        Route::inertia('change-password', 'ChangePassword')->name('change_password');
        Route::controller(BrandController::class)->name('brands.')->group(function (): void {
            Route::get('brands', 'index')->name('index');
            Route::post('brands', 'store')->name('store_brand');
            Route::get('fetch-brands', 'fetchBrands')->name('fetch_brands');
            Route::get('brands/{brandId}/edit', 'edit')->name('edit_brand');
            Route::put('brands/{brandId}/update', 'update')->name('update_brand');
            Route::inertia('brands/create', 'brands/Manage')->name('create');
            Route::get('export-brands/{fileName}', 'exportBrands')->name('export_brands');
            Route::get('brand-sync-data/{saleChannelId}', 'syncData')->name('sync_data');
        });
        Route::controller(CompanyController::class)->name('companies.')->group(function (): void {
            Route::get('companies', 'index')->name('index');
            Route::get('companies/create', 'create')->name('create');
            Route::get('fetch-companies', 'fetchCompanies')->name('fetch_companies');
            Route::post('companies', 'store')->name('store_company');
            Route::get('companies/{companyId}/edit', 'edit')->name('edit_company');
            Route::get('companies/{companyId}/resend-verification-email', 'resendVerificationEmail')->name(
                'resend_verification_email'
            );
            Route::get('companies/{companyId}/currency-rate-update', 'currencyRateUpdateView')->name(
                'currency_rate_update'
            );
            Route::post('companies/currency-rate-update', 'currencyRateUpdate')->name('update_currency_rate');
            Route::post('companies/currency-rate-update-toggle/{companyId}', 'currencyRateUpdateToggle')->name(
                'currencyUpdateToggle'
            );
            Route::put('companies/{companyId}/update', 'update')->name('update_company');
            Route::post('companies/{companyId}/archive', 'archive')->name('archive_company');
            Route::put('companies/{companyId}/restore', 'restore')->name('restore_company');
        });
        Route::controller(EmployeeController::class)->name('employees.')->group(function (): void {
            Route::inertia('employees', 'employees/Index')->name('index');
            Route::get('fetch-employees', 'fetchEmployees')->name('fetch');
            Route::get('get-company-employees/{companyId}', 'getByCompanyId')->name('get_company_employees');
            Route::get('employees/create', 'create')->name('create');
            Route::post('employees', 'store')->name('store');
            Route::post('employees/{employeeId}/set-status/{status}', 'setStatus')->name('set_status');
            Route::get('employees/{employeeId}/edit', 'edit')->name('edit');
            Route::put('employees/{employeeId}', 'update')->name('update');
        });
        Route::controller(EmployeeGroupController::class)->name('employee_groups.')->group(function (): void {
            Route::get('employee-groups', 'index')->name('index');
            Route::get('fetch-employee-groups', 'fetchEmployeeGroups')->name('fetch');
            Route::get('employee-groups/create', 'create')->name('create');
            Route::post('employee-groups', 'store')->name('store');
            Route::get('employee-groups/{employeeGroupId}/edit', 'edit')->name('edit');
            Route::put('employee-groups/{employeeGroupId}/update', 'update')->name('update');
            Route::get('export-employee-groups/{fileName}', 'exportEmployeeGroups')->name('export');
            Route::get('get-employee-groups-by-company/{companyId}', 'getEmployeeGroupByCompanyId')->name(
                'get_employee_group_by_company'
            );
        });
        Route::controller(ExternalConnectionController::class)->name('external_connections.')->group(
            function (): void {
                Route::get('external-connections', 'index')->name('index');
                Route::get('fetch-external-connections', 'fetchExternalConnections')->name('fetch');
                Route::get('external-connections/create', 'create')->name('create');
                Route::post('external-connections', 'store')->name('store');
                Route::get('external-connections/{externalConnectionId}/edit', 'edit')->name('edit');
                Route::post('external-connections/{externalConnectionId}/update', 'update')->name('update');
                Route::get('external-connections/reject', 'reject')->name('reject');
                Route::get('external-connections/approve', 'approve')->name('approve');
                Route::get('external-connections/{externalConnectionId}/sync', 'syncData')->name('sync_data');
            }
        );
        Route::controller(AdminController::class)->name('admins.')->group(function (): void {
            Route::inertia('admins', 'admins/Index')->name('index');
            Route::get('fetch-admins', 'fetchAdmins')->name('fetch_admins');
            Route::get('admins/create', 'create')->name('create');
            Route::post('admins', 'store')->name('store');
            Route::get('admins/{adminId}/edit', 'edit')->name('edit');
            Route::put('admins/{adminId}/update', 'update')->name('update');
            Route::get('admins/{adminId}/change-password', 'changePassword')->name('change_password');
            Route::post('admins/{adminId}/update-password', 'updatePassword')->name('update_password');
        });
        Route::controller(LoginController::class)->group(function (): void {
            Route::post('logout', 'logout')->name('logout');
        });
        Route::controller(DesignationController::class)->name('designations.')->group(function (): void {
            Route::inertia('designations', 'designations/Index')->name('index');
            Route::get('fetch-designations', 'fetchDesignations')->name('fetch');
            Route::get('designations/create', 'create')->name('create');
            Route::post('designations', 'store')->name('store');
            Route::get('designations/{designationId}/edit', 'edit')->name('edit');
            Route::put('designations/{designationId}/update', 'update')->name('update');
            Route::get('get-designations-by-company/{companyId}', 'getByCompanyId')->name('get_by_company');
        });
        Route::controller(SiteConfigurationController::class)->name('site_configurations.')->group(
            function (): void {
                Route::inertia('site-configurations', 'site_configurations/Index')->name('index');
                Route::get('site-configurations/fetch', 'fetch')->name('fetch');
                Route::get('site-configurations/{SiteConfigurationId}/edit', 'edit')->name('edit');
                Route::post('site-configurations/{SiteConfigurationId}/update', 'update')->name('update');
            }
        );
        Route::controller(RoleController::class)->name('roles.')->group(function (): void {
            Route::get('roles', 'index')->name('index');
            Route::get('roles/fetch', 'fetch')->name('fetch');
            Route::get('role/create', 'create')->name('create');
            Route::post('role', 'store')->name('store');
            Route::get('role/{roleId}/edit', 'edit')->name('edit_roles_permissions');
            Route::get('role/{roleId}/clone', 'clone')->name('clone');
            Route::post('role/{roleId}/update', 'update')->name('update_roles_permissions');
        });
        Route::controller(NotificationController::class)->name('notifications.')->group(function (): void {
            Route::get('fetch-notifications', 'fetchNotifications')->name('fetch');
            Route::post('mark-all-as-read', 'markAllAsRead')->name('mark_all_as_read');
            Route::get('fetch-read-notifications', 'fetchReadNotifications')->name('fetch_read_notification');
            Route::post('mark-as-read', 'markAsRead')->name('mark_as_read');
            Route::post('mark-as-unread', 'markAsUnRead')->name('mark_as_unread');
        });
        Route::controller(SuperAdminController::class)->name('super_admins.')->group(function (): void {
            Route::inertia('super-admins', 'super_admins/Index')->name('index');
            Route::get('super-admins/fetch', 'fetchSuperAdmins')->name('fetch');
            Route::get('super-admins/create', 'create')->name('create');
            Route::post('super-admins', 'store')->name('store');
            Route::get('super-admins/{superAdminId}/edit', 'edit')->name('edit');
            Route::put('super-admins/{superAdminId}/update', 'update')->name('update');
            Route::get('super-admins/edit-profile', 'editProfile')->name('edit_profile');
            Route::get('super-admins/{superAdminId}/change-password', 'changePassword')->name('change_password');
            Route::post('super-admins/{superAdminId}/update-password', 'updatePassword')->name('update_password');
            Route::get('super-admins/{superAdminId}/resend-verification-email', 'resendVerificationEmail')->name(
                'resend_verification_email'
            );
        });
        Route::controller(SaleChannelController::class)->name('sales_channel.')->group(function (): void {
            Route::inertia('sales-channels', 'sales_channel/Index')->name('index');
            Route::get('sales-channel/create', 'create')->name('create');
            Route::post('sales-channel', 'store')->name('store');
            Route::get('fetch-sales-channel', 'fetchSalesChannel')->name('fetch');
            Route::get('sales-channel/{salesChannelId}/edit', 'edit')->name('edit');
            Route::put('sales-channel/{salesChannelId}/update', 'update')->name('update');
            Route::post('sales-channel/{salesChannelId}/refresh-access-token', 'refreshAccessToken')->name(
                'refresh_access_token'
            );
            Route::post('sales-channels/{salesChannelId}/set-status/{status}', 'setStatus')->name('update_status');
        });
        Route::controller(LocationController::class)->name('locations.')->group(function (): void {
            Route::get('get-locations-by-company/{companyId}', 'getByCompanyId')->name('get_by_company');
        });

        Route::controller(CourierController::class)->name('courier.')->group(function (): void {
            Route::inertia('couriers', 'courier/Index')->name('index');
            Route::get('courier/create', 'create')->name('create');
            Route::post('courier', 'store')->name('store');
            Route::get('fetch-courier', 'fetchCourier')->name('fetch');
            Route::get('courier/{courierId}/edit', 'edit')->name('edit');
            Route::put('courier/{courierId}/update', 'update')->name('update');
        });
        Route::controller(IntegrationController::class)->name('integrations.')->group(
            function (): void {
                Route::inertia('integrations', 'integrations/Index')->name('index');
                Route::get('integrations/get-connection-name/{connectionType}', 'connectionName')->name(
                    'connection_name'
                );
                Route::get('integrations/create', 'create')->name('create');
                Route::post('integrations', 'store')->name('store');
                Route::get('integrations/fetch-integrations', 'fetchIntegration')->name('fetch');
                Route::get('integrations/{integrationId}/edit', 'edit')->name('edit');
                Route::put('integrations/{integrationId}/update', 'update')->name('update');
                Route::post('integrations/{integrationId}/refresh-access-token', 'refreshAccessToken')->name(
                    'refresh_access_token'
                );
                Route::post('integrations/{integrationId}/set-status/{status}', 'setStatus')->name('update_status');
            }
        );
    });
});
