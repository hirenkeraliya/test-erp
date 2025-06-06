<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\CashMovement\Enums\CashMovementTypes;
use App\Domains\CashMovementReason\Enums\StaticCashMovementReasons;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Member\Enums\StaticMembers;
use App\Domains\Member\MemberQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Permission\Services\StoreManagerPermissionModuleService;
use App\Domains\Permission\Services\WarehouseManagerPermissionModuleService;
use App\Domains\SiteConfiguration\Enums\EcommerceType;
use App\Domains\SiteConfiguration\Enums\SiteConfigurationTypes;
use App\Domains\SiteConfiguration\Enums\ThemeColors;
use App\Domains\Template\TemplateQueries;
use App\Models\AutomatedNotification;
use App\Models\CashMovementReason;
use App\Models\Company;
use App\Models\Country;
use App\Models\Member;
use App\Models\PaymentType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteConfiguration;
use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaticDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(WorldSeeder::class);

        DB::statement('ALTER TABLE cash_movement_reasons AUTO_INCREMENT = 100;');

        DB::statement('ALTER TABLE payment_types AUTO_INCREMENT = 100;');

        foreach (StaticPaymentTypes::getList() as $staticPaymentType) {
            PaymentType::factory()->create([
                'id' => $staticPaymentType['id'],
                'company_id' => null,
                'name' => StaticPaymentTypes::getFormattedCaseName($staticPaymentType['id']),
                'parent_payment_type_id' => null,
                'is_member_required' => $this->isMemberRequiredFor($staticPaymentType['id']),
                'is_available_for_refund' => $staticPaymentType['id'] === StaticPaymentTypes::CASH->value,
                'trigger_card_payment_machine' => false,
                'trigger_qr_code_payment_machine' => false,
                'trigger_card_affin_payment_machine' => false,
                'trigger_card_bank_rakyat_terminal' => false,
                'is_card_payment' => false,
                'status' => true,
                'payment_terminal_key' => null,
            ]);
        }

        CashMovementReason::factory()->create([
            'id' => 1,
            'company_id' => null,
            'reason' => StaticCashMovementReasons::getFormattedCaseName(StaticCashMovementReasons::CASHBACK->value),
            'type_id' => CashMovementTypes::CASH_OUT->value,
        ]);

        CashMovementReason::factory()->create([
            'id' => 2,
            'company_id' => null,
            'reason' => StaticCashMovementReasons::getFormattedCaseName(
                StaticCashMovementReasons::CASHBACK_REVERSAL->value
            ),
            'type_id' => CashMovementTypes::CASH_IN->value,
        ]);

        $password = $this->generateRandomPassword();

        SuperAdmin::factory()->create([
            'username' => 'super_admin',
            'name' => 'Super Admin',
            'email' => config('app.developer_email'),
            'password' => bcrypt($password),
        ]);

        $this->command->info('SuperAdmin Generated password: ' . $password);

        $company = Company::create([
            'name' => 'test-company',
            'email' => config('app.developer_email'),
            'uuid' => Str::uuid(),
            'grn_format' => 'GRN/',
            'void_sale_number_prefix' => 'vs',
            'commission_type_id' => CommissionTypes::BY_PROMOTER,
            'new_member_free_loyalty_points' => 1,
            'min_promoters_per_item' => 1,
            'default_country_id' => Country::first()->id,
        ]);

        $company->companySetting()->create();

        $company->brands()->create([
            'name' => 'test-brand',
            'code' => '123456',
        ]);

        $memberQueries = new MemberQueries();
        Member::create([
            'company_id' => $company->id,
            'first_name' => 'test',
            'last_name' => 'test',
            'mobile_number' => StaticMembers::STATIC_MEMBER->value,
            'card_number' => $memberQueries->generateUniqueCardNumber(),
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::THEME,
            'value' => ThemeColors::DARK_PURPLE->value,
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::FAVICON_ICON,
            'value' => 'favicon_icon.png',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::LOGIN_PAGE_LOGO,
            'value' => 'login_page_logo.png',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::LOGIN_PAGE_TAGLINE,
            'value' => 'Just a few clicks away from accessing your account.',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::LOGIN_PAGE_SUB_TAGLINE,
            'value' => 'Seamlessly manage your retail operations from here.',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::NAVBAR_LOGO,
            'value' => 'navbar_logo.png',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::DEFAULT_COMPANY,
            'value' => $company->id,
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::ECOMMERCE_TYPE,
            'value' => EcommerceType::SEPARATE_FOR_EACH_COMPANY->value,
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_THEME_COLOR->value,
            'value' => '#000331',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_BUTTON_TEXT_COLOR->value,
            'value' => '#5591B2',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_TITLE_BAR_COLOR->value,
            'value' => '#E4EBF5',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_COMPLETE_TEXT->value,
            'value' => '#108438',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_COMPLETE_TEXT_BACKGROUND->value,
            'value' => '#85D4A0',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_TEXT_HINT_COLOR->value,
            'value' => '#4E4E4E',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_TEXT_CHANGE_DUE->value,
            'value' => '#D90202',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_ALL_TEXT_COLOR->value,
            'value' => '#000000',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::ECOMMERCE_FAVICON,
            'value' => 'ecommerce_favicon.png',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::ECOMMERCE_COMPANY_NAME,
            'value' => $company->name,
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::ECOMMERCE_COMPANY_LOGO,
            'value' => 'ecommerce_company_logo.png',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_LABEL_COLOR,
            'value' => '#5591B2',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_BUTTON_BACKGROUND_COLOR,
            'value' => '#5591B2',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_ALL_SUB_TITTLE_TEXT_COLOR,
            'value' => '#4E4E4E',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_SWITCH_ON_COLOR,
            'value' => '#4E4E4E',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_CHECKBOX_FILL_COLOR,
            'value' => '#533232',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_DASHBOARD_SECTION1_COLOR,
            'value' => '#4E4E4E',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_DASHBOARD_SECTION2_COLOR,
            'value' => '#D90202',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_DASHBOARD_SECTION3_COLOR,
            'value' => '#85D4A0',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_DASHBOARD_SECTION4_COLOR,
            'value' => '#5591B2',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_FIRST_GRADIENT,
            'value' => '#5591B2',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_SECOND_GRADIENT,
            'value' => '#5591B2',
        ]);

        SiteConfiguration::factory()->create([
            'type_id' => SiteConfigurationTypes::APP_SCAFFOLD_BACKGROUND_COLOR_THIRD_GRADIENT,
            'value' => '#5591B2',
        ]);

        $this->addDefaultRoleForAdmin();
        $this->addDefaultRoleForStoreManager();
        $this->addDefaultRoleForWarehouseManager();
        $this->addDefaultAutomatedNotification($company);
        $this->addDefaultTemplateAndVariants($company);
    }

    public function addDefaultRoleForAdmin(): void
    {
        $permissions = PermissionModuleService::preparedPermissionModules();
        $permissionNames = [];

        foreach ($permissions as $permission) {
            foreach ($permission['children'] as $children) {
                $permissionNames[] = $children['id'];
                Permission::firstOrCreate([
                    'name' => $children['id'],
                    'guard_name' => 'admin',
                ]);
            }
        }

        $role = Role::firstOrCreate([
            'name' => 'Full Access',
            'guard_name' => 'admin',
        ]);

        $role->syncPermissions($permissionNames);
    }

    public function addDefaultRoleForStoreManager(): void
    {
        $permissions = StoreManagerPermissionModuleService::preparedPermissionModules();
        $permissionNames = [];

        foreach ($permissions as $permission) {
            foreach ($permission['children'] as $children) {
                $permissionNames[] = $children['id'];
                Permission::firstOrCreate([
                    'name' => $children['id'],
                    'guard_name' => 'store_manager',
                ]);
            }
        }

        $role = Role::firstOrCreate([
            'name' => 'Full Access',
            'guard_name' => 'store_manager',
        ]);

        $role->syncPermissions($permissionNames);
    }

    public function addDefaultRoleForWarehouseManager(): void
    {
        $permissions = WarehouseManagerPermissionModuleService::preparedPermissionModules();
        $permissionNames = [];

        foreach ($permissions as $permission) {
            foreach ($permission['children'] as $children) {
                $permissionNames[] = $children['id'];
                Permission::firstOrCreate([
                    'name' => $children['id'],
                    'guard_name' => 'warehouse_manager',
                ]);
            }
        }

        $role = Role::firstOrCreate([
            'name' => 'Full Access',
            'guard_name' => 'warehouse_manager',
        ]);

        $role->syncPermissions($permissionNames);
    }

    public function addDefaultAutomatedNotification(Company $company): void
    {
        AutomatedNotification::firstOrCreate([
            'company_id' => $company->id,
            'type_id' => AutomatedNotificationTypes::LOW_STOCK_COMPANY->value,
            'sent_notification' => false,
            'low_stock_alert_threshold' => 10,
        ]);
    }

    public function addDefaultTemplateAndVariants(Company $company): void
    {
        $templateQueries = resolve(TemplateQueries::class);
        $templateQueries->createDefaultTemplateAndAttributes($company->id);
    }

    private function isMemberRequiredFor(int $staticPaymentTypeId): bool
    {
        return $staticPaymentTypeId !== StaticPaymentTypes::CASH->value && $staticPaymentTypeId !== StaticPaymentTypes::GIFT_CARD->value && $staticPaymentTypeId !== StaticPaymentTypes::CREDIT_NOTE->value;
    }

    private function generateRandomPassword(): string
    {
        $uppercase = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
        $lowercase = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 3);
        $number = substr(str_shuffle('0123456789'), 0, 3);
        $symbol = substr(str_shuffle('!@#$%^&*()_-+=<>?'), 0, 1);

        return $uppercase . $lowercase . $number . $symbol;
    }
}
