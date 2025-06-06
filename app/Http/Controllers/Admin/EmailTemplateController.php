<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\EmailTemplate\DataObjects\EmailTemplateData;
use App\Domains\EmailTemplate\EmailTemplateQueries;
use App\Domains\EmailTemplate\Resources\EmailTemplateListResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function __construct(
        protected EmailTemplateQueries $emailTemplateQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('email_templates/Index');
    }

    public function fetch(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->emailTemplateQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => EmailTemplateListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('email_templates/Manage');
    }

    public function store(EmailTemplateData $emailTemplateData): RedirectResponse
    {
        $this->emailTemplateQueries->addNew($emailTemplateData);

        return to_route('admin.email_templates.index')->with('success', 'Email Template added successfully.');
    }

    public function edit(int $emailTemplateId): Response
    {
        return Inertia::render('email_templates/Manage', [
            'emailTemplate' => $this->emailTemplateQueries->getById($emailTemplateId),
        ]);
    }

    public function update(EmailTemplateData $emailTemplateData, int $emailTemplateId): RedirectResponse
    {
        $this->emailTemplateQueries->update($emailTemplateData, $emailTemplateId);

        return to_route('admin.email_templates.index')->with('success', 'Email Template updated successfully.');
    }

    public function getAll(): array
    {
        return [
            'email_templates' => $this->emailTemplateQueries->getAll(),
        ];
    }
}
