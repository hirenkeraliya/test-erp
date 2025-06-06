<?php

declare(strict_types=1);

namespace App\Domains\Member\Jobs;

use App\Domains\Member\Mail\SendConfirmationEmailMail;
use App\Domains\Member\MemberQueries;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendConfirmationEmailJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly string $email,
        private readonly string $message,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('member_app')->info('member_app', ['Member Send Email job has started.']);

        $memberQueries = resolve(MemberQueries::class);

        try {
            $member = $memberQueries->getByEmailWithCompanyMedia($this->email);

            if (! $member instanceof Member) {
                return;
            }

            if (null === $member->email) {
                return;
            }

            Mail::to([[
                'name' => $member->getFullName(),
                'email' => $member->email,
            ]])->send(new SendConfirmationEmailMail($member, $this->message));

            Log::channel('member_app')->info('member_app', ['Member Send Email Job completed.']);
        } catch (Throwable $throwable) {
            Log::error('The Member Send SMS job failed', [
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
