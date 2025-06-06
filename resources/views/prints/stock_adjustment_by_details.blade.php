<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Adjustment (By Details)</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Stock Adjustment Report" reportType="By Details" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        @if ($stockAdjustmentType)
            <p> Stock Adjustment Type: <strong>{{ $stockAdjustmentType }}</strong> </p>
        @endif
    </div>

    @foreach ($stockAdjustmentRecords as $stockAdjustmentRecord)
        <p> Location: <strong>{{ $stockAdjustmentRecord['location_name'] }}</strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    @foreach ($columns as $column)
                        <td class="{{ $column === 'Quantity' ? 'text-right' : 'text-left'}}"> {{ $column }} </td>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @if (count($stockAdjustmentRecord['stock_adjustment_data']) > 0)
                    @forelse($stockAdjustmentRecord['stock_adjustment_data'] as $stockAdjustmentData)
                        <tr class="page-break-inside-avoid">
                            <td class="{{ $stockAdjustmentData['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockAdjustmentData['adjustment_date'] }}</td>
                            <td class="{{ $stockAdjustmentData['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockAdjustmentData['adjustment_type'] }}</td>
                            <td class="{{ $stockAdjustmentData['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockAdjustmentData['approved_by'] }}</td>
                            <td class="{{ $stockAdjustmentData['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockAdjustmentData['reason'] }}</td>
                            @if ($stockAdjustmentData['adjustment_date'] !== 'Total')
                                <td class="{{ $stockAdjustmentData['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-left"> Name </th>
                                                <th class="text-left"> UPC </th>
                                                <th class="text-left"> Article Number </th>
                                                <th class="text-right"> Quantity </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr>
                                                <td class="text-left">
                                                    {{ $stockAdjustmentData['items']['name'] }}
                                                </td>

                                                <td class="text-left">
                                                    {{ $stockAdjustmentData['items']['upc'] }}
                                                </td>

                                                <td class="text-left">
                                                    {{ $stockAdjustmentData['items']['article_number'] }}
                                                </td>

                                                <td class="text-right">
                                                    {{ $stockAdjustmentData['items']['quantity'] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            @else
                                <td></td>
                            @endif
                            <td class="text-right {{ $stockAdjustmentData['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockAdjustmentData['quantity'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $stockAdjustmentRecord['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $stockAdjustmentRecord['adjustment_date'] }}
                        </td>
                        <td class="{{ $stockAdjustmentRecord['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $stockAdjustmentRecord['adjustment_type'] }}
                        </td>
                        <td class="{{ $stockAdjustmentRecord['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $stockAdjustmentRecord['approved_by'] }}
                        </td>
                        <td class="{{ $stockAdjustmentRecord['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $stockAdjustmentRecord['reason'] }}
                        </td>
                        <td class="{{ $stockAdjustmentRecord['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $stockAdjustmentRecord['items'] }}
                        </td>
                        <td class="text-right {{ $stockAdjustmentRecord['adjustment_date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $stockAdjustmentRecord['quantity'] }}
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="7" class="text-center">No Records</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
</body>
</html>
