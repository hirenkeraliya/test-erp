<?php

declare(strict_types=1);

namespace App\Domains\Member\Jobs;

use App\Domains\Member\Mail\SendEmail;
use App\Domains\MemberGroupMember\MemberGroupMemberQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailsJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly int $memberGroupId,
        private readonly int $emailTemplateId,
        private readonly int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $memberGroupMemberQueries = resolve(MemberGroupMemberQueries::class);
            $memberGroups = $memberGroupMemberQueries->getEmailsByGroupId($this->memberGroupId, $this->companyId);

            foreach ($memberGroups as $memberGroup) {
                Log::debug('Send email to member', [
                    'member_id' => $memberGroup->member->id,
                    'email' => $memberGroup->member->email,
                ]);

                Mail::to($memberGroup->member->email)->send(new SendEmail($this->emailTemplateId));
            }
        } catch (Throwable $throwable) {
            Log::error('The Member Send Email By Group & Email Template', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
