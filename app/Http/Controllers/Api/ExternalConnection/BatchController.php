<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\ExternalConnection;

use App\Domains\Batch\BatchQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function getProductBatchNumbers(Request $request): array
    {
        $batchQueries = resolve(BatchQueries::class);

        $batches = $batchQueries->getByNumbersWithProductUpc($request->get('batch_details'), $request->get('upc'));

        return [
            'batches' => $batches,
        ];
    }
}
