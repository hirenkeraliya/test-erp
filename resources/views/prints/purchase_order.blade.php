<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="stylesheet" href="{{ asset('/css/purchase-invoice-print.css') }}" />
        <title>{{ $orderType }}</title>
    </head>

    <body class="main arial-font arial-font-custom-report">
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
            <h3 class="title">{{ $orderType }}</h3>
        </div>

        <div class="row">
            <div class="col-3">
                <div class="pr-1">
                    <h2>From:</h2>
                    <p>
                        <b>{{ $fromLocation->name }}</b><br>
                        {{ $fromLocation->address_line_1 }},
                        {{ $fromLocation->address_line_2 }}<br>
                        {{ $fromCity }}<br>
                        <b>Tel:</b> {{ $fromLocation->phone }}<br>
                        <b>FAX:</b> {{ $fromLocation->fax }}
                    </p>
                </div>
            </div>

            <div class="col-3">
                <h2>To:</h2>
                <p>
                    <b>{{ $toLocation->name }}</b><br>
                    <b>({{ $toCompany->name}})</b><br>
                    {{ $toLocation->address_line_1 }},
                    {{ $toLocation->address_line_2 }} <br>
                    {{ $toCity }} <br>
                    <b>Tel:</b> {{ $toLocation->phone }} <br>
                    <b>FAX:</b> {{ $toLocation->fax }} <br>
                </p>
            </div>

            <div class="col-3 mt-2-5">
                <p>
                    <b> Order No:</b> {{ $purchaseOrder->order_number }}<br>
                    <b> Date:</b> {{ $purchaseOrder->created_at }}<br>
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
                    <th class="text-center">Transfer Quantity</th>
                </tr>
            </thead>

            <tbody>
                @php $number = 1; @endphp
                @foreach($purchaseOrderItems as $purchaseOrderItem)
                <tr class="{{ $loop->index !== 0 ? 'page-break-inside-avoid' : '' }}">
                    <td>{{ $number++ }}</td>
                    <td>
                        {{ $purchaseOrderItem['article_number'] }}
                    </td>
                    <td>
                        {{ $purchaseOrderItem['product_name'] }}
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
                                    <th>Transfer Quantity</th>
                                </tr>
                            </thead>
                            @foreach($purchaseOrderItem['products'] as $key => $product)
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
                                    <td rowspan="2" width="20" class="no-border">{{ $product['quantity'] }}</td>
                                    <td rowspan="2" width="20" class="no-border">{{ $product['transferred_quantity'] }}</td>
                                </tr>
                            </tbody>
                            @endforeach
                        </table>
                    </td>
                    <td>{{ $purchaseOrderItem['total_quantity'] }}</td>
                    <td>{{  $purchaseOrderItem['total_transferred_quantity']  }}</td>
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
                        <strong>Opened by: {{ $openedBy }}</strong>
                    </div>
                    <div class="col-12">
                        <strong>Approved by: {{ $approvedBy }}</strong>
                    </div>
                    <div class="col-12">
                        <strong>Cancelled by: {{ $cancelledBy }}</strong>
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
