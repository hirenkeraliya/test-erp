<?php

declare(strict_types=1);

namespace App\Domains\Member\Exports;

use App\Domains\ExportRecord\Interfaces\ExportRecordClassInterface;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Services\MemberService;
use App\Models\ExportRecord;
use Illuminate\Support\Collection;

class ExportMember implements ExportRecordClassInterface
{
    public function export(int $exportRecordId, int $companyId): void
    {
    }

    public function fetch(ExportRecord $exportRecord, int $insertedRows, int $nextRecords): Collection
    {
        $memberQueries = resolve(MemberQueries::class);

        $memberService = resolve(MemberService::class);

        $members = $memberQueries->exportMemberRecords(
            $exportRecord->filters ?? [],
            $exportRecord->company_id,
            $insertedRows,
            $nextRecords
        );

        return $memberService->preparedMemberRecords($members);
    }
}
