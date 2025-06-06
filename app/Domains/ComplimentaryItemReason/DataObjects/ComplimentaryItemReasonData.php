<?php

declare(strict_types=1);

namespace App\Domains\ComplimentaryItemReason\DataObjects;

use App\Domains\ComplimentaryItemReason\ComplimentaryItemReasonQueries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class ComplimentaryItemReasonData extends Data
{
    public function __construct(
        public string $reason,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(Request $request): array
    {
        $complimentaryItemReasonId = null;
        $complimentaryItemReasonQueries = new ComplimentaryItemReasonQueries();

        if ('admin.complimentary_item_reasons.update' === $request->route()?->getName()) {
            $complimentaryItemReasonId = $request->route()->parameter('complimentaryItemReasonId');
        }

        return [
            'reason' => [
                'required',
                'string',
                'max:255',
                Rule::unique('complimentary_item_reasons', 'reason')->ignore($complimentaryItemReasonId)
                    ->where($complimentaryItemReasonQueries->filterByCompany(session('admin_company_id'))),
            ],
        ];
    }
}
