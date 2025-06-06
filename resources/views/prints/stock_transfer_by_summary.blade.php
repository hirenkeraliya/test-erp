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
    <x-report-header :company="$company" reportName="{{ $isStatusAllowed ? 'Stock Transfer Status Report' : 'Stock Transfer Report' }}" reportType="By Summary" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        @if ($isStatusAllowed && $status)
            <p> Status: <strong>{{ $status }}</strong> </p>
        @endif
        <p> Date Selection: <strong>{{ $dateSelectionType }}</strong> </p>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Show Price: <strong>{{ $displayTotal ? 'Yes' : 'No'}}</strong> </p>
    </div>

    @forelse ($stockTransfersData as $location => $stockTransferData)
        <h2> Location : <strong> {{ $location }} </strong> </h2>

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
                @forelse($stockTransferData as $stockTransfer)
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransfer['date'] }}</td>
                        <td class="{{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransfer['upc'] }}</td>
                        <td class="{{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransfer['article_number'] }}</td>
                        <td class="{{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}} pr-5">{{ $stockTransfer['location_name'] }}</td>
                        <td class="{{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransfer['name'] }}</td>
                        <td class="{{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransfer['status'] }}</td>
                        <td class="mt-2 text-center {{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransfer['quantity'] }}</td>
                        <td class="mt-2 text-center {{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransfer['received_quantity'] }}</td>
                        @if($displayTotal)
                            @if ($stockTransfer['date'] === 'Total')
                                <td class="mt-2 text-right {{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransfer['total_price'] }}</td>
                            @else
                                <td class="mt-2 text-right {{ $stockTransfer['date'] === 'Total' ? 'text-bold' : ''}}">{{ CommonFunctions::currencyFormat((float) $stockTransfer['total_price']) }}</td>
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
