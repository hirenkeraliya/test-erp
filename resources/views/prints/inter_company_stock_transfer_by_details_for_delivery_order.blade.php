<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Delivery Order (By Details)</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Delivery Order Report" reportType="By Details" :filterBy="$filterBy"
        :dateRange="$dateRange" :date="$date" />
    <div>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Location: <strong>{{ $location['name'] }} ({{ $location['code'] }})</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="text-left">Date</th>
                <th class="text-left">Delivery Order No</th>
                <th class="text-left">Sales Order No</th>
                <th class="text-left">Purchase Order No</th>
                <th class="text-left">Invoice No</th>
                <th class="text-left">External Company Name</th>
                <th class="text-left">External Location Name</th>
                <th class="text-left">Status</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Received Quantity</th>
                @if ($displayPurchaseCost)
                    <th class="text-right">Total Purchase Cost({{ $currencySymbol }})</th>
                @endif
                <th class="text-center">Remark</th>
            </tr>
        </thead>

        <tbody>
            @forelse($interCompanyDeliveryOrdersData as $deliveryOrderData)

                <tr class="page-break-inside-avoid">
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['date'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['delivery_order_number'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['sales_order_number'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['purchase_order_number'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['invoice_number'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['external_company_name'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['external_location_name'] }}</td>
                    <td class="{{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['status'] }}</td>
                    <td
                        class="mt-2 text-center {{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['quantity'] }}</td>
                    <td
                        class="mt-2 text-center {{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['received_quantity'] }}</td>
                    @if ($displayPurchaseCost)
                    <td class="mt-2 text-right {{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : ''}}">
                        {{ $deliveryOrderData['total_purchase_cost'] }}</td>
                    @endif
                    <td class="mt-2 {{ $deliveryOrderData['date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $deliveryOrderData['remark'] }}</td>
                </tr>
                @if (array_key_exists('transfer_from_and_to', $deliveryOrderData))
                    <tr class="page-break-inside-avoid">
                        <td></td>
                        @if ($displayPurchaseCost)
                        <td colspan="11">
                        @else
                        <td colspan="9">
                        @endif
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="border-top-none">Name</th>
                                        <th class="border-top-none">Article number</th>
                                        <th class="text-right border-top-none">Quantity</th>
                                        <th class="text-right border-top-none">Package Quantity</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($deliveryOrderData['transfer_from_and_to'] as $key => $product)
                                        <tr>
                                            <td class="border-top-none">{{ $product['name'] }}</td>
                                            <td class="border-top-none">{{ $product['article_number'] }}</td>
                                            <td class="text-right border-top-none">{{ $product['total_quantity'] }}
                                            </td>
                                        </tr>
                                        @if (array_key_exists('color_wise_products', $product))
                                            <tr>
                                                <td class="border-top-none"></td>
                                                <td colspan="3" class="border-top-none">
                                                    <table class="table">
                                                        <thead>
                                                            <tr>
                                                                <th class="border-top-none">UPC</th>
                                                                <th class="border-top-none">Color</th>
                                                                <th class="border-top-none">Size</th>
                                                                <th class="text-right border-top-none">Quantity</th>
                                                                <th class="text-right border-top-none">Rec.Quantity</th>
                                                                @if ($displayPurchaseCost)
                                                                <th class="text-right border-top-none">Purchase Cost</th>
                                                                <th class="text-right border-top-none">Total</th>
                                                                @endif
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            @foreach ($product['color_wise_products'] as $colorWiseProduct)
                                                                <tr>
                                                                    <td class="border-top-none">
                                                                        {{ $colorWiseProduct['upc'] }}</td>
                                                                    <td class="border-top-none">
                                                                        {{ $colorWiseProduct['color'] }}</td>
                                                                    <td class="border-top-none">
                                                                        {{ $colorWiseProduct['size'] }}</td>
                                                                    <td class="text-right border-top-none">
                                                                        {{ $colorWiseProduct['transfer_quantity'] }}
                                                                    </td>
                                                                    <td class="text-right border-top-none">
                                                                        {{ $colorWiseProduct['received_quantity'] }}
                                                                    </td>
                                                                    @if ($displayPurchaseCost)
                                                                    <td class="text-right border-top-none">
                                                                        {{ $colorWiseProduct['purchase_cost'] }}
                                                                    </td>
                                                                    <td class="text-right border-top-none">
                                                                        @currencyFormat($colorWiseProduct['purchase_cost'] * $colorWiseProduct['received_quantity'])
                                                                    </td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                        <td></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="6" class="text-center">No Records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
