<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<style>
    table {
        border-collapse: collapse;
    }

    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: black;
    }

    .table-bordered {
        border: 1px solid black;
    }

    .table td,
    .table th {
        vertical-align: top;
        border-top: 1px solid black;
    }

    .table .thead-dark th {
        color: #fff;
        background-color: black;
        border-color: #6c6e7e;
    }

    th {
        text-align: inherit;
    }

    tr {
        display: table-row;
        vertical-align: inherit;
        border-color: inherit;
    }

    .table thead th {
        vertical-align: bottom;
    }

    .table-bordered thead td,
    .table-bordered thead th {
        border-bottom-width: 2px;
    }

    .align-self-end {
        align-self: flex-end !important;
    }

    .text-right {
        text-align: right;
    }

    .text-left {
        text-align: left;
    }

    .float-right {
        float: right;
    }

    .text-center {
        text-align: center !important;
    }

    .text-bold {
        font-weight: bold;
    }

    .arial-font {
        font-family: Arial;
        font-size: 16px;
    }

    .arial-font-custom-report {
        font-family: Arial;
        font-size: 14px;
    }

    .border-1 {
        border: 1px solid black;
    }

    .bordered td,
    .bordered th {
        border: 1px solid black;
    }
</style>

<body class="arial-font-custom-report">
    <p>
        Date: <strong>{{ $preparedData['date'] }}</strong>
    </p>
    <p><strong> Location Wise Sales </strong></p>
    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    @foreach($preparedData['locationColumns'] as $key=>$column)
                        <th class="vertical-align { $key === 0 ? 'text-left' : 'text-center' }}">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @isset($preparedData['locationAndBrandWiseGroup']['locations'])
                    @foreach ($preparedData['locationAndBrandWiseGroup']['locations'] as $key => $location)
                        <tr class="page-break-inside-avoid">
                            <td>{{ $location['location_name'] }}</td>
                            <td class="text-center">{{ $location['total_sales_count'] }}</td>
                            <td class="text-center">{{ $location['total_units_sold'] }}</td>
                            <td class="text-right">{{ $preparedData['currency_symbol'].$location['total_sales_amount'] }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="text-center font-bold"> No Records Found</td>
                    </tr>
                @endisset
            </tbody>
        </table>
    </div>

    <p><strong> Brand Wise Sales </strong></p>
    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    @foreach($preparedData['brandColumns'] as $key => $column)
                        <th class="vertical-align {{ $key === 0 ? 'text-left' : 'text-center' }}">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @isset($preparedData['locationAndBrandWiseGroup']['brands'])
                    @foreach ($preparedData['locationAndBrandWiseGroup']['brands'] as $key => $brand)
                        <tr>
                            <td>{{ $brand['brand_name'] }}</td>
                            <td class="text-center">{{ $brand['total_sales_count'] }}</td>
                            <td class="text-center">{{ $brand['total_units_sold'] }}</td>
                            <td class="text-right">{{ $preparedData['currency_symbol'].$brand['total_sales_amount'] }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td  colspan="4" class="text-center font-bold"> No Records Found</td>
                    </tr>
                @endisset
            </tbody>
        </table>
    </div>
</body>

</html>
