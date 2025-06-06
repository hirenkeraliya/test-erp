<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Layaway Sale Report</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <div class="row">
        <div class="col-7">
            <h4>
                {{ $company->name }} ( {{ $company->code }} )
            </h4>
        </div>

        <div class="col-5">
            <h4 class="pl-5">
                Layaway Sale Order Form
            </h4>
        </div>
    </div>

    <div class="row">
        <div class="col-6 date-display">
            <p>
                Date: {{ $date }}
            </p>

            <p>
                Location: {{ $location->name }} ( {{ $location->code }} )
            </p>
        </div>

        <div class="col-6">
            <p>
                Layaway Sale Id: {{ $salesDetails['receipt_number'] }}
            </p>

            <p>
                Tax Invoice No:
            </p>
        </div>
    </div>

    <div>
        <h4>
            Items
        </h4>
        <table class="table">
            <thead>
                <tr>
                    <th> Id </th>
                    <th> Product </th>
                    @if ($productVariant)
                        <th> Attributes </th>
                    @else
                        <th> Color </th>
                        <th> Size </th>
                    @endif
                    <th> Upc </th>
                    <th> Quantity. </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($salesDetails['sale_items'] as $item)
                    <tr>
                        <td> {{ $item['id'] }} </td>
                        <td> {{ $item['product'] }} </td>
                        @if ($productVariant)
                            <td> {{ $item['attributes'] }} </td>
                        @else
                            <td> {{ $item['color'] }} </td>
                            <td> {{ $item['size'] }} </td>
                        @endif
                        <td> {{ $item['upc'] }} </td>
                        <td> {{ $item['quantity'] }} </td>
                    </tr>
                @endforeach
            </tbody>
        </table>


        <div class="mb-8">
            <p>
                Member Name: {{ $salesDetails['user_details']['name'] }}
            </p>
            <p>
                Address: {{ $salesDetails['user_details']['address_line_1'] }}
                {{ $salesDetails['user_details']['address_line_2'] }}
                {{ $salesDetails['user_details']['city'] }}
                {{ $salesDetails['user_details']['area_code'] }}
            </p>
        </div>

        <div class="row">
            <div class="col-6 text-center">
                <div><p class="border-t">Member Signature</p></div>
                <div><p class="border-t">Member Name</p></div>
            </div>
            <div class="col-6 text-center">
                <div><p class="border-t">Signature</p></div>
                <div><p class="border-t">Name</p></div>
            </div>
        </div>
    </div>
</body>

</html>
