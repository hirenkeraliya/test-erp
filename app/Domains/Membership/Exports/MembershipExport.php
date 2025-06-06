<?php

declare(strict_types=1);

namespace App\Domains\Membership\Exports;

use App\Models\Membership;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MembershipExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $memberships
    ) {
    }

    public function collection(): Collection
    {
        return $this->memberships->map(fn (Membership $membership): array => [
            'name' => $membership->name,
            'lifetime_value' => $membership->lifetime_value,
            'loyalty_points_per_currency_unit' => $membership->loyalty_points_per_currency_unit,
            'min_loyalty_points_for_redemption' => $membership->min_loyalty_points_for_redemption,
            'max_loyalty_points_for_redemption' => $membership->max_loyalty_points_for_redemption,
        ]);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Lifetime Value',
            'Loyalty Points Per Currency unit',
            'Min Loyalty Points For Redemption',
            'Max Loyalty Points For Redemption',
        ];
    }
}
