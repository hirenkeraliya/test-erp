<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Digital Invoice</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report center">
    <div>
        <div class="col-12">
            <h4 class="pl-5">
              Digital Invoice
            </h4>
        </div>
    </div>

    <div class="row">
        <div class="col-12 date-display">
            <p>
                <b>Date:</b> {{ $date }}
            </p>

            <p>
                <b>Buyer Name:</b> {{ $digitalInvoice->buyer_name }}
            </p>

            <p>
               <b>Buyer Email:</b> {{ $digitalInvoice->buyer_email }}
            </p>

            <p>
                <b>Buyer Address:</b> {{ $digitalInvoice->buyer_address }}
            </p>

            <p>
               <b>Buyer Contact:</b> {{ $digitalInvoice->buyer_contact }}
            </p>

            <p>
                <b>Receipt Number:</b> {{ $digitalInvoice->receipt_number }}
            </p>

            <p>
                <b>Sequence Number:</b> {{ $digitalInvoice->digital_invoice_number }}
            </p>

            <p>
                <b>Buyer TIN:</b> {{ $digitalInvoice->buyer_tin }}
            </p>

            <p>
                <b>Buyer Identification Number:</b> {{ $digitalInvoice->buyer_identification_number }}
            </p>

            <p>
                <b>Buyer SST Number:</b> {{ $digitalInvoice->buyer_sst_number }}
            </p>
        </div>
    </div>
</body>

</html>
