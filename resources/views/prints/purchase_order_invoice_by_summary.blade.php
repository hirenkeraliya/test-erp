<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title> Purchase Order Invoice Report </title>
</head>

<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Purchase Order Invoice Report" reportType="By Summary"
        :filterBy="$filterBy" :dateRange="$dateRange" :date="$date" />

    <table class="table table-bordered">
        <thead>
            <tr>
                @foreach ($columns as $column)
                <th class="text-center">
                    {{ $column }}
                </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse($purchaseOrderInvoicesData as $purchaseOrderInvoiceData)
            <tr class="page-break-inside-avoid">
                <td class="{{ $purchaseOrderInvoiceData['date'] === 'Total' ? 'text-bold' : '' }}">
                    {{ $purchaseOrderInvoiceData['date'] }}
                </td>
                <td class="{{ $purchaseOrderInvoiceData['date'] === 'Total' ? 'text-bold' : '' }}">
                    {{ $purchaseOrderInvoiceData['invoice_number'] }}
                </td>
                <td class="{{ $purchaseOrderInvoiceData['date'] === 'Total' ? 'text-bold' : '' }}">
                    {{ $purchaseOrderInvoiceData['status'] }}
                </td>
                <td
                    class="{{ $purchaseOrderInvoiceData['date'] === 'Total' ? 'text-bold text-center' : 'text-center' }}">
                    {{ $purchaseOrderInvoiceData['quantity'] }}
                </td>
                <td
                    class="{{ $purchaseOrderInvoiceData['date'] === 'Total' ? 'text-bold text-right' : 'text-right' }}">
                    {{ $purchaseOrderInvoiceData['total_amount'] }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">No Records</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>