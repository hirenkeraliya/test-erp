<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Enums\Statuses;
use App\Models\Admin;
use App\Models\Batch;
use App\Models\BookingPayment;
use App\Models\BookingPaymentPayment;
use App\Models\BookingPaymentProduct;
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
use App\Models\CreditNote;
use App\Models\CreditNoteRefund;
use App\Models\CreditNoteUse;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Director;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\EmailRecipient;
use App\Models\Employee;
use App\Models\GoodsReceivedNote;
use App\Models\GoodsReceivedNoteProduct;
use App\Models\ImportRecord;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\InventoryUpdate;
use App\Models\Location;
use App\Models\LoyaltyCampaign;
use App\Models\Member;
use App\Models\Membership;
use App\Models\PackageType;
use App\Models\PaymentType;
use App\Models\PosMismatch;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Promoter;
use App\Models\PurchaseAmount;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemPriceOverride;
use App\Models\SaleItemUnit;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnReason;
use App\Models\Season;
use App\Models\Size;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockTransferReason;
use App\Models\StoreManager;
use App\Models\Style;
use App\Models\Tag;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use App\Models\VoidSale;
use App\Models\VoidSaleReason;
use App\Models\VoucherConfiguration;
use App\Models\VoucherConfigurationTier;
use App\Models\WarehouseManager;
use Faker\Generator;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(StaticDataSeeder::class);

        $company = Company::firstOr(fn () => Company::factory()->create());

        $brand = Brand::firstOr(fn () => Brand::factory()->hasAttached($company)->create());

        $productCollection = ProductCollection::factory()->create([
            'company_id' => $company->id,
        ]);

        $designation = Designation::factory()->create([
            'company_id' => $company->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'designation_id' => $designation->id,
        ]);

        $admin = Admin::factory()->create([
            'employee_id' => $employee->id,
            'username' => 'admin',
        ]);

        $seasons = Season::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $categories = Category::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $stores = Location::factory(5)->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::STORE->value,
        ]);

        $counters = Counter::factory(2)->create([
            'location_id' => $stores->first()->id,
            'is_locked' => false,
        ]);

        $ids = Category::query()->whereNull('parent_category_id')->pluck('id')->toArray();
        for ($i = 0; $i <= 10; $i++) {
            Category::factory()->create([
                'company_id' => $company->id,
                'parent_category_id' => $ids[array_rand($ids)],
            ]);
        }

        $unitOfMeasureDerivatives = UnitOfMeasureDerivative::factory()->count(5);

        $unitOfMeasure = UnitOfMeasure::factory()
            ->has($unitOfMeasureDerivatives, 'derivatives')
            ->create([
                'company_id' => $company->id,
            ]);

        $packageType = PackageType::factory(3)->create([
            'company_id' => $company->id,
        ]);

        $saleReturnReason = SaleReturnReason::factory(5)->create([
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
            'designation_id' => $designation->id,
        ]);

        $cashierGroupId = CashierGroup::factory()->create([
            'company_id' => $company->id,
            'name' => 'Cashier Group',
        ]);

        $cashier = Cashier::factory()
            ->hasAttached($stores)
            ->create([
                'employee_id' => $employeeId,
                'cashier_group_id' => $cashierGroupId,
                'username' => 'cashier',
            ]);

        $generator = resolve(Generator::class);

        Product::factory(2)
            ->hasAttached($categories, [
                'sort_order' => 0,
            ])
            ->hasAttached($tags)
            ->create([
                'company_id' => $company->id,
                'compound_product_name' => $generator->name(),
                'unit_of_measure_id' => $unitOfMeasure->id,
                'season_id' => $seasons[0]->id,
                'department_id' => $departments[0]->id,
                'color_id' => $colors[0]->id,
                'size_id' => $sizes[0]->id,
                'brand_id' => $brand->id,
                'style_id' => $styles[0]->id,
                'status' => Statuses::DRAFT->value,
                'created_by_id' => 2,
                'created_by_type' => ModelMapping::ADMIN->name,
            ]);

        $products = Product::factory(3)
            ->hasAttached($categories, [
                'sort_order' => 0,
            ])
            ->hasAttached($tags)
            ->create([
                'company_id' => $company->id,
                'compound_product_name' => $generator->name(),
                'unit_of_measure_id' => $unitOfMeasure->id,
                'season_id' => $seasons[0]->id,
                'department_id' => $departments[0]->id,
                'color_id' => $colors[0]->id,
                'size_id' => $sizes[0]->id,
                'brand_id' => $brand->id,
                'style_id' => $styles[0]->id,
                'status' => Statuses::ACTIVE->value,
                'created_by_id' => $admin->id,
                'created_by_type' => ModelMapping::ADMIN->name,
            ]);

        $paymentTypes = PaymentType::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $parentPaymentTypeIds = PaymentType::query()->whereNull('parent_payment_type_id')->pluck('id')->toArray();

        for ($i = 0; $i < 5; $i++) {
            PaymentType::factory()->create([
                'company_id' => $company->id,
                'parent_payment_type_id' => $parentPaymentTypeIds[array_rand($parentPaymentTypeIds)],
            ]);
        }

        $promoters = Promoter::factory()
            ->count(1)
            ->hasAttached($stores)
            ->create([
                'employee_id' => $employee->id,
            ]);

        $employeeIdForStoreManager = Employee::factory()->create([
            'company_id' => $company->id,
            'designation_id' => $designation->id,
        ]);

        $storeManager = StoreManager::factory()
            ->hasAttached($stores)
            ->create([
                'username' => 'store_manager',
                'employee_id' => $employeeIdForStoreManager->id,
            ]);

        $goodsReceivedNote = GoodsReceivedNote::factory()->create([
            'company_id' => $company->id,
            'location_id' => $stores->first()->id,
        ]);

        $batch = Batch::factory()->create([
            'company_id' => $company->id,
            'product_id' => $products->first()->id,
        ]);

        $purchaseAmount = PurchaseAmount::factory()->create();

        GoodsReceivedNoteProduct::factory()->create([
            'goods_received_note_id' => $goodsReceivedNote->id,
            'product_id' => $products->first()->id,
            'batch_id' => $batch->id,
            'purchase_amount_id' => $purchaseAmount->id,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $products->first()->id,
            'location_id' => $stores->first()->id,
        ]);

        InventoryUpdate::factory()->create([
            'product_id' => $products->first()->id,
            'batch_id' => $batch->id,
            'purchase_amount_id' => $purchaseAmount->id,
            'location_id' => $stores->first()->id,
            'affected_by_id' => $goodsReceivedNote->id,
            'affected_by_type' => ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name,
        ]);

        InventoryUnit::factory()->create([
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => $purchaseAmount->id,
            'batch_id' => $batch->id,
        ]);

        $warehouses = Location::factory(5)->create([
            'company_id' => $company->id,
            'type_id' => LocationTypes::WAREHOUSE->value,
        ]);

        $warehouseEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'designation_id' => $designation->id,
        ]);

        WarehouseManager::factory()
            ->hasAttached($warehouses)
            ->create([
                'username' => 'warehouse_manager',
                'employee_id' => $warehouseEmployee->id,
            ]);

        $cashMovementReasons = CashMovementReason::factory(2)->create([
            'company_id' => $company->id,
        ]);

        $members = Member::factory(5)->create([
            'company_id' => $company->id,
            'created_location_id' => $stores->first()->id,
        ]);

        $voidSaleReasons = VoidSaleReason::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $employeeForDirector = Employee::factory()->create([
            'company_id' => $company->id,
            'designation_id' => $designation->id,
        ]);

        Director::factory()
            ->hasAttached($stores)
            ->create([
                'employee_id' => $employeeForDirector->id,
            ]);

        $dreamPrice = DreamPrice::factory()
            ->hasAttached($stores)
            ->create([
                'company_id' => $company->id,
            ]);

        DreamPriceProduct::factory(5)->create([
            'dream_price_id' => $dreamPrice->id,
            'product_id' => $products->pluck('id')->random(1)->first(),
        ]);

        ImportRecord::factory(5)->create([
            'company_id' => $company->id,
            'created_by_id' => $admin->id,
            'created_by_type' => $admin::class,
        ]);

        ComplimentaryItemReason::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $counterUpdate = CounterUpdate::factory()->create([
            'counter_id' => $counters->first()->id,
            'cashier_id' => $cashier->id,
        ]);

        EmailRecipient::factory(5)->create([
            'company_id' => $company->id,
        ]);

        $stockAdjustments = StockAdjustment::factory(2)->create([
            'company_id' => $company->id,
            'created_by_admin_id' => $admin->id,
            'approved_by_employee_id' => $employee->id,
        ]);

        StockAdjustmentItem::factory(2)->create([
            'stock_adjustment_id' => $stockAdjustments->pluck('id')->random(1)->first(),
            'product_id' => $products->pluck('id')->random(1)->first(),
            'location_id' => $stores->first()->id,
        ]);

        $sales = Sale::factory(2)->create([
            'member_id' => $members->first()->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        VoidSale::factory(2)->create([
            'sale_id' => $sales->pluck('id')->random(1)->first(),
            'voided_by_store_manager_id' => $storeManager->id,
            'void_sale_reason_id' => $voidSaleReasons->pluck('id')->random(1)->first(),
        ]);

        $saleItems = SaleItem::factory(2)
            ->hasAttached($promoters)
            ->create([
                'sale_id' => $sales->pluck('id')->random(1)->first(),
                'product_id' => $products->first()->id,
                'derivative_id' => UnitOfMeasureDerivative::first()->id,
            ]);

        SaleItemPriceOverride::factory(2)->create([
            'sale_item_id' => $saleItems->pluck('id')->random(1)->first(),
            'negotiator_id' => $storeManager->id,
            'negotiator_type' => ModelMapping::STORE_MANAGER->name,
        ]);

        PosMismatch::factory(2)->create([
            'module_id' => $sales->pluck('id')->random(1)->first(),
            'module_type' => ModelMapping::SALE->name,
        ]);

        SaleItemUnit::factory(2)->create([
            'sale_item_id' => $saleItems->pluck('id')->random(1)->first(),
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => $purchaseAmount->id,
            'batch_id' => $batch->id,
        ]);

        $salePayment = SalePayment::factory(2)->create([
            'sale_id' => $sales->pluck('id')->random(1)->first(),
            'payment_type_id' => $paymentTypes->pluck('id')->random(1)->first(),
            'counter_update_id' => $counterUpdate->id,
        ]);

        $stockTransferReason = StockTransferReason::factory()->create([
            'company_id' => $company->id,
        ]);

        $stockTransfer = StockTransfer::factory()->create([
            'company_id' => $company->id,
            'stock_transfer_reason_id' => $stockTransferReason->id,
            'source_location_id' => fn () => $stores->first()->id,
            'destination_location_id' => $warehouses->first()->id,
            'requested_by_id' => $admin->id,
        ]);

        StockTransferItem::factory()->create([
            'stock_transfer_id' => $stockTransfer->id,
            'product_id' => $products->first()->id,
            'package_type_id' => $packageType->first()->id,
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
            'authorizer_type' => ModelMapping::STORE_MANAGER->name,
        ]);

        $this->call(PromotionSeeder::class, false, [
            'companyId' => $company->id,
            'products' => $products,
            'categories' => $categories,
            'brands' => collect([$brand]),
            'productCollections' => collect([$productCollection]),
            'stores' => $stores,
        ]);

        $bookingPayments = BookingPayment::factory(2)->create([
            'counter_update_id' => $counterUpdate->id,
            'member_id' => $members[0]->id,
        ]);

        BookingPaymentPayment::factory(2)->create([
            'booking_payment_id' => $bookingPayments[0]->id,
            'payment_type_id' => $paymentTypes[0]->id,
            'counter_update_id' => $counterUpdate->id,
        ]);

        BookingPaymentProduct::factory(2)->create([
            'booking_payment_id' => $bookingPayments[0]->id,
            'product_id' => $products[0]->id,
            'box_product_id' => null,
            'product_box_package_type_id' => null,
        ]);

        $saleReturn = SaleReturn::factory(2)->create([
            'original_sale_id' => $sales->pluck('id')->random(1)->first(),
            'counter_update_id' => $counterUpdate->id,
            'member_id' => $members[0]->id,
        ]);

        SaleReturnItem::factory(2)->create([
            'sale_return_id' => $saleReturn->pluck('id')->random(1)->first(),
            'original_sale_item_id' => $saleItems->pluck('id')->random(1)->first(),
            'product_id' => $products->first()->id,
            'sale_return_reason_id' => $saleReturnReason->pluck('id')->random(1)->first(),
        ]);

        PosMismatch::factory(2)->create([
            'module_id' => $saleReturn->pluck('id')->random(1)->first(),
            'module_type' => ModelMapping::SALE_RETURN->name,
        ]);

        $creditNote = CreditNote::factory(2)->create([
            'sale_return_id' => $saleReturn->pluck('id')->random(1)->first(),
            'counter_update_id' => $counterUpdate->id,
            'member_id' => $members[0]->id,
            'cancel_layaway_sale_id' => null,
        ]);

        CreditNoteUse::factory(2)->create([
            'credit_note_id' => $creditNote->pluck('id')->random(1)->first(),
            'counter_update_id' => $counterUpdate->id,
            'sale_payment_id' => $salePayment->pluck('id')->random(1)->first(),
            'booking_payment_payment_id' => null,
        ]);

        CreditNoteRefund::factory(2)->create([
            'credit_note_id' => $creditNote->pluck('id')->random(1)->first(),
            'counter_update_id' => $counterUpdate->id,
            'payment_type_id' => $paymentTypes->pluck('id')->random(1)->first(),
            'store_manager_id' => $storeManager->id,
        ]);
    }
}
