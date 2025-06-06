<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Common\Enums\CourierWebhookUrls;
use App\Domains\Courier\CourierQueries;
use App\Domains\Courier\DataObjects\CourierData;
use App\Domains\Courier\Enums\CourierTypes;
use App\Domains\Courier\Resources\CourierResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourierController extends Controller
{
    public function __construct(
        protected CourierQueries $courierQueries
    ) {
    }

    public function fetchCourier(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->courierQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('courier/Manage', [
            'courierWebhookUrls' => CourierWebhookUrls::getList(),
            'courierTypes' => CourierTypes::getList(),
        ]);
    }

    public function store(CourierData $courierData): RedirectResponse
    {
        $this->courierQueries->addNew($courierData);

        return to_route('super_admin.courier.index')->with('success', 'Courier added successfully.');
    }

    public function edit(int $courierId): Response
    {
        $courier = $this->courierQueries->getById($courierId);

        return Inertia::render('courier/Manage', [
            'courier' => new CourierResource($courier),
            'courierWebhookUrls' => CourierWebhookUrls::getList(),
            'courierTypes' => CourierTypes::getList(),
        ]);
    }

    public function update(CourierData $courierData, int $courierId): RedirectResponse
    {
        $courier = $this->courierQueries->getById($courierId);

        $this->courierQueries->update($courierData, $courier);

        return to_route('super_admin.courier.index')->with('success', 'Courier updated successfully.');
    }
}
