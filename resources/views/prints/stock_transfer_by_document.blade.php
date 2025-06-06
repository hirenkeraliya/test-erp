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
    <x-report-header :company="$company" reportName="{{ $isStatusAllowed ? 'Stock Transfer Status Report' : 'Stock Transfer Report' }}" reportType="By Document" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        @if ($isStatusAllowed)
            <p> Status: <strong>{{ $status }}</strong> </p>
        @endif
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Date Selection Type: <strong>{{ $dateSelectionType }}</strong> </p>
        <p> Show Price: <strong>{{ $displayTotal ? 'Yes' : 'No'}}</strong> </p>
    </div>

    @forelse($stockTransfersData as $key => $stockTransferRecord)
        <p> Location: <strong>{{ $key }}</strong> </p>

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
                @forelse($stockTransferRecord as $stockTransferData)
                    <tr class="page-break-inside-avoid">
                        <td>{{ $stockTransferData['date'] }}</td>
                        <td>{{ $stockTransferData['no'] }}</td>
                        <td>{{ $stockTransferData['reference_number'] }}</td>
                        <td>{{ $stockTransferData['transfer_type'] }}</td>
                        <td>{{ $stockTransferData['status'] }}</td>
                        <td class="mt-2 {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['location_name'] }}</td>
                        <td class="mt-2 text-center {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['quantity'] }}</td>
                        <td class="mt-2 text-center {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['received_quantity'] }}</td>
                        @if($displayTotal)
                            <td class="mt-2 text-center {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['total_price'] }}</td>
                        @endif
                        <td class="mt-2 {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['reason'] }}</td>
                        <td class="mt-2 {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['remark'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No Records</td>
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
