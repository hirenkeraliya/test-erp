<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="stylesheet" href="{{ asset('/css/purchase-invoice-print.css') }}" />
        <title>Purchase Order Invoice</title>
    </head>

    <body class="main arial-font arial-font-custom-report">
        <div class="row m-0">
            <div class="col-12 border">
                <div class="row">
                    <div class="col-4">
                        <img alt="logo" class="img-fluid rounded" src="{{$fromCompany->getDiskBasedFirstMediaUrl('dark_logo')}}" />
                    </div>

                    <div class="col-8 text-left">
                        <div class="flex justify-between">
                            <div>
                                <p><b>Registered Company Name:</b> {{ $fromCompany->name }}</p>

                                <p><b>SSN:</b> {{ $fromCompany->social_security_number }}</p>

                                <p><b>Address:</b> {{ $fromCompany->address }}</p>
                            </div>
                            <div class="pr-2 pt-2">
                                <h1 class="text-right">{{$status}}</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="divide"></div>

        <div class="text-center">
            <h3 class="title">Tax Invoice</h3>
        </div>

        <div class="row">
            <div class="col-6 mb-2">
                <p><b>Bill To:</b></p>
                <p><b>{{ $toCompany->name }}</b></p>
                @if ($toCompany->social_security_number)
                <p> <b>SSN:</b> {{ $toCompany->social_security_number }} </p>
                @endif
                @if ($toCompany->address)
                <p> <b>Address:</b> {{ $toCompany->address }} </p>
                @endif
                @if ($toCompany->fax)
                <p><b>FAX:</b> {{ $toCompany->fax }} </p>
                @endif
            </div>
        </div>

          <div class="row">
            <div class="col-3">
                <div class="pr-1">
                    <h2>From Location:</h2>
                    <p>
                        <b>{{ $fromLocation->name }}</b><br>
                        {{ $fromLocation->address_line_1 }},
                        {{ $fromLocation->address_line_2 }}<br>
                        {{ $fromLocation->city ? $fromLocation->city->name : '' }}<br>
                        <b>Tel:</b> {{ $fromLocation->phone }}<br>
                        <b>FAX:</b> {{ $fromLocation->fax }}
                    </p>
                </div>
            </div>

            <div class="col-3">
                <h2>To Location:</h2>
                <p>
                    <b>{{ $toLocation->name }}</b><br>
                    <b>({{ $toCompany->name}})</b><br>
                    {{ $toLocation->address_line_1 }},
                    {{ $toLocation->address_line_2 }} <br>
                    {{ $toLocation->city ?? '' }} <br>
                    <b>Tel:</b> {{ $toLocation->phone }} <br>
                    <b>FAX:</b> {{ $toLocation->fax }} <br>
                </p>
            </div>

            <div class="col-3 mt-2-5">
                <p><b>Invoice No:</b>  {{$purchaseOrderInvoice->invoice_number}}</p>
                <p><b>Date:</b>  {{$purchaseOrderInvoice->created_at}}</p>
                <p><b>SO No.:</b>  {{$orderNumber}}</p>
                <p><b>DO No.:</b>  {{$deliveryNumber}}</p>
            </div>
        </div>

        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th class="text-left">No</th>
                    <th class="text-left">Upc</th>
                   @if(! $productVariant)
                        <th class="text-left">Color</th>
                    @endif
                    @if(! $productVariant)
                        <th class="text-left">Size</th>
                    @endif
                    @if($productVariant)
                        <th class="text-left">Attributes</th>
                    @endif
                    <th class="text-left">Article Number</th>
                    <th class="text-left">Description</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Purchase Cost( {{ $currencySymbol }})</th>
                    <th class="text-right">Amount({{ $currencySymbol }})</th></th>
                </tr>
            </thead>

            <tbody>
                @foreach($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem)
                <tr class="{{ $loop->index !== 0 ? 'page-break-inside-avoid' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $purchaseOrderFulfillmentItem['upc'] }}</td>
                    @if(! $productVariant)
                        <td>{{ $purchaseOrderFulfillmentItem['color'] }}</td>
                    @endif
                    @if(! $productVariant)
                        <td>{{ $purchaseOrderFulfillmentItem['size'] }}</td>
                    @endif
                    @if($productVariant)
                        <td>
                            @foreach($purchaseOrderFulfillmentItem['attributes'] as $key => $attribute)
                                {{ $key }} : {{ $attribute }}<br>
                            @endforeach
                        </td>
                    @endif
                    <td>
                        {{ $purchaseOrderFulfillmentItem['article_number'] }}
                    </td>
                    <td>{{ $purchaseOrderFulfillmentItem['name'] }}</td>
                    <td class="text-right">{{ $purchaseOrderFulfillmentItem['quantity'] }}</td>
                    <td class="text-right">{{ $purchaseOrderFulfillmentItem['purchase_cost'] }}</td>
                    <td class="text-right">{{ $purchaseOrderFulfillmentItem['amount'] }}</td>
                </tr>
                @endforeach

                <tr>
                    <th colspan="6" class="text-right"> Grand Total </th>
                    <th class="text-right" colspan="6"> {{ $total_amount }} </th>
                </tr>
            </tbody>
        </table>

        <div class="page-break-inside-avoid">
            <div class="row">
                <div class="col-6 mb-2">
                    <p><b>For:</b> {{ $fromCompany->name }} ({{ $fromCompany->social_security_number }})</p>
                    <div class="mt-10">
                         <span class="border-top">Authorised Signatory:</span>
                    </div>
                </div>
                <div class="col-6 mb-2">
                    <p>Received By:</p>
                    <div class="mt-10">
                         <span class="border-top">Stamp and Signature:</span>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
