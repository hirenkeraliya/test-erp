<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>{{ $isStatusAllowed ? 'Stock Transfer Status' : 'Stock Transfer' }}</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="{{ $isStatusAllowed ? 'Stock Transfer Status Report' : 'Stock Transfer Report' }}" reportType="By Summary By UPC" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        @if ($isStatusAllowed)
            <p> Status: <strong>{{ $status }}</strong> </p>
        @endif
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Records From Date Selection: <strong>{{ $dateSelectionType   }}</strong> </p>
        <p> Show Price: <strong>{{ $displayTotal ? 'Yes' : 'No'}}</strong> </p>
    </div>

    @forelse ($stockTransfersData as $location => $stockTransferRecords)
        <h2> Location: <strong>{{ $location }}</strong> </h2>

        <table class="table table-bordered">
            <thead >
                <tr>
                    @foreach($columns as $column)
                        @if($column === 'Price')
                            <th class="text-center">{{ $displayTotal ? $column : ''}}</th>
                        @else
                            <th class="text-center">{{ $column }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse($stockTransferRecords as $stockTransferData)
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['date'] }}</td>
                        <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['upc'] }}</td>
                        <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}} pr-5">{{ $stockTransferData['location_name'] }}</td>
                        <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['name'] }}</td>
                        <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['status'] }}</td>
                        @if(config('app.product_variant'))
                            <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['attributes'] }}</td>
                        @else
                            <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['color'] }}</td>
                            <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['size'] }}</td>
                        @endif
                        <td class="mt-2 text-center {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['quantity'] }}</td>
                        <td class="mt-2 text-center {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['received_quantity'] }}</td>
                        @if($displayTotal)
                            @if ($stockTransferData['date'] === 'Total')
                                <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['total_price'] }}</td>
                            @else
                                <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ CommonFunctions::currencyFormat($stockTransferData['total_price']) }}</td>
                            @endif
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No Records</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <div class="text-center">
            <h2>No Records Found.</h2>
        </div>
    @endforelse
</body>
</html>
