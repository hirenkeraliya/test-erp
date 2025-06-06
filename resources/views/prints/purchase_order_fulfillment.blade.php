<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/purchase-invoice-print.css') }}" />
    <title>Delivery Order</title>

</head>

<body class="arial-font arial-font-custom-report">
    <div class="row m-0">
        <div class="col-6 border">
            <div class="row">
                <div class="col-4">
                    <img alt="logo" class="img-fluid rounded" src="{{ $fromCompany->getDiskBasedFirstMediaUrl('dark_logo') }}">
                </div>
                <div class="col-8">
                    <p> <b>Registered Company Name:</b> {{ $fromCompany->name }} </p>

                    <p> <b>SSN:</b> {{ $fromCompany->social_security_number }} </p>

                    <p> <b>Address:</b> {{ $fromCompany->address }} </p>
                </div>
            </div>
        </div>
    </div>

    <div class="divide"></div>

    <div class="text-center">
        <h3 class="title">Delivery Order</h3>
    </div>

    <div class="row">
        <div class="col-3">
            <div class="pr-1">
                <h2>From:</h2>
                <p>
                    <b>{{ $fromLocation['name'] }}</b><br>
                    {{ $fromLocation['address_line_1'] }},
                    {{ $fromLocation['address_line_2'] }}<br>
                    {{ $fromLocation['city'] }}<br>
                    <b>Tel:</b> {{ $fromLocation['phone'] }}<br>
                    <b>FAX:</b> {{ $fromLocation['fax'] }}
                </p>
            </div>
        </div>

        <div class="col-3">
            <h2>To:</h2>
            <p>
                <b>{{ $toLocation['name'] }}</b><br>
                {{ $toLocation['address_line_1'] }},
                {{ $toLocation['address_line_2'] }} <br>
                {{ $toLocation['city'] }} <br>
                <b>Tel:</b> {{ $toLocation['phone'] }} <br>
                <b>FAX:</b> {{ $toLocation['fax'] }} <br>
            </p>
        </div>

        <div class="col-6 mt-2-5">
            <p>
                <b>Transfer Date:</b> {{ $purchaseOrderFulfillment->happened_at ? date('d/m/Y', strtotime($purchaseOrderFulfillment->happened_at)) : 'N/A' }}<br>
                <b>Entry Date:</b> {{ $purchaseOrderFulfillment->created_at->format('d/m/Y') ?? 'N/A' }}<br>
                <b>Delivery Order No:</b> {{ $purchaseOrderFulfillment->delivery_order_number }}<br>
                <p>
                    <img src="data:image/png;base64,{{ $deliveryOrderBarcode }}" width="140" height="30">
                </p>
                <b>{{ $requestTitle }}:</b> {{ $requestNo }}<br>
                <b>{{ $orderTitle }}:</b> {{ $orderNo }}<br>
            </p>
        </div>
    </div>

    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">Article Number</th>
                <th class="text-center">Description</th>
                <th class="text-center">Quantity</th>
                <th class="text-center">Received Quantity</th>
                <th class="text-center">Package Type (Qty)</th>
            </tr>
        </thead>

        <tbody>
            @php $number = 1; @endphp
            @foreach($purchaseOrderFulfillmentItems as $key => $purchaseOrderFulfillmentItem)
                <tr class="{{ $loop->index !== 0 ? 'page-break-inside-avoid' : '' }}">
                    <td>{{ $number++ }}</td>
                    <td>
                        {{ $purchaseOrderFulfillmentItem['article_number'] }}
                    </td>
                    <td>
                        {{ $purchaseOrderFulfillmentItem['product_name'] }}
                        <table class="table mt-2">
                            <thead>
                                <tr>
                                    <th>UPC</th>
                                    @if(! $productVariant)
                                        <th>Color</th>
                                    @endif
                                    @if(! $productVariant)
                                        <th>Size</th>
                                    @endif
                                    @if($productVariant)
                                        <th>Attributes</th>
                                    @endif
                                    <th>Quantity</th>
                                    <th>Received Quantity</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            @foreach($purchaseOrderFulfillmentItem['products'] as $key => $product)
                            <tbody>
                                <tr>
                                    <td rowspan="2" width="100" class="no-border">{{ $product['upc'] }}</td>
                                    @if(! $productVariant)
                                            <td rowspan="2" width="100" class="no-border">{{ $product['color'] }}</td>
                                        @endif
                                        @if(! $productVariant)
                                            <td rowspan="2" width="80" class="no-border">{{ $product['size'] }}</td>
                                        @endif
                                        @if($productVariant)
                                            <td rowspan="2" width="80" class="no-border">
                                                @foreach($product['attributes'] as $key => $attribute)
                                                    {{ $key }} : {{ $attribute }}<br>
                                                @endforeach
                                            </td>
                                        @endif
                                    <td rowspan="2" width="20" class="no-border">{{ $product['transfer_quantity'] }}</td>
                                    <td rowspan="2" width="20" class="no-border">{{ $product['received_quantity'] }}</td>
                                    <td rowspan="2" width="20" class="no-border">{{ $product['remarks'] }}</td>
                                </tr>
                            </tbody>
                            @endforeach
                        </table>
                    </td>
                    <td>{{ $purchaseOrderFulfillmentItem['transfer_quantity'] }}</td>
                    <td>{{ $purchaseOrderFulfillmentItem['received_quantity'] }}</td>
                    <td>
                        {{ $purchaseOrderFulfillmentItem['package_quantity']}} :<br>
                        ({{ $purchaseOrderFulfillmentItem['package_total_quantity'] }})
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break-inside-avoid">
        <div class="row">
            <div class="col-4">
                <div class="row">
                    <div class="col-3 mb-2">
                        Remark:
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                        <hr class="mt-5">
                    </div>
                </div>
            </div>


        </div>

        <div class="row mt-3">
            <div class="col-4">
                <div class="row mt-3">
                    <div class="col-3 mb-2">
                        Checked by:
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="row mt-3">
                    <div class="col-3 mb-2">
                        Supervisor:
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="row mt-3">
                    <div class="col-3 mb-2">
                        Manager:
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4 mt-4">
                <div class="row">
                    <div class="col-12">
                        <strong>Shipped by: {{ $shippedBy }}</strong>
                    </div>
                    <div class="col-12">
                        <strong>Received by: {{ $receivedBy}}</strong>
                    </div>
                    <div class="col-12">
                        <strong>Discrepancy by: {{ $discrepancyBy}}</strong>
                    </div>
                    <div class="col-12">
                        <strong>Closed by: {{ $closedBy }}</strong>
                    </div>
                </div>
            </div>

            <div class="col-4 mt-4">
                <div
                    class="transfer-feedback-box"
                >
                    Transfer Feedback
                </div>
            </div>

            <div class="col-4 mt-4">
                <div class="row">
                    <div class="col-4">
                        Lorry Driver:<br><br>
                        Lorry No:<br><br>
                        Driver's I/C:<br><br>
                        Driver's Phone:
                    </div>

                    <div class="col-7">
                        <hr class="mt-2"><br>
                        <hr class="mt-2"><br>
                        <hr class="mt-2"><br>
                        <hr class="mt-3">
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
