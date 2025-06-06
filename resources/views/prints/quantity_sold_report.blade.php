<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <title>Quantity Sold Report</title>
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Quantity Sold Report</strong>
    </h4>

    <x-pdf-report-header :filterData="$filter_header_data"/>

    <p>
        Records from {{ $dateRange[0] }} to {{ $dateRange[1] }}
    </p>

    <p>
        Date: {{ $date }}
    </p>

    <div style="display: flex">
        <div class="p-2">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th colspan="7">
                            @if($locationName !== null)
                                {{ $locationName }}
                            @else
                                {{ $regionName }}
                            @endif
                        </th>
                    </tr>
                </thead>

                <thead>
                    <tr>
                        <th> Name </th>
                        <th> Upc </th>
                        <th> Article Number </th>
                        @if(config('app.product_variant'))
                            <th> Attributes </th>
                        @else
                            <th> Color </th>
                            <th> Size </th>
                        @endif
                        <th> Qty Sold </th>
                        <th> Amount Sold </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($records as $record)
                        <tr>
                            <td> {{ $record['product'] }} </td>
                            <td> {{ $record['upc'] }} </td>
                            <td> {{ $record['article_number'] }} </td>
                            @if(config('app.product_variant'))
                                <td> {{ $record['product_variant_values'] }} </td>
                            @else
                                <td> {{ $record['color'] }} </td>
                                <td> {{ $record['size'] }} </td>
                            @endif                            
                            <td> {{ $record['qty_sold'] }} </td>
                            <td> {{ $record['amount_sold'] }} </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-2">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th colspan="7">
                            @if($compareLocationName !== null)
                                {{ $compareLocationName }}
                            @else
                                {{ $compareRegionName }}
                            @endif
                        </th>
                    </tr>
                </thead>

                <thead>
                    <tr>
                        <th> Name </th>
                        <th> Upc </th>
                        <th> Article Number </th>
                         @if(config('app.product_variant'))
                            <th> Attributes </th>
                        @else
                            <th> Color </th>
                            <th> Size </th>
                        @endif
                        <th> Qty Sold </th>
                        <th> Amount Sold </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($comparedRecords as $record)
                        <tr>
                            <td> {{ $record['product'] }} </td>
                            <td> {{ $record['upc'] }} </td>
                            <td> {{ $record['article_number'] }} </td>
                            @if(config('app.product_variant'))
                                <td> {{ $record['product_variant_values'] }} </td>
                            @else
                                <td> {{ $record['color'] }} </td>
                                <td> {{ $record['size'] }} </td>
                            @endif    
                            <td> {{ $record['compare_qty_sold'] }} </td>
                            <td> {{ $record['compare_sold_amount'] }} </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
