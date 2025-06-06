<?php

namespace App\Http\Controllers\Admin;

use App\Domains\CustomFieldValue\Resources\CustomFieldValueResource;
use App\Domains\Template\TemplateQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomFieldValueController extends Controller
{
    public function fetch(Request $request, TemplateQueries $templateQueries): array
    {
        $attributes = $templateQueries->fetchAttributesByTemplate(
            $request->templateId,
            session('admin_company_id')
        );

        return [
            'template' => new CustomFieldValueResource($attributes),
        ];
    }
}
