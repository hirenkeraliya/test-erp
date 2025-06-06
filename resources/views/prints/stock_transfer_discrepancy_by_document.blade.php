<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Transfer Report</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Stock Transfer Discrepancy Report" reportType="By Document" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    <div>
        <p> Records From Selected Date: <strong>{{ $dateSelectionType }}</strong> </p>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
    </div>

    @foreach ($stockTransfersData as $stockTransferRecord)
        <p> Location : <strong> {{ $stockTransferRecord['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    @foreach ($columns as $column)
                        <th class="text-left"> {{ $column }} </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @if (count($stockTransferRecord['stock_transfers']) > 0)
                    @forelse($stockTransferRecord['stock_transfers'] as $stockTransferData)
                        <tr class="page-break-inside-avoid">
                            <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['date'] }}</td>
                            <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['no'] }}</td>
                            <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['reference_number'] }}</td>
                            <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['transfer_type'] }}</td>
                            <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['status'] }}</td>
                            <td class="mt-2 {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['location_name'] }}</td>
                            <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['quantity'] }}</td>
                            <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['received_quantity'] }}</td>
                            <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['discrepancy_quantity'] }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="9" class="text-center">No Records</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
</body>
</html>
