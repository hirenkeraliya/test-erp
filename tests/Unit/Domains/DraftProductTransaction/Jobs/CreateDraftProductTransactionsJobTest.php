<?php

declare(strict_types=1);

namespace App\Domains\ExternalProduct\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\DraftProductTransaction\DraftProductTransactionQueries;
use App\Domains\DraftProductTransaction\Jobs\CreateDraftProductTransactionsJob;
use App\Domains\Product\Enums\Statuses;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;

test(
    'CreateDraftProductTransactionsJob Calls then add new draft product transaction and ExternalCompanyWiseProductJob call',
    function (): void {
        Queue::fake()->except(CreateDraftProductTransactionsJob::class);

        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $admin = Admin::factory()->make([
            'id' => 1,
            'employee_id' => $employee->id,
        ]);

        $status = Statuses::ACTIVE->value;

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'code' => '1546',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'sub_department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'article_number' => '123456',
            'is_non_inventory' => false,
            'status' => Statuses::ACTIVE->value,
        ]);

        $draftProductTransactionData = [];
        $draftProductTransactionData['product_id'] = $product->id;
        $draftProductTransactionData['approved_by_id'] = $admin->id;
        $draftProductTransactionData['approved_by_type'] = ModelMapping::getCaseName($admin::class);
        $draftProductTransactionData['approved_at'] = now()->format('Y-m-d H:i:s');
        $draftProductTransactionData['rejected_by_id'] = null;
        $draftProductTransactionData['rejected_by_type'] = null;
        $draftProductTransactionData['rejected_at'] = null;

        $this->mock(DraftProductTransactionQueries::class, function ($mock) use ($draftProductTransactionData): void {
            $mock->shouldReceive('addNew')
                ->with($draftProductTransactionData)
                ->once();
        });

        CreateDraftProductTransactionsJob::dispatch(
            $product->id,
            $companyId,
            $admin->id,
            $admin::class,
            $status
        )->onQueue(config('horizon.default_queue_name'));
        Queue::assertPushed(ExternalCompanyWiseProductJob::class, 1);
    }
);
