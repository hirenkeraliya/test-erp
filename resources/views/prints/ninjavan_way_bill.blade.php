<!DOCTYPE html>
<html lang="en">

<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/ninja-van-way-bill.css') }}">
    <title>Ninja-van Waybill</title>

    <style>
        table {
            font-size: 12px;
        }

        td {
            display: table-cell;
            margin: 10px 15px;
            padding: 10px;
        }

        .no-border {
            border: 0px !important;
        }
    </style>
</head>

<body class="arial-font arial-font-custom-report a6">

    @foreach($ordersDetails as $orderDetail)
    <div style="height:700px;" class="page-break">
        <div class=" row mb-5">
            <div class="col-12 border">
                <div class="row">
                    <div class="col-4">
                        <p>
                            <b class="header-text">Ninja Van</b>
                        </p>
                    </div>

                    <div class="col-8">
                        <p class="label-border">For Ninja Van Use : </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-8">
                <div class="pr-1">
                    <p>
                        <b style="font-size: 15px;">{{ $orderDetail['tracking_number'] }}</b><br>
                    <p>
                        <img src="data:image/png;base64,{{ $orderDetail['tracking_barcode'] }}" width="240" height="50">
                    </p>
                    <p>Order Ref.: {{ $orderDetail['order_ref'] }}</p>
                    </p>
                </div>
            </div>

            <div class="col-4">
                <img src="data:image/png;base64,{{ $orderDetail['qr_code'] }}" width="100" height="100">
            </div>
        </div>

        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th><b>FROM (SENDER)</b></th>
                    <th><b>TO (ADDRESSEE)</b></th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td width="100">
                        <p class="mb-5">{{ $orderDetail['location_name'] }} </p>
                        <p class="mb-5">{{ $orderDetail['location_phone'] }}</p>
                        <p style="line-height: 1.3;">{{ $orderDetail['from_address'] }}</p>
                    </td>
                    <td width="100">
                        <p class="mb-5">{{ $orderDetail['member_name'] }} </p>
                        <p class="mb-5">{{ $orderDetail['member_phone'] }}</p>
                        <p style="line-height: 1.3;">{{ $orderDetail['to_address'] }}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <b>COD: -</b>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <b>Comments:</b>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
</body>

</html>