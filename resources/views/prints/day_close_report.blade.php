<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Day Close</title>
    <style>
        body {
            font-family: "Inter", sans-serif;
            margin: 0 0 30px 0;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        span,
        ul,
        li,
        a {
            margin: 0;
            padding: 0;
        }

        .table-header {
            padding-top: 6px;
            padding-bottom: 6px;
            padding-left: 10px;
            padding-right: 10px;
            font-size: 16px;
            font-weight: 600;
            color: #000000;
        }

        .table-sub-header {
            background-color: #ebebeb;
            padding-top: 6px;
            padding-bottom: 6px;
            padding-left: 10px;
            padding-right: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #000000;
        }

        .bg-table-header {
            background-color: #E0E0E0;
        }

        table {
            border-spacing: 30px;
            width: 98%;
            background-color: #F9F9F9;
        }

        table tbody tr td {
            font-size: 14px;
            font-weight: 500;
            color: #000000;
            padding: 3px 5px;
        }

        table tbody tr td.table-text-data {
            font-size: 14px;
            font-weight: 400;
            color: #222222;
            text-align: right;
        }

        table tbody tr {
            border-bottom: 1px solid #C9C9C9;
        }

        table tbody tr:last-child {
            border-bottom: 0px;
        }

        .table-auto {
            border: 1px solid black;
        }

        .bottom-border {
            text-align: center;
        }

        .date-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
        }

        .day-close-data {
            font-size: 16px;
            font-weight: 400;
            color: #000000;
        }

        .day-close-open-data {
            font-size: 14px;
            font-weight: 500;
            color: #000000;
        }

        .store-data {
            font-size: 16px;
            font-weight: 600;
            color: #000000;
        }

        .title {
            font-size: 16px;
            font-weight: 600;
            color: #000000;
        }

        .hr-line {
            background-color: #E0E0E0 !important;
            height: 1px;
            width: 100%;
            display: block;
            border: 0px;
        }
    </style>
</head>

<body class="arial-font">
    <div class="date-display">
        <div>
            <p class="store-data mb-1"><span class="store-data">Location:</span> {{ $dayClose['location'] }}</p>
            <p class="day-close-data">
                <span class="day-close-data">Day Close by:</span>
                {{ $dayClose['store_manager'] }}
            </p>
        </div>

        <div>
            <p class="title text-center">Day Close (EOD)</p>
        </div>

        <div>
            <p class="day-close-open-data mb-1">
                <span class="day-close-open-data">Opened At:</span>
                {{ $dayClose['opened_at'] }}
            </p>

            <p class="day-close-open-data">
                <span class="day-close-open-data">Closed At:</span>
                {{ $dayClose['closed_at'] }}
            </p>
        </div>
    </div>

    <hr class="hr-line" />

    <div class="row mt-3">
        <div class="col-6">
            <table>
                <thead>
                    <tr>
                        <th class="bg-table-header table-header text-center" colspan="2">Sales</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>
                            Collection
                        </td>

                        <td class="table-text-data text-right">
                            @currencyFormat($dayClose['sales_collection_amount'])
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sales
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_sales'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sale Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_sales_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Layaway
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_layaway_sales'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Layaway Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_layaway_sales_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Cancel Layaway
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_cancel_layaway_sales'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Cancel Layaway Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_cancel_layaway_sales_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Credit Sales
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_credit_sales'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Credit Sales Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_credit_sales_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Void
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_voided_sales'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Void Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_voided_sales_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Item Wise Discount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_item_wise_discount_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Cart Wide Discount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_cart_wide_discount_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Tax
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_tax_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sales Round Off
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_sales_round_off'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sale Returns
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_sale_returns'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sale Returns Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_sale_returns_amount'] }}
                        </td>
                    </tr>
                    <tr>

                        <td>
                            Credit Notes Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_credit_notes_used_amount'] }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Credit Notes Accepted
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_credit_notes_used'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Credit Notes Refunded Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_credit_notes_refunded_amount'] }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Credit Notes Refunded
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_credit_notes_refunded'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sale Returns Round Off
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_sale_returns_round_off'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Cashbacks
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_cashback'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Cashback Amount
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_cashback_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Vouchers Used
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_vouchers_used'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Vouchers Generated
                        </td>

                        <td class="table-text-data">
                            {{ $dayClose['total_vouchers_generated'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Booking Payment
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_booking_payment_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Booking Payment Refunded
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_booking_payment_refunded_amount'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Booking Payment Used
                        </td>

                        <td class="table-text-data">
                            {{ $currencySymbol }}{{ $dayClose['total_booking_payment_used_amount'] }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="col-6">
            <table class="mb-5 float-right">
                <thead>
                    <tr>
                        <th class="bg-table-header table-header text-center" colspan="3">Payment</th>
                    </tr>
                    <tr>
                        <th class="table-sub-header text-left">Payment</th>
                        <th class="table-sub-header text-center">Transactions</th>
                        <th class="table-sub-header text-right">Amount</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($dayClose['payments'] as $dayClosePayment)
                    <tr>
                        <td>{{ $dayClosePayment['payment_type'] }}</td>
                        <td class="table-text-data text-center">{{ $dayClosePayment['total_transactions'] }}</td>
                        <td class="table-text-data"> {{ $currencySymbol }}{{ $dayClosePayment['total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="mt-5 float-right">
                <thead>
                    <tr>
                        <th class="bg-table-header table-header text-center" colspan="2">Cash Transaction</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td> Opening Balance </td>
                        <td class="table-text-data"> {{ $currencySymbol }}{{ $dayClose['opening_balance'] }}</td>
                    </tr>
                    <tr>
                        <td> Sales (Cash) </td>
                        <td class="table-text-data"> {{ $currencySymbol }}{{ $dayClose['total_cash_amount_in_sales'] }}</td>
                    </tr>
                    <tr>
                        <td>Booking Payments (Cash) </td>
                        <td class="table-text-data"> {{ $currencySymbol }}{{ $dayClose['total_cash_amount_in_booking_payment'] }}</td>
                    </tr>
                    <tr>
                        <td>Booking Payment Refunds (Cash) </td>
                        <td class="table-text-data"> {{ $currencySymbol }}{{ $dayClose['total_cash_amount_in_booking_payment_refunded'] }}</td>
                    </tr>
                    <tr>
                        <td>Cash Ins </td>
                        <td class="table-text-data"> {{ $currencySymbol }}{{ $dayClose['total_cash_ins_amount'] }}</td>
                    </tr>
                    <tr>
                        <td>Cash Outs </td>
                        <td class="table-text-data"> {{ $currencySymbol }}{{ $dayClose['total_cash_outs_amount'] }}</td>
                    </tr>
                    <tr>
                        <td>Credit Note Refunds (Cash) </td>
                        <td class="table-text-data"> {{ $currencySymbol }}{{ $dayClose['total_cash_amount_in_credit_note_refunded'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>