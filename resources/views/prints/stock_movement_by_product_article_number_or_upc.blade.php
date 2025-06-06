<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">

    <title>Stock Movement Report</title>

</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Stock Movement Report {{ $reportType }}</strong>
    </h4>

    <p>
        @if (is_array($filterDate))
        Stock Movement from {{ $filterDate[0] }} to {{ $filterDate[1] }}
        @else
        Stock Movement Date : {{ $filterDate }}
        @endif
    </p>

    <p>
        Date: {{ $date }}
    </p>

    <x-filter-label-header :getFilterLabels="$getFilterLabels" />

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                @foreach ($columns as $column)
                <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            <h3>
                @if ($locations)
                {{ $locations->getNamesWithCodes }}
                @else
                All Stores
                @endif
            </h3>
            @forelse($stockMovementDataByProducts as $k => $stockMovementDataByProduct)
            <tr class="page-break-inside-avoid">
                <td>
                    {{ $stockMovementDataByProduct['name'] }}
                </td>

                <td>
                    {{ $stockMovementDataByProduct['location_name'] }}
                </td>

                <td class="text-right">
                    {{ (float) $stockMovementDataByProduct['price'] }}
                </td>

                <td class="text-left">
                    @if (isset($reportTypeByArticleNumber))
                    {{ $stockMovementDataByProduct['article_number'] }}
                    @elseif (isset($reportTypeByUpc))
                    {{ $stockMovementDataByProduct['upc'] }}
                    @endif
                </td>


                @if (isset($reportTypeByUpc))
                    <td class="text-left">
                    {{ $stockMovementDataByProduct['color'] }}
                    </td>
                @endif

                @if (isset($reportTypeByUpc))
                    <td class="text-left">
                    {{ $stockMovementDataByProduct['size'] }}
                    </td>
                @endif

                <td class="text-right">
                    {{ $stockMovementDataByProduct['goods_receive_note_in_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByProduct['goods_receive_note_out_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByProduct['stock_adjustment_in_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByProduct['stock_adjustment_out_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByProduct['stock_transfer_in_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByProduct['stock_transfer_out_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByProduct['delivery_order_in_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByProduct['delivery_order_out_balance'] }}
                </td>

                <td class="text-right">
                    {{ (float) $stockMovementDataByProduct['sold'] }}
                </td>

                <td class="text-right">
                    {{ (float) $stockMovementDataByProduct['balance'] }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
            </tr>
            @endforelse

            <tr class="page-break-inside-avoid text-bold">
                <td colspan="{{ $colSpan }}">
                    <b>
                        Grand Total
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $stockMovementTotalDataByProducts['sold'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $stockMovementTotalDataByProducts['remaining'] }}
                    </b>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
