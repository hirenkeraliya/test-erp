<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Stock Transfer (By Details)</title>
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
        <p> Location: <strong>{{ $location }}</strong> </p>

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
                    <th class="text-center">Main Quantity</th>
                    @if($displayTotal)
                        <th class="text-center">Main Price</th>
                    @endif
                    <th class="text-center">Main Package Type</th>
                    <th class="text-center">Remark</th>
                    <th class="text-center">Requested At</th>
                    <th class="text-center">Product Name</th>
                    <th class="text-center">Product Article Number</th>
                    <th class="text-center">Sub Quantity</th>
                    <th class="text-center">Sub Package Quantity</th>
                    <th class="text-center">Product UPC</th>
                    @if(config('app.product_variant'))
                        <th class="text-center">Attributes</th>
                    @else
                        <th class="text-center">Product Color</th>
                        <th class="text-center">Product Size</th>
                    @endif

                    <th class="text-center">Quantity</th>
                    <th class="text-center">Received Quantity</th>
                    <th class="text-center">Package Type</th>
                </tr>
            </thead>

            <tbody>
                @forelse($stockTransferRecord as $stockTransferData)
                    @if(array_key_exists('transfer_from_and_to', $stockTransferData))
                        @foreach($stockTransferData['transfer_from_and_to'] as $key => $product)
                            @if(array_key_exists('color_wise_products', $product))
                                @foreach($product['color_wise_products'] as $key => $colorWiseProduct)
                                    <tr>
                                        <td>{{ $stockTransferData['transfer_date'] }}</td>
                                        <td>{{ $stockTransferData['reference_number'] }}</td>
                                        <td>{{ $stockTransferData['transfer_number'] }}</td>
                                        <td>{{ $stockTransferData['transfer_type'] }}</td>
                                        <td>{{ $stockTransferData['status'] }}</td>
                                        <td>{{ $stockTransferData['receiver_location'] }}</td>
                                        <td>{{ $stockTransferData['reason'] }}</td>
                                        <td>{{ $stockTransferData['quantity'] }}</td>
                                        @if($displayTotal)
                                            <td>{{ $stockTransferData['total_price'] }}</td>
                                        @endif
                                        <td>
                                            @if (array_key_exists('package_type', $stockTransferData) && $stockTransferData['package_type'])
                                                @foreach ($stockTransferData['package_type'] as $packageTypeName => $packageTypeQuantity)
                                                    @if ($packageTypeName !== 'N/A')
                                                        {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                                    @endif
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>{{ $stockTransferData['remark'] }}</td>
                                        <td>{{ $stockTransferData['requested_by'] }}</td>
                                        <td>{{ $product['name'] }}</td>
                                        <td>{{ $product['article_number'] }}</td>
                                        <td>{{ $product['total_quantity'] }}</td>
                                        <td>
                                            @if ($product['total_package_quantity'])
                                                @foreach ($product['total_package_quantity'] as $packageTypeName => $packageTypeQuantity)
                                                    @if ($packageTypeName !== 'N/A')
                                                        {{ $packageTypeName . ":" . $packageTypeQuantity }}
                                                    @endif
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>{{ $colorWiseProduct['upc'] }}</td>
                                         @if(config('app.product_variant'))
                                             <td>{{ $colorWiseProduct['attributes'] }}</td>
                                        @else
                                            <td>{{ $colorWiseProduct['color'] }}</td>
                                            <td>{{ $colorWiseProduct['size'] }}</td>
                                        @endif
                                        <td>{{ $colorWiseProduct['quantity'] }}</td>
                                        <td>{{ $colorWiseProduct['received_quantity'] }}</td>
                                        <td>{{ $colorWiseProduct['package_type'] }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
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
