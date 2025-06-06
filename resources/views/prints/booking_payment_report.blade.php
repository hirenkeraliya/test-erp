<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Payment Report</title>

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
                Booking Payment
            </h4>
        </div>
    </div>

    <div class="date-display">

        <p>
            Date: {{ $date }}
        </p>

        <p>
            Location: {{ $location->name }} ( {{ $location->code }} )
        </p>
    </div>

    <div>
        <h4>
            Items
        </h4>
        <table class="table">
            <thead>
                <tr>
                    <th> Product </th>
                    @if(config('app.product_variant'))
                        <th> Attributes </th>
                    @else
                        <th> Color </th>
                        <th> Size </th>
                    @endif
                    <th> Upc </th>
                    <th> Quantities </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($bookingPaymentDetails['products'] as $item)
                    <tr>
                        <td> {{ $item['product'] }} </td>
                        @if(config('app.product_variant'))
                            <td> 
                                @foreach ( $item['attributes'] as $attribute )
                                    {{ $attribute['name'] }}: {{ $attribute['value'] }}<br>
                                @endforeach
                            </td>
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

        <h4>
            Payment Modes
        </h4>

        <table class="table">
            <thead>
                <tr>
                    <th> Payment Type </th>
                    <th> Amount </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($bookingPaymentDetails['payments'] as $payment)
                    <tr>
                        <td> {{ $payment['payment_type'] }} </td>
                        <td> {{ $payment['amount'] }} </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mb-8">
            <p>
                Member Name: {{ $bookingPaymentDetails['user_details']['name'] }}
            </p>
            <p>
                Address: {{ $bookingPaymentDetails['user_details']['address_line_1'] }}
                {{ $bookingPaymentDetails['user_details']['address_line_2'] }}
                {{ $bookingPaymentDetails['user_details']['city'] }}
                {{ $bookingPaymentDetails['user_details']['area_code'] }}
            </p>
            <p>
                # Reference: {{ $bookingPaymentDetails['bill_reference_number'] }}
            </p>
            <p>
                Remarks: {{ $bookingPaymentDetails['remarks'] }}
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
