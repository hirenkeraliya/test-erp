<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Order Report </title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Order Report" reportType="By Document" :dateRange="$dateRange"
        :date="$date" />

    <h4 class="font-bold">
        {{ $location->getNameWithCode() }}
    </h4>

    <table class="table table-bordered">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th class="text-center">
                        {{ $column }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse($ordersData as $orderData)
                <tr class="page-break-inside-avoid">
                    <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">{{ $orderData['date'] }}</td>
                    <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $orderData['receipt_number'] }}</td>
                    <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">{{ $orderData['member'] }}</td>
                    <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $orderData['bill_reference_number'] }}</td>
                    <td class="{{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">{{ $orderData['type'] }}</td>
                    <td class="mt-2 text-center {{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $orderData['quantity'] }}</td>
                    <td class="mt-2 text-center {{ $orderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $orderData['total_price'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No Records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
