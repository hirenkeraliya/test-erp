<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Purchase Order</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <div class="row">
        <div class="col-12 text-center">
            <h4>
                {{ $company->name }} ( {{ $company->code }} )
            </h4>

            <h6>
                {{ $company->address }}
            </h6>
        </div>
    </div>

    <div class="row border-t-4">
        <h3 class="col-12 text-center font-bold">
            Purchase Order
        </h3>
    </div>

    <div class="row">
        <div class="col-6">
            <p class="font-bold">
                Bill To: <br />
                {{ $ordersDetails['member_details']['name'] }} <br />
                {{ $ordersDetails['member_details']['address_line_1'] }} <br />
                {{ $ordersDetails['member_details']['address_line_2'] }}
            </p>
        </div>

        <div class="col-6 font-bold">
            <p> PO NO: {{ $ordersDetails['receipt_number'] }} </p>
            <p> Date: {{ $ordersDetails['date'] }} </p>
        </div>
    </div>

    <div class="text-center">
        <table class="table">
            <thead>
                <tr>
                    <th> Upc </th>
                    <th> Description </th>
                    <th> Quantity </th>
                    <th> Remark </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($ordersDetails['order_items'] as $item)
                    <tr>
                        <td> {{ $item['upc'] }} </td>
                        <td> {{ $item['product'] }} </td>
                        <td class="text-center"> @truncateDecimal($item['quantity']) </td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <th colspan="2">Quantity</th>
                    <th class="text-center">{{ $ordersDetails['total_quantity'] }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <div class="row mt-16">
            <div class="">
                <p class="border-t-4">
                    Manager Signature
                </p>
            </div>
        </div>
    </div>
</body>

</html>
