<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> {{ 'Stock Transfer Status Summary' }} </title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Stock Transfer Status Summary Report" reportType="By Summary" :dateRange="$dateRange" :date="$date"  />
    <p>
        Man days start from the
        <strong>
            {{ $manDaysStatus }}
        </strong>
        Status.
    </p>
    @if($locations)
    <p>
        Source Locations:
        <strong>
            {{ $locations }}
        </strong>
    </p>
    @endif

    @forelse ($stockTransfers as $stockTransfer)
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="text-center">Ref #</th>
                <th class="text-center">Transactions</th>
                <th class="text-center">Total Man Days</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 16px; border-right: 1px solid black;">
                    <strong>Transfer Order Number:</strong> {{ $stockTransfer['transfer_order_number'] ?? 'N/A' }}<br>
                    <strong>Request Order Number:</strong> {{ $stockTransfer['request_order_number'] ?? 'N/A' }}<br>
                    <strong>Transfer Out Number:</strong> {{ $stockTransfer['transfer_out_number'] ?? 'N/A' }}<br>
                    <strong>Transfer In Number:</strong> {{ $stockTransfer['transfer_in_number'] ?? 'N/A' }}<br>
                    <strong>Receiver Location:</strong> {{ $stockTransfer['destination_location'] ?? 'N/A' }}<br>
                </td>
                <td style="padding: 16px; border-right: 1px solid black;">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockTransfer['transactions'] as $transaction)
                                <tr>
                                    <td>{{ $transaction['label'] }}</td>
                                    <td>{{ $transaction['date'] }} ({{ $transaction['human_readable_date'] }})</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No Transactions Available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
                <td style="padding: 16px; text-align: center; vertical-align: middle;">
                    {{ $stockTransfer['total_man_days'] }}
                </td>
            </tr>
        </tbody>
    </table>
@empty
    <div class="text-center">
        <h2>No Records Found.</h2>
    </div>
@endforelse


</body>
</html>
