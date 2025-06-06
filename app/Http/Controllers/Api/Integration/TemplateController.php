<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Integration;

use App\Domains\Template\TemplateQueries;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function getAllTemplates(Request $request): array
    {
        /** @var Integration $integration */
        $integration = $request->user();
        $companyId = $integration->getCompanyId();

        /** @var TemplateQueries $templateQueries */
        $templateQueries = resolve(TemplateQueries::class);

        return [
            'templates' => $templateQueries->getAllTemplatesByCompanyId($companyId),
        ];
    }
}
