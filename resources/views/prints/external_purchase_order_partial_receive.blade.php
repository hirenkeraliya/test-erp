<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/purchase-invoice-print.css') }}" />
    <title>External Purchase Order Partial Receive</title>

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
        <h3 class="title">External Purchase Order Partial Receive</h3>
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
                <b>{{ $toLocation->name }}</b><br>
                {{ $toLocation->address_line_1 }},
                {{ $toLocation->address_line_2 }} <br>
                {{ $toLocation->city ? $toLocation->city->name : '' }} <br>
                <b>Tel:</b> {{ $toLocation->phone }} <br>
                <b>FAX:</b> {{ $toLocation->fax }} <br>
            </p>
        </div>

        <div class="col-6 mt-2-5">
            <p>
                <b>Received Date:</b> {{ $externalPurchaseOrderReceive->received_date ? date('d/m/Y', strtotime($externalPurchaseOrderReceive->received_date)) : 'N/A' }}<br>
                <b>External Purchase Order No:</b> {{ $externalPurchaseOrder->order_number }}<br>
                <b>Purchase Plan No:</b> {{ $purchasePlan->plan_number }}<br>
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
            </tr>
        </thead>

        <tbody>
            @php $number = 1; @endphp
            @foreach($externalPurchaseOrderReceiveItems as $key => $externalPurchaseOrderReceiveItem)
                <tr class="{{ $loop->index !== 0 ? 'page-break-inside-avoid' : '' }}">
                    <td>{{ $number++ }}</td>
                    <td>
                        {{ $externalPurchaseOrderReceiveItem['article_number'] }}
                    </td>
                    <td>
                        {{ $externalPurchaseOrderReceiveItem['product_name'] }}
                        <table class="table mt-2">
                            <thead>
                                <tr>
                                    <th>UPC</th>
                                    <th>Color</th>
                                    <th>Size</th>
                                    <th>Quantity</th>
                                    <th>Received Quantity</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            @foreach($externalPurchaseOrderReceiveItem['products'] as $key => $product)
                            <tbody>
                                <tr>
                                    <td rowspan="2" width="100" class="no-border">{{ $product['upc'] }}</td>
                                    <td rowspan="2" width="100" class="no-border">{{ $product['color'] }}</td>
                                    <td rowspan="2" width="80" class="no-border">{{ $product['size'] }}</td>
                                    <td rowspan="2" width="20" class="no-border">{{ $product['quantity'] }}</td>
                                    <td rowspan="2" width="20" class="no-border">{{ $product['received_quantity'] }}</td>
                                    <td rowspan="2" width="20" class="no-border">{{ $product['notes'] }}</td>
                                </tr>
                            </tbody>
                            @endforeach
                        </table>
                    </td>
                    <td>{{ $externalPurchaseOrderReceiveItem['quantity'] }}</td>
                    <td>{{ $externalPurchaseOrderReceiveItem['received_quantity'] }}</td>
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
    </div>
</body>
</html>
