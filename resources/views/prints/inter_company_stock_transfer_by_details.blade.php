<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Stock Transfer (By Details)</title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Stock Transfer Report" reportType="By Details" :filterBy="$filterBy"
        :dateRange="$dateRange" :date="$date" />
    <div>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Show Purchase Cost: <strong>{{ $displayPurchaseCost ? 'Yes' : 'No' }}</strong> </p>
        <p> Location: <strong>{{ $location['name'] }} ({{ $location['code'] }})</strong> </p>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="text-left">Transfer Date</th>
                <th class="text-left">Delivery Order Number</th>
                @if($transferType === 'Purchase Request')
                    <th class="text-left">Purchase Request Number</th>
                    <th class="text-left">External Purchase Request Number</th>
                @elseif($transferType === 'Transfer Request')
                    <th class="text-left">Transfer Request Number</th>
                    <th class="text-left">External Transfer Request Number</th>
                @else
                    <th class="text-left">Sales Order Number</th>
                    <th class="text-left">Purchase Order Number</th>
                @endif
                <th class="text-left">Invoice Number</th>
                <th class="text-left">Reference Number</th>
                <th class="text-left">Transfer Type</th>
                <th class="text-left">External Company Name</th>
                <th class="text-left">External Location Name</th>
                <th class="text-left">Status</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Transferred Quantity</th>
                @if ($displayPurchaseCost)
                    <th class="text-right">Purchase Cost</th>
                    <th class="text-right">Total Purchase Cost({{ $currencySymbol }})</th>
                @endif
                <th class="text-left">Remark</th>
            </tr>
        </thead>

        <tbody>
            @forelse($interCompanyStockTransfersData as $stockTransferData)
                <tr class="page-break-inside-avoid">
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['transfer_date'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['delivery_order_numbers'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['sales_order_number'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['purchase_order_number'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['invoice_number'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['reference_number'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['transfer_type'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['external_company_name'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['external_location_name'] }}</td>
                    <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['status'] }}</td>
                    <td
                        class="mt-2 text-right {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['quantity'] }}</td>
                    <td
                        class="mt-2 text-right {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['transferred_quantity'] }}</td>
                    @if ($displayPurchaseCost)
                    <td
                        class="mt-2 text-right {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['purchase_cost'] }}</td>
                    <td
                        class="mt-2 text-right {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['total_purchase_cost'] }}</td>
                    @endif
                    <td class="mt-2 {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : '' }}">
                        {{ $stockTransferData['remark'] }}</td>
                </tr>
                @if (array_key_exists('transfer_from_and_to', $stockTransferData))
                    <tr class="page-break-inside-avoid">
                        <td></td>
                        <td colspan="{{ $displayPurchaseCost ? 12 : 10 }}">
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
                                    @foreach ($stockTransferData['transfer_from_and_to'] as $key => $product)
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
                                                               @if(config('app.product_variant'))
                                                                    <th class="border-top-none">Attributes</th>
                                                                @else
                                                                    <th class="border-top-none">Color</th>
                                                                    <th class="border-top-none">Size</th>
                                                                @endif
                                                                <th class="text-right border-top-none">Quantity</th>
                                                                <th class="text-right border-top-none">Rec.Quantity</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            @foreach ($product['color_wise_products'] as $colorWiseProduct)
                                                                <tr>
                                                                    <td class="border-top-none">
                                                                        {{ $colorWiseProduct['upc'] }}</td>
                                                                    @if(config('app.product_variant'))
                                                                        <td class="border-top-none">{{ $colorWiseProduct['attributes'] }}</td>
                                                                    @else
                                                                        <td class="border-top-none">{{ $colorWiseProduct['color'] }}</td>
                                                                        <td class="border-top-none">{{ $colorWiseProduct['size'] }}</td>
                                                                    @endif
                                                                    <td class="text-right border-top-none">
                                                                        {{ $colorWiseProduct['quantity'] }}</td>
                                                                    <td class="text-right border-top-none">
                                                                        {{ $colorWiseProduct['transferred_quantity'] }}
                                                                    </td>
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
                        <td></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="12" class="text-center">No Records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
