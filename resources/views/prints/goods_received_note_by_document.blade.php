<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Goods Received Note(ByDocument)</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Goods Received Notes Report" reportType="By Document" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @foreach ($goodsReceivedNotes as $goodsReceivedNote)
        <p> Location : <strong> {{ $goodsReceivedNote['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    <th class="text-center">Date</th>
                    <th class="text-center">Grn Ref</th>
                    <th class="text-center">Created By</th>
                    <th class="text-center item">Do Ref</th>
                    <th class="text-center">Po Ref</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Notes</th>
                </tr>
            </thead>

            <tbody>
                @if (count($goodsReceivedNote['goods_received_notes']) > 0)
                    @forelse($goodsReceivedNote['goods_received_notes'] as $goodsReceivedNoteDetails)
                        <tr class="page-break-inside-avoid">
                            <td class="{{ $goodsReceivedNoteDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteDetails['date'] }}
                            </td>
                            <td class="{{ $goodsReceivedNoteDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteDetails['grn_ref'] }}
                            </td>
                            <td class="mt-2 {{ $goodsReceivedNoteDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteDetails['created_by'] }}
                            </td>
                            <td class="{{ $goodsReceivedNoteDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteDetails['do_ref'] }}
                            </td>
                            <td class="mt-2 {{ $goodsReceivedNoteDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteDetails['po_ref'] }}
                            </td>
                            <td class="text-center mt-2 {{ $goodsReceivedNoteDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteDetails['total_quantity'] }}
                            </td>
                            <td class="mt-2 {{ $goodsReceivedNoteDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteDetails['notes'] }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="page-break-inside-avoid">
                        <td class="{{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['date'] }}
                        </td>
                        <td class="{{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['grn_ref'] }}
                        </td>
                        <td class="mt-2 {{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['created_by'] }}
                        </td>
                        <td class="{{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['do_ref'] }}
                        </td>
                        <td class="mt-2 {{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['po_ref'] }}
                        </td>
                        <td class="text-center mt-2 {{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['total_quantity'] }}
                        </td>
                        <td class="mt-2 {{ $goodsReceivedNote['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNote['notes'] }}
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="4" class="text-center">No Records</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
</body>
</html>
