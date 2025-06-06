<?php

declare(strict_types=1);

namespace App\Domains\ExportRecordTransaction;

use App\Domains\Common\Enums\ModelMapping;
use App\Models\ExportRecordTransaction;
use Illuminate\Foundation\Auth\User;

class ExportRecordTransactionQueries
{
    public function addNew(User $user, int $companyId): void
    {
        ExportRecordTransaction::create([
            'company_id' => $companyId,
            'downloaded_by_type' => ModelMapping::getCaseName($user::class),
            'downloaded_by_id' => $user->id,
        ]);
    }
}
