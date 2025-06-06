<?php

declare(strict_types=1);

namespace App\Domains\MemberAddress\Jobs;

use App\Domains\MemberAddress\Services\MemberAddressSaleChannelService;
use App\Models\MemberAddress;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberAddressAddJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected MemberAddress $memberAddress,
    ) {
    }

    public function handle(): void
    {
        Log::channel('e_commerce')->info('e-commerce webhook member address add job', [
            'start time of the webhook call for the member address add' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $this->memberAddress->getKey(),
        ]);

        try {
            $memberAddressSaleChannelService = resolve(MemberAddressSaleChannelService::class);
            $memberAddressSaleChannelService->createMemberAddress($this->memberAddress);
        } catch (Throwable $throwable) {
            Log::channel('e_commerce')->error('e-commerce webhook member address add job failed', [
                'Error message' => $throwable->getMessage(),
                'Error code' => $throwable->getCode(),
                'File' => $throwable->getFile(),
                'Line' => $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
        }

        Log::channel('e_commerce')->info('e-commerce webhook member address add job ended', [
            'end time of the webhook call for the member address add' => Carbon::now()->format('Y-m-d H:i:s'),
            'member address id: ' . $this->memberAddress->getKey(),
        ]);
    }
}
