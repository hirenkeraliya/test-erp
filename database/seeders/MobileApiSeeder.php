<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Common\Enums\AuthorizerTypes;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\Cashback;
use App\Models\Cashier;
use App\Models\CashierGroup;
use App\Models\CashMovement;
use App\Models\CashMovementReason;
use App\Models\Category;
use App\Models\Color;
use App\Models\Company;
use App\Models\ComplimentaryItemReason;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Director;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\Member;
use App\Models\Membership;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Promoter;
use App\Models\PurchaseAmount;
use App\Models\SaleReturnReason;
use App\Models\Season;
use App\Models\Size;
use App\Models\StoreManager;
use App\Models\Style;
use App\Models\SuperAdmin;
use App\Models\Tag;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use App\Models\VoidSaleReason;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationTier;
use Illuminate\Database\Seeder;

class MobileApiSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(StaticDataSeeder::class);

        SuperAdmin::factory()->create([
            'username' => 'super_admin',
            'name' => 'Super Admin',
        ]);

        $company = Company::factory()->create([
            'name' => config('app.name'),
            'email' => 'company@test.com',
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $company->id,
        ]);

        Admin::factory()->create([
            'employee_id' => $employee->id,
            'username' => 'admin',
        ]);

        $brands = Brand::factory(15)
            ->hasAttached($company)
            ->create();

        $productCollections = ProductCollection::factory(15)
            ->hasAttached($company)
            ->create();

        $seasons = Season::factory(15)->create([
            'company_id' => $company->id,
        ]);

        $categories = Category::factory(15)->create([
            'company_id' => $company->id,
        ]);

        $locations = Location::factory(5)->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counters = Counter::factory(2)->create([
            'location_id' => $locations->first()->id,
            'is_locked' => false,
        ]);

        $ids = Category::query()->whereNull('parent_category_id')->pluck('id')->toArray();
        for ($i = 0; $i <= 10; $i++) {
            Category::factory()->create([
                'company_id' => $company->id,
                'parent_category_id' => $ids[array_rand($ids)],
            ]);
        }

        $unitOfMeasure = UnitOfMeasure::factory()
            ->has(UnitOfMeasureDerivative::factory()->count(5), 'derivatives')
            ->create([
                'company_id' => $company->id,
            ]);

        $styles = Style::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $colors = Color::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $sizes = Size::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $departments = Department::factory(10)->create([
            'company_id' => $company->id,
        ]);

        CashierGroup::factory(10)->create([
            'company_id' => $company->id,
        ]);

        $tags = Tag::factory(2)->create([
            'company_id' => $company->id,
        ]);

        $employeeId = Employee::factory()->create([
            'company_id' => $company->id,
        ]);

        $cashierGroupId = CashierGroup::factory()->create([
            'company_id' => $company->id,
            'name' => 'Cashier Group',
        ]);

        $cashier = Cashier::factory()
            ->hasAttached($locations)
            ->create([
                'employee_id' => $employeeId,
                'cashier_group_id' => $cashierGroupId,
                'username' => 'cashier',
            ]);

        $products = Product::factory(10000)
            ->hasAttached($categories, [
                'sort_order' => 0,
            ])
            ->hasAttached($tags)
            ->create([
                'company_id' => $company->id,
                'unit_of_measure_id' => $unitOfMeasure->id,
                'season_id' => $seasons[0]->id,
                'department_id' => $departments[0]->id,
                'color_id' => $colors[0]->id,
                'size_id' => $sizes[0]->id,
                'brand_id' => $brands[0]->id,
                'style_id' => $styles[0]->id,
            ]);

        PaymentType::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $parentPaymentTypeIds = PaymentType::query()->whereNull('parent_payment_type_id')->pluck('id')->toArray();

        for ($i = 0; $i < 5; $i++) {
            PaymentType::factory()->create([
                'company_id' => $company->id,
                'parent_payment_type_id' => $parentPaymentTypeIds[array_rand($parentPaymentTypeIds)],
            ]);
        }

        Promoter::factory()
            ->count(1)
            ->hasAttached($locations)
            ->create([
                'employee_id' => $employee->id,
            ]);

        $employeeIdForStoreManager = Employee::factory()->create([
            'company_id' => $company->id,
        ]);

        $storeManager = StoreManager::factory()
            ->hasAttached($locations)
            ->create([
                'username' => 'store_manager',
                'employee_id' => $employeeIdForStoreManager->id,
            ]);

        $batchProducts = $products->where('has_batch', true);
        $purchaseAmount = PurchaseAmount::factory()->create();

        foreach ($batchProducts as $batchProduct) {
            $batch = Batch::factory()->create([
                'company_id' => $company->id,
                'product_id' => $batchProduct->id,
                'number' => random_int(0, 1000) . $batchProduct->name,
            ]);

            $inventory = Inventory::factory()->create([
                'product_id' => $batchProduct->id,
                'location_id' => $counters->first()->location_id,
            ]);

            InventoryUnit::factory()->create([
                'inventory_id' => $inventory->id,
                'purchase_amount_id' => $purchaseAmount->id,
                'batch_id' => $batch->id,
            ]);

            InventoryUpdate::factory()->create([
                'product_id' => $batchProduct->id,
                'batch_id' => $batch->id,
                'purchase_amount_id' => $purchaseAmount->id,
                'location_id' => $counters->first()->store_id,
                'affected_by_id' => $cashier->id,
                'affected_by_type' => ModelMapping::CASHIER->name,
            ]);
        }

        $cashMovementReasons = CashMovementReason::factory(2)->create([
            'company_id' => $company->id,
        ]);

        Member::factory(10)->create([
            'company_id' => $company->id,
            'created_location_id' => $locations->first()->id,
        ]);

        $employeeForDirector = Employee::factory()->create([
            'company_id' => $company->id,
        ]);

        Director::factory()
            ->hasAttached($locations)
            ->create([
                'employee_id' => $employeeForDirector->id,
            ]);

        $dreamPrice = DreamPrice::factory()
            ->hasAttached($locations)
            ->create([
                'company_id' => $company->id,
            ]);

        DreamPriceProduct::factory(5)->create([
            'dream_price_id' => $dreamPrice->id,
            'product_id' => $products->pluck('id')->random(1)->first(),
        ]);

        ComplimentaryItemReason::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counters->first()->id,
            'cashier_id' => $cashier->id,
        ]);

        $voucherConfiguration = VoucherConfiguration::factory()->create([
            'company_id' => $company->id,
        ]);

        VoucherConfigurationTier::factory()->create([
            'voucher_configuration_id' => $voucherConfiguration->id,
        ]);

        Cashback::factory(2)->create([
            'company_id' => $company->id,
        ]);

        LoyaltyCampaign::factory(2)->create([
            'company_id' => $company->id,
        ]);

        Membership::factory(2)->create([
            'company_id' => $company->id,
        ]);

        CashMovement::factory(2)->create([
            'counter_update_id' => $counterUpdate->id,
            'cash_movement_reason_id' => $cashMovementReasons->pluck('id')->random(1)->first(),
            'authorizer_id' => $storeManager->id,
            'authorizer_type' => AuthorizerTypes::STORE_MANAGER->value,
        ]);

        VoidSaleReason::factory(5)->create([
            'company_id' => $company->id,
        ]);

        SaleReturnReason::factory(5)->create([
            'company_id' => $company->id,
        ]);

        Cashback::factory(2)->create([
            'company_id' => $company->id,
        ]);

        $this->call(PromotionSeeder::class, false, [
            'companyId' => $company->id,
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'locations' => $locations,
            'productCollections' => $productCollections,
        ]);
    }
}
