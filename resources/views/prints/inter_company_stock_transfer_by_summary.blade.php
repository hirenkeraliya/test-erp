<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Inter Company Stock Transfer</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Inter Company Stock Transfer Report" reportType="By Summary" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Show Purchase Cost: <strong>{{ $displayPurchaseCost ? 'Yes' : 'No'}}</strong> </p>
        <p> Location: <strong>{{ $location['name'] }} ({{ $location['code']}})</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead >
            <tr>
                <th class="text-left">Date</th>
                <th class="text-left">Article Number</th>
                <th class="text-left">Delivery Order Numbers</th>
                <th class="text-left">Sales Order Number</th>
                <th class="text-left">Purchase Order Number</th>
                <th class="text-left">Invoice Number</th>
                <th class="text-left">External Company Name</th>
                <th class="text-left">External Location Name</th>
                <th class="text-left">Name</th>
                <th class="text-left">Status</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Transferred Quantity</th>
                @if($displayPurchaseCost)
                    <th class="text-right">Purchase Cost</th>
                    <th class="text-right">Total Purchase Cost({{ $currencySymbol }})</th>
                @endif
            </tr>
        </thead>

        <tbody>
            @forelse($interCompanyStockTransfersData as $stockTransferData)
                <tr class="page-break-inside-avoid">
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['date'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['article_number'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['delivery_order_numbers'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['sales_order_number'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['purchase_order_number'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['invoice_number'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}} pr-5">{{ $stockTransferData['external_company_name'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}} pr-5">{{ $stockTransferData['external_location_name'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['name'] }}</td>
                    <td class="{{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['status'] }}</td>
                    <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['quantity'] }}</td>
                    <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['transferred_quantity'] }}</td>
                    @if($displayPurchaseCost)
                        <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['purchase_cost'] }}</td>
                        <td class="mt-2 text-right {{ $stockTransferData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['total_purchase_cost'] }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No Records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
