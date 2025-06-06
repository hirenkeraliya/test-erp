<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\Jobs\PromoterCommissionGenerationJob;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Domains\PromoterCommissionRegeneration\PromoterCommissionRegenerationQueries;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Promoter;
use App\Models\PromoterCommission;
use App\Models\PromoterCommissionUpdate;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Carbon\Carbon;

test(
    'PromoterCommissionGenerationJob job calls respective methods and store promoter commission as expected.',
    function (): void {
        $currentTime = Carbon::now();
        $company = Company::factory()->make([
            'commission_type_id' => CommissionTypes::BY_PROMOTER->value,
            'default_country_id' => 1,
        ]);

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'designation_id' => 1,
        ]);

        $employee->company = $company;

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
        ]);

        $promoter->employee = $employee;

        $counter = Counter::factory()->make([
            'id' => 1,
            'location_id' => 1,
        ]);

        $counterUpdate = CounterUpdate::factory()->make([
            'id' => 1,
            'counter_id' => $counter->id,
            'cashier_id' => 1,
        ]);

        $counterUpdate->counter = $counter;

        $sale = Sale::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'counter_update_id' => $counterUpdate->id,
        ]);

        $sale->counterUpdate = $counterUpdate;

        $saleItem = SaleItem::factory()->make([
            'id' => 1,
            'sale_id' => 1,
            'product_id' => 1,
            'derivative_id' => null,
        ]);

        $department = Department::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $saleItem->product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => $department->id,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
        ]);

        $saleItem->product->department = $department;

        $saleItem->sale = $sale;

        $promoter->saleItems = collect([$saleItem]);

        $saleItem->promoters = collect($promoter);

        $promoterCommission = PromoterCommission::factory()->make([
            'id' => 1,
            'promoter_id' => $promoter->id,
        ]);

        $saleReturn = SaleReturn::factory()->make([
            'id' => 1,
            'original_sale_id' => 1,
            'counter_update_id' => 1,
            'member_id' => $employee->id,
            'happened_at' => $currentTime->format('Y-m-d H:i:s'),
        ]);

        $saleReturnItem = SaleReturnItem::factory()->make([
            'id' => 1,
            'sale_return_id' => $saleReturn->id,
            'original_sale_item_id' => $saleItem->id,
            'product_id' => 1,
            'sale_return_reason_id' => 1,
        ]);

        $saleReturnItem->saleItem = $saleItem;

        $promoteCommissionUpdate = PromoterCommissionUpdate::factory()->make([
            'id' => 1,
            'promoter_commission_id' => $promoterCommission->id,
            'affected_by_id' => $saleItem->id,
            'affected_by_type' => ModelMapping::SALE_ITEM->name,
            'department_id' => null,
            'commission_amount' => 100,
            'commission_percentage' => 1,
            'total_price_paid' => 50,
            'amount' => 25,
        ]);

        $saleReturnItem->saleItem = $saleItem;
        $saleReturnItem->saleItem->promoterCommissionUpdate = $promoteCommissionUpdate;

        $this->mock(PromoterCommissionQueries::class, function ($mock) use ($promoterCommission): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($promoterCommission);
            $mock->shouldReceive('updateCommissionAmount')
                ->once();
        });

        $this->mock(PromoterCommissionUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->times(2);
        });

        $this->mock(PromoterCommissionRegenerationQueries::class, function ($mock): void {
            $mock->shouldReceive('markAsCompleted')
                ->once();
        });

        $this->mock(PromoterQueries::class, function ($mock) use ($promoter, $saleReturnItem): void {
            $mock->shouldReceive('getAllWithMonthlySalesAndCompanyDetailsForPeriod')
                ->once()
                ->andReturn(collect([$promoter]));
            $mock->shouldReceive('getPromoterCommissionReturnItemsByIdAndPeriod')
                ->once()
                ->andReturn(collect([$saleReturnItem]));
        });

        PromoterCommissionGenerationJob::dispatch([$promoter->id])->onQueue(config('horizon.default_queue_name'));
    }
);
