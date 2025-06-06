<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Inter Company Delivery Order</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Inter Company Delivery Order Report" reportType="By Summary By UPC" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Location: <strong>{{ $location['name'] }} ({{ $location['code']}})</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead >
            <tr>
                <th class="text-left">Date</th>
                <th class="text-left">UPC</th>
                <th class="text-left">Delivery Order Numbers</th>
                <th class="text-left">Sales Order Numbers</th>
                <th class="text-left">Purchase Order Numbers</th>
                <th class="text-left">Invoice Numbers</th>
                <th class="text-left">External Company Name</th>
                <th class="text-left">External Location Name</th>
                <th class="text-left">Name</th>
                <th class="text-left">Status</th>
                <th class="text-left">Color</th>
                <th class="text-left">Size</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Received Quantity</th>
                @if ($displayPurchaseCost)
                <th class="text-right">Purchase Cost</th>
                <th class="text-right">Total Purchase Cost({{ $currencySymbol }})</th>
                @endif
            </tr>
        </thead>

        <tbody>
            @forelse($interCompanyDeliveryOrdersData as $deliveryOrderData)
                <tr class="page-break-inside-avoid">
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['date'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['upc'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['delivery_order_numbers'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['sales_order_number'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['purchase_order_number'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['invoice_number'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}} pr-5">{{ $deliveryOrderData['external_company_name'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}} pr-5">{{ $deliveryOrderData['external_location_name'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['name'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['status'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['color'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['size'] }}</td>
                    <td class="mt-2 text-right {{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['quantity'] }}</td>
                    <td class="mt-2 text-right {{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['received_quantity'] }}</td>
                    @if ($displayPurchaseCost)
                    <td class="mt-2 text-right {{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['purchase_cost'] }}</td>
                    <td class="mt-2 text-right {{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">{{ $deliveryOrderData['total_purchase_cost'] }}</td>
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
