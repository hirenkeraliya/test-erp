<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> {{ $isStatusAllowed ? 'Stock Transfer Status' : 'Stock Transfer' }} (By Details)</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="{{ $isStatusAllowed ? 'Stock Transfer Status Report' : 'Stock Transfer Report' }}" reportType="By Details" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />
    <div>
        @if ($isStatusAllowed)
            <p> Status: <strong>{{ $status }}</strong> </p>
        @endif
        <p> Transfer Type: <strong>{{ $transferType }}</strong> </p>
        <p> Records From Date Selection: <strong>{{ $dateSelectionType }}</strong> </p>
        <p> Show Price: <strong>{{ $displayTotal ? 'Yes' : 'No'}}</strong> </p>
    </div>

    @forelse ($stockTransfersData as $location => $stockTransferRecord)
        <h2> Location: <strong>{{ $location }}</strong> </h2>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-center">Date ( {{ $displayDateSelectionType }} )</th>
                    <th class="text-center">Reference Number</th>
                    <th class="text-center">Transfer Number</th>
                    <th class="text-center">Transfer Type</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Location</th>
                    <th class="text-center">Reason</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Rec.Quantity</th>
                    @if($displayTotal)
                        <th class="text-center">Price</th>
                    @endif
                    <th class="text-center">Package Type</th>
                    <th class="text-center">Remark</th>
                    <th class="text-center">Requested At</th>
                </tr>
            </thead>

            <tbody>
                @forelse($stockTransferRecord as $stockTransferData)
                    <tr class="page-break-inside-avoid">
                        <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['transfer_date'] }}</td>
                        <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['reference_number'] }}</td>
                        <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['transfer_number'] }}</td>
                        <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['transfer_type'] }}</td>
                        <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['status'] }}</td>
                        <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['receiver_location'] }}</td>
                        <td class="{{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['reason'] }}</td>
                        <td class="mt-2 text-center {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['quantity'] }}</td>
                        <td class="mt-2 text-center {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['received_quantity'] }}</td>
                        @if($displayTotal)
                            <td class="mt-2 text-right {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['total_price'] }}</td>
                        @endif
                        <td class="mt-2 text-center {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">
                            @if (array_key_exists('package_type', $stockTransferData) && $stockTransferData['package_type'])
                                @foreach ($stockTransferData['package_type'] as $packageTypeName => $packageTypeQuantity)
                                    @if ($packageTypeName !== 'N/A')
                                        {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                    @endif
                                @endforeach
                            @endif
                        </td>
                        <td class="mt-2 {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['remark'] }}</td>
                        <td class="mt-2 {{ $stockTransferData['transfer_date'] === 'Total' ? 'text-bold' : ''}}">{{ $stockTransferData['requested_by'] }}</td>
                    </tr>
                    @if(array_key_exists('transfer_from_and_to', $stockTransferData) && count($stockTransferData['transfer_from_and_to']) > 0)
                        <tr class="page-break-inside-avoid">
                            <td></td>
                            <td colspan="{{ $displayTotal ? 10 : 9 }}">
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
                                        @foreach($stockTransferData['transfer_from_and_to'] as $key => $product)
                                            <tr>
                                                <td class="border-top-none">{{ $product['name'] }}</td>
                                                <td class="border-top-none">{{ $product['article_number'] }}</td>
                                                <td class="text-right border-top-none">{{ $product['total_quantity'] }}</td>
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
