<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Attribute\AttributeQueries;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function getAllAttributes(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        /** @var AttributeQueries $attributeQueries */
        $attributeQueries = resolve(AttributeQueries::class);

        return [
            'attributes' => $attributeQueries->getAllAttributesByCompanyId($companyId),
        ];
    }
}
