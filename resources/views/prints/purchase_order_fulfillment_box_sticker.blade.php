<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/purchase-invoice-print.css') }}" />
    <title> Box Sticker</title>

</head>

<body class="arial-font arial-font-custom-report">
    <div class="row m-0">
        <div class="col-6 border">
            <div class="row">
                <div class="col-4">
                    <img alt="logo" class="img-fluid rounded" src="{{ $fromCompany->getDiskBasedFirstMediaUrl('dark_logo') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="divide"></div>
        <div class="row">
            <div class="col-3">
                <h2>To:</h2>
                <p>
                    <b>{{ $toLocation['name'] }}</b><br>
                    {{ $toLocation['address_line_1'] }},
                    {{ $toLocation['address_line_2'] }} <br>
                    {{ $toLocation['city'] }} <br>
                    <b>Tel:</b> {{$toLocation['phone'] }} <br>
                    <b>FAX:</b> {{ $toLocation['fax'] }} <br>
                </p>
            </div>
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
        </div>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">Name</th>
                <th class="text-center">Package Type</th>
                <th class="text-center">Package Type Quantity</th>
                <th class="text-center">Quantity per Package Type</th>
                <th class="text-center">Quantity</th>
            </tr>
        </thead>

        <tbody>
            @foreach($purchaseOrderFulfillmentItems as $purchaseOrderFulfillmentItem)
                <tr class="{{ $loop->index !== 0 ? 'page-break-inside-avoid' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $purchaseOrderFulfillmentItem['name'] }}
                    </td>
                    <td>{{ $purchaseOrderFulfillmentItem['package_type'] }}</td>
                    <td class="text-center">@truncateDecimal((float) $purchaseOrderFulfillmentItem['package_quantity'])</td>
                    <td class="text-center">@truncateDecimal((float) $purchaseOrderFulfillmentItem['package_total_quantity'])</td>
                    <td class="text-center">@truncateDecimal((float) $purchaseOrderFulfillmentItem['transfer_quantity'])</td>
                </tr>
            @endforeach

            <tr class="page-break-inside-avoid">
                <td colspan="5" class="text-right"> <b>Quantity:</b></td>
                <td class="text-center"><b>@truncateDecimal((float) $totalTransferQty)</b></td>
            </tr>
        </tbody>
    </table>

    <div class="page-break-inside-avoid">
        <div class="row mt-3">
            <div class="col-6">
                <div class="row mt-3">
                    <div class="col-2 mb-2">
                        <b>Packed by:</b>
                    </div>

                    <div class="col-8">
                        <hr class="mt-3">
                    </div>
                </div>
                <div class="mt-3 mb-2">
                    <b>Transfer Date:</b>  {{$purchaseOrderFulfillment->happened_at ? date('d/m/Y', strtotime($purchaseOrderFulfillment->happened_at)) : 'N/A' }}
                </div>
                <div class="mt-3">
                    <b>Delivery Order No:</b>  {{ $purchaseOrderFulfillment->delivery_order_number }}<br>
                </div>
            </div>
            <div class="col-5">
                <div
                    class="transfer-feedback-box float-right mt-3"
                >
                    Checked by
                </div>
            </div>
        </div>
    </div>
</body>
</html>
