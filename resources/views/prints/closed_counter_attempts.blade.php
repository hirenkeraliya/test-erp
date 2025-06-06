<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Closed Counter Attempt Report </title>

    <style>
        tr, td, th {
            padding: 2px;
        }

        table {
            padding: 5px
        }

        .color-red {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body class="arial-font-custom-report">
    <h2 class="text-center">
        {{ $location->name }} ({{ $location->code }})
    </h2>

    <h3 class="text-center">
        {{ $counter }}
    </h3>

    <p>
        Date: {{ $date }}
    </p>

    @foreach($closedCounterAttempts as $closedCounterAttempt)
        <h3>
            Attempt Date: {{ $closedCounterAttempt['happened_at'] }}
        </h3>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th> Payment </th>
                    <th> Declared </th>
                    <th> Calculated </th>
                    <th class="text-center"> Denominations </th>
                </tr>
            </thead>

            <tbody>
                @php
                    $totalDeclaredAmount = 0;
                    $totalCalculatedAmount = 0;
                    $totalQuantity = 0;
                    $totalDenominationAmount = 0;
                @endphp
                @foreach ($closedCounterAttempt['counter_update_declaration_attempt_payments'] as $counterUpdateDeclarationAttemptPayments)
                    <tr class="page-break-inside-avoid">
                        <td>
                            {{ $counterUpdateDeclarationAttemptPayments['payment_type'] }}
                        </td>
                        <td>
                            {{ $currencySymbol }}{{ $counterUpdateDeclarationAttemptPayments['declared_amount'] }}
                            @php
                                $totalDeclaredAmount += $counterUpdateDeclarationAttemptPayments['declared_amount']
                            @endphp
                        </td>
                        <td>
                            {{ $currencySymbol }}{{ $counterUpdateDeclarationAttemptPayments['calculated_amount'] }}
                            @php
                                $totalCalculatedAmount += $counterUpdateDeclarationAttemptPayments['calculated_amount']
                            @endphp
                        </td>
                        <td>
                            @if ($counterUpdateDeclarationAttemptPayments['denominations'] !== null)
                                <div>
                                    <table width='100%'>
                                        <thead>
                                            <tr>
                                                <th> Denominations </th>
                                                <th> Quantity </th>
                                                <th> Total </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($counterUpdateDeclarationAttemptPayments['denominations'] as $denomination)
                                                <tr class="page-break-inside-avoid">
                                                    <td> {{ $denomination->denomination }} </td>
                                                    <td>
                                                        {{ $denomination->quantity }}
                                                        @php
                                                            $totalQuantity += $denomination->quantity;
                                                        @endphp
                                                    </td>
                                                    <td>
                                                        {{ $currencySymbol }}{{ $denomination->denomination * $denomination->quantity }}
                                                        @php
                                                            $totalDenominationAmount += $denomination->denomination * $denomination->quantity
                                                        @endphp
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td class="color-red"> Total: </td>
                                                <td class="color-red"> {{ $totalQuantity }} </td>
                                                <td class="color-red"> {{ $currencySymbol }}{{ $totalDenominationAmount }} </td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center">
                                    No Denomination Required.
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td class="color-red">Amount</td>
                    <td class="color-red"> {{ $currencySymbol }}{{ $totalDeclaredAmount }} </td>
                    <td class="color-red"> {{ $currencySymbol }}{{ $totalCalculatedAmount }} </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endforeach
</body>
</html>
