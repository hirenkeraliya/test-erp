<?php

declare(strict_types=1);

namespace App\Domains\Common\Jobs;

use App\Models\Member;
use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutomationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly ?Member $member = null,
    ) {
    }

    public function handle(): void
    {
        $automationService = resolve(AutomationService::class);

        if (! $automationService->isEnabled()) {
            return;
        }

        if (! $this->member instanceof Member || ! $this->member->mobile_number) {
            return;
        }

        $automationService->sendOrderDetails($this->member);
    }
}
