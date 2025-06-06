<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SellThroughReceivedExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected array $sellThroughReceivedData,
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->sellThroughReceivedData)->map(fn ($sellThroughReceivedData): array => [
            'location_name' => $sellThroughReceivedData['location_name'],
            'goods_receive_note_in_balance' => $sellThroughReceivedData['goods_receive_note_in_balance'],
            'goods_receive_note_out_balance' => $sellThroughReceivedData['goods_receive_note_out_balance'],
            'stock_adjustment_in_balance' => $sellThroughReceivedData['stock_adjustment_in_balance'],
            'stock_adjustment_out_balance' => $sellThroughReceivedData['stock_adjustment_out_balance'],
            'stock_transfer_in_balance' => $sellThroughReceivedData['stock_transfer_in_balance'],
            'stock_transfer_out_balance' => $sellThroughReceivedData['stock_transfer_out_balance'],
            'delivery_order_in_balance' => $sellThroughReceivedData['delivery_order_in_balance'],
            'delivery_order_out_balance' => $sellThroughReceivedData['delivery_order_out_balance'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Location Name',
            'GRN In',
            'GRN Out',
            'Adjustment In',
            'Adjustment Out',
            'Transfer In',
            'Transfer Out',
            'Delivery Order In',
            'Delivery Order Out',
        ];
    }
}
