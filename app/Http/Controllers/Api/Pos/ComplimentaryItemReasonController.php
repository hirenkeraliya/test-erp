<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pos;

use App\CommonFunctions;
use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use App\Http\Controllers\Controller;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ComplimentaryItemReasonController extends Controller
{
    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function getList(Request $request): array
    {
        /** @var Cashier $cashier */
        $cashier = $request->user();

        $validatedData = $request->validate([
            'after_updated_at' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d H:i:s'],
        ]);

        $afterUpdatedAt = $validatedData['after_updated_at'] ?? null;

        $companyId = CommonFunctions::getCashierCompanyId($cashier);

        $complimentaryItemReasonQueries = resolve(ComplimentaryItemReasonQueries::class);
        $complimentaryItemReasonLists = $complimentaryItemReasonQueries->getList($companyId, $afterUpdatedAt);

        return [
            'complimentary_item_reasons' => $complimentaryItemReasonLists,
        ];
    }
}
