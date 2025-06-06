<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Jobs\EmailVerificationJob;
use App\Domains\EmailRecipient\DataObjects\EmailRecipientData;
use App\Domains\EmailRecipient\EmailRecipientQueries;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\EmailRecipient\Exports\EmailRecipientExport;
use App\Domains\EmailRecipient\Resources\AdminEmailRecipientListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmailRecipientController extends Controller
{
    public function __construct(
        protected EmailRecipientQueries $emailRecipientQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('email_recipients/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('email_recipient'),
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchEmailRecipients(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->emailRecipientQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminEmailRecipientListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('email_recipients/Manage', [
            'emailTypes' => EmailTypes::formattedForSelection(),
        ]);
    }

    public function store(EmailRecipientData $emailRecipientData): RedirectResponse
    {
        $this->emailRecipientQueries->addNew($emailRecipientData, session('admin_company_id'));

        return to_route('admin.email_recipients.index')->with('success', 'Email recipient added successfully.');
    }

    public function edit(int $emailRecipientId): Response
    {
        return Inertia::render('email_recipients/Manage', [
            'emailRecipient' => $this->emailRecipientQueries->getById(
                $emailRecipientId,
                session('admin_company_id')
            ),
            'emailTypes' => EmailTypes::formattedForSelection(),
        ]);
    }

    public function update(EmailRecipientData $emailRecipientData, int $emailRecipientId): RedirectResponse
    {
        $this->emailRecipientQueries->update($emailRecipientData, $emailRecipientId, session('admin_company_id'));

        return to_route('admin.email_recipients.index')->with('success', 'Email recipient updated successfully.');
    }

    public function exportEmailRecipients(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $emailRecipients = $this->emailRecipientQueries->getEmailRecipientExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new EmailRecipientExport($emailRecipients), $filename);
    }

    public function resendVerificationEmail(int $emailRecipientId): RedirectResponse
    {
        $emailRecipient = $this->emailRecipientQueries->getById($emailRecipientId, session('admin_company_id'));
        EmailVerificationJob::dispatch($emailRecipient)->delay(now()->addSeconds(5))->onQueue('high');

        return to_route('admin.email_recipients.index')
            ->with('success', 'The verification mail sent successfully.');
    }
}
