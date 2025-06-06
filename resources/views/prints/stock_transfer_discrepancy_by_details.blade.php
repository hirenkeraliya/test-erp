<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Stock Transfer Discrepancy Report (By Details)</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Stock Transfer Discrepancy Report" reportType="By Details" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Records From Selected Date: <strong>{{ $dateSelectionType }}</strong> </p>
    </div>

    @foreach ($stockTransfersData as $stockTransferRecord)
        <p> Location : <strong> {{ $stockTransferRecord['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-left">Transfer Date ( {{ $displaySelectedDateType }} )</th>
                    <th class="text-left">Reference Number</th>
                    <th class="text-left">Transfer Number</th>
                    <th class="text-left">Transfer Type</th>
                    <th class="text-left">Status</th>
                    <th class="text-left">Location</th>
                    <th class="text-left">Package Type</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Received Quantity</th>
                    <th class="text-right">Discrepancy Quantity</th>
                </tr>
            </thead>

            <tbody>
                @if (count($stockTransferRecord['stock_transfers']) > 0)
                    @forelse($stockTransferRecord['stock_transfers'] as $stockTransferData)
                        <tr class="page-break-inside-avoid">
                            <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['transfer_date'] }}</td>
                            <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['reference_number'] }}</td>
                            <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['transfer_number'] }}</td>
                            <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['transfer_type'] }}</td>
                            <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['status'] }}</td>
                            <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['receiver_location'] }}</td>
                            <td class="mt-2 text-left {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">
                                @if (array_key_exists('package_type', $stockTransferData) && $stockTransferData['package_type'])
                                    @foreach ($stockTransferData['package_type'] as $packageTypeName => $packageTypeQuantity)
                                        @if ($packageTypeName !== 'N/A')
                                            {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                        @endif
                                    @endforeach
                                @endif
                            </td>
                            <td class="mt-2 text-right {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['quantity'] }}</td>
                            <td class="mt-2 text-right {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['received_quantity'] }}</td>
                            <td class="mt-2 text-right {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['discrepancy_quantity'] }}</td>
                        </tr>
                        @if(array_key_exists('transfer_from_and_to', $stockTransferData))
                            <tr class="page-break-inside-avoid">
                                <td></td>
                                <td colspan="8">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="border-top-none">Name</th>
                                                <th class="border-top-none">Article number</th>
                                                <th class="text-right border-top-none">Quantity</th>
                                                <th class="text-right border-top-none">Received Quantity</th>
                                                <th class="text-right border-top-none">Discrepancy Quantity</th>
                                                <th class="text-right border-top-none">Package Quantity</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach($stockTransferData['transfer_from_and_to'] as $key => $product)
                                                <tr>
                                                    <td class="border-top-none">{{ $product['name'] }}</td>
                                                    <td class="border-top-none">{{ $product['article_number'] }}</td>
                                                    <td class="text-right border-top-none">{{ $product['total_quantity'] }}</td>
                                                    <td class="text-right border-top-none">{{ $product['total_received_quantity'] }}</td>
                                                    <td class="text-right border-top-none">{{ $product['total_discrepancy_quantity'] }}</td>
                                                    <td class="text-right border-top-none">
                                                        @if ($product['total_package_quantity'])
                                                            @foreach ($product['total_package_quantity'] as $packageTypeName => $packageTypeQuantity)
                                                                @if ($packageTypeName !== 'N/A')
                                                                    {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if(array_key_exists('color_wise_products', $product))
                                                <tr>
                                                    <td class="border-top-none"></td>
                                                    <td colspan="2" class="border-top-none">
                                                        <table class="table">
                                                            <thead >
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
                                                                    <th class="text-right border-top-none">Dis.Quantity</th>
                                                                    <th class="text-right border-top-none">Package Type Quantity</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                @foreach($product['color_wise_products'] as $colorWiseProduct)
                                                                    <tr>
                                                                        <td class="border-top-none">{{ $colorWiseProduct['upc'] }}</td>
                                                                        @if(config('app.product_variant'))
                                                                            <td class="border-top-none">{{ $colorWiseProduct['attributes'] }}</td>
                                                                        @else
                                                                            <td class="border-top-none">{{ $colorWiseProduct['color'] }}</td>
                                                                            <td class="border-top-none">{{ $colorWiseProduct['size'] }}</td>
                                                                        @endif
                                                                        <td class="text-right border-top-none">{{ $colorWiseProduct['quantity'] }}</td>
                                                                        <td class="text-right border-top-none">{{ $colorWiseProduct['received_quantity'] }}</td>
                                                                        <td class="text-right border-top-none">{{ $colorWiseProduct['discrepancy_quantity'] }}</td>
                                                                        <td class="text-right border-top-none">{{ $colorWiseProduct['package_type'] }}</td>
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
                    @endforeach
                @else
                    <tr>
                        <td colspan="10" class="text-center">No Records</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
</body>
</html>
