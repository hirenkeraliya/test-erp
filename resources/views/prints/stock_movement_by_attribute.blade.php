<!DOCTYPE html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Movement</title>
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Stock Movement {{ $reportType }}</strong>
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

    <h3>
        @if ($locations)
        {{ $locations->getNamesWithCodes }}
        @else
        All Stores
        @endif
    </h3>

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                @foreach ($columns as $column)
                <th class="text-center">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse($stockMovementDataByAttributes as $stockMovementDataByAttribute)
            <tr class="page-break-inside-avoid">
                <td>
                    {{ $stockMovementDataByAttribute['name'] }}
                </td>

                <td>
                    {{ $stockMovementDataByAttribute['location_name'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['goods_receive_note_in_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['goods_receive_note_out_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['stock_adjustment_in_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['stock_adjustment_out_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['stock_transfer_in_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['stock_transfer_out_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['delivery_order_in_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['delivery_order_out_balance'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['sold'] }}
                </td>

                <td class="text-right">
                    {{ $stockMovementDataByAttribute['balance'] }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
            </tr>
            @endforelse

            <tr class="page-break-inside-avoid text-bold">
                <td colspan="10" class="text-right">
                    <b>
                        Grand Total
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $stockMovementTotalDataByAttributes['sold'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $stockMovementTotalDataByAttributes['remaining'] }}
                    </b>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>