<!DOCTYPE html>
<html lang="en">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Goods Received Note(BySummary)</title>
</head>
<body class="arial-font-custom-report">
    <x-report-header :company="$company" reportName="Goods Received Notes Report" reportType="By Summary" :filterBy="$filterBy" :dateRange="$dateRange" :date="$date"  />

    @foreach ($goodsReceivedNoteProducts as $goodsReceivedNoteProduct)
        <p> Location : <strong> {{ $goodsReceivedNoteProduct['location_name'] }} </strong> </p>

        <table class="table table-bordered">
            <thead >
                <tr>
                    @foreach($columns as $column)
                        <th class="text-center">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @if (count($goodsReceivedNoteProduct['goods_received_notes']) > 0)
                    @forelse($goodsReceivedNoteProduct['goods_received_notes'] as $goodsReceivedNoteProductDetails)
                        <tr class="page-break-inside-avoid">
                            <td style="width:70px"
                                class="{{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}"
                            >
                                {{ $goodsReceivedNoteProductDetails['date'] }}
                            </td>
                            <td class="{{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteProductDetails['upc'] }}
                            </td>
                            <td class="{{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteProductDetails['article_number'] }}
                            </td>
                            <td
                            class="mt-2 {{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}"
                            >
                                {{ $goodsReceivedNoteProductDetails['name'] }}
                            </td>
                            @if(config('app.product_variant'))
                                <td
                                class="mt-2 {{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                     {{ $goodsReceivedNoteProductDetails['attributes'] }}
                                </td>
                            @else
                                <td
                                class="mt-2 {{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}"
                                >
                                    {{ $goodsReceivedNoteProductDetails['color'] }}
                                </td>
                                <td class="mt-2 {{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                    {{ $goodsReceivedNoteProductDetails['size'] }}
                                </td>
                            @endif
                            <td class="text-center mt-2 {{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteProductDetails['quantity'] }}
                            </td>
                            <td class="text-right mt-2 {{ $goodsReceivedNoteProductDetails['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteProductDetails['total_price'] }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="page-break-inside-avoid">
                        <td class="{{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNoteProduct['date'] }}
                        </td>
                        <td class="{{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNoteProduct['upc'] }}
                        </td>
                        <td class="{{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNoteProduct['article_number'] }}
                        </td>
                        <td class="mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNoteProduct['name'] }}
                        </td>
                        @if(config('app.product_variant'))
                            <td class="mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteProduct['attributes'] }}
                            </td>
                        @else
                            <td class="mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteProduct['color'] }}
                            </td>
                            <td class="mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">
                                {{ $goodsReceivedNoteProduct['size'] }}
                            </td>
                        @endif
                        <td
                            class="text-center mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}"
                        >
                            {{ $goodsReceivedNoteProduct['quantity'] }}
                        </td>
                        <td class="text-right mt-2 {{ $goodsReceivedNoteProduct['date'] === 'Total' ? 'text-bold' : ''}}">
                            {{ $goodsReceivedNoteProduct['total_price'] }}
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
