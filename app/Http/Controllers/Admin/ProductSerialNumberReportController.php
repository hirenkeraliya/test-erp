<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\SerialNumber\Resources\AdminProductSerialNumberReportListResource;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class ProductSerialNumberReportController extends Controller
{
    public function __construct(
        protected SerialNumberQueries $serialNumberQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $serialNumbers = $this->serialNumberQueries->getWithBasicColumns($companyId);

        return Inertia::render('reports/product_serial_number_report/Index', [
            'serialNumbers' => $serialNumbers,
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchProductSerialNumberReport(Request $request): array
    {
        $filterData = [
            'serial_number_id' => $request->get('serial_number_id') ?? null,
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->serialNumberQueries->getPaginatedProductSerialNumber(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminProductSerialNumberReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }
}
