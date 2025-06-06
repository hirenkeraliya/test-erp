<?php

declare(strict_types=1);

namespace App\Domains\Common\Jobs;

use App\Models\Company;
use App\Models\EmailRecipient;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Region;
use App\Models\SuperAdmin;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EmailVerificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Employee|Member|Vendor|Region|Location|Company|SuperAdmin|EmailRecipient $model,
    ) {
    }

    public function handle(): void
    {
        $this->model->sendEmailVerificationNotification();
    }
}
