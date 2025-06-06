<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('/css/print-invoice.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.3/echarts.min.js"></script>
    <script type="text/javascript" src="{{ asset('build/js/chartHelper.js') }}"></script>
    <title>Sell Through Report</title>

</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <h4>
        <strong>Sell Through Report {{ $reportType }}</strong>
    </h4>

    <p>
        @if (is_array($filterDate))
        Sell Through from {{ $filterDate[0] }} to {{ $filterDate[1] }}
        @else
        Sell Through Date : {{ $filterDate }}
        @endif
    </p>

    <p>
        Date: {{ $date }}
    </p>

    <x-filter-label-header :getFilterLabels="$getFilterLabels" />

    @if (isset($reportTypeByArticleNumber))
    <div style="width: 100%">
        <div style="display: flex; width: 100%">
            @php
            $productsCount = 0;
            @endphp
            @foreach ($sellThroughDataByProducts as $sellThroughDataByProduct)
            @if ($productsCount !== 0 && $productsCount % 3 === 0)
        </div>
        <div style="display: flex; width: 100%">
            @endif
            @if ($productsCount >= 6)
            @break
            @endif
            @if (array_key_exists('Image', $columns) && $sellThroughDataByProduct->product->getDiskBasedFirstMediaUrl('thumbnail'))
            <div style="width: 33.33%; text-align: center;">
                <p>{{ $sellThroughDataByProduct['name'] }}</p>
                <p>{{ $sellThroughDataByProduct['price'] }}</p>
                <p>{{ $sellThroughDataByProduct['article_number'] }}</p>
                <p><img src="{{ $sellThroughDataByProduct->product->getDiskBasedFirstMediaUrl('thumbnail') }}" alt="{{ $sellThroughDataByProduct['name'] }}" width="100px" height="100px" /></p>
            </div>
            @php
            $productsCount++;
            @endphp
            @endif
            @endforeach
        </div>
    </div>
    @endif

    <table class="table table-bordered bordered">
        <thead>
            <tr>
                @foreach ($columns as $column)
                <th class="{{ $column['bodyClass'] }} capitalize">
                  {{ $column['label'] }}
                </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            <h3>
                @if ($locations)
                {{ $locations->getNamesWithCodes }}
                @else
                All Stores
                @endif
            </h3>
            @forelse($sellThroughDataByProducts as $k => $sellThroughDataByProduct)
                <tr>
                    @foreach ($columns as $column)
                        @if ($column['key'] === 'image')
                            <td>
                                <img src="{{ $sellThroughDataByProduct['image'] }}"
                                    alt="{{ $sellThroughDataByProduct['name'] }}" width="100px"
                                    height="100px" />
                            </td>
                        @elseif ($column['key'] === 'color' && $sellThroughDataByProduct[$column['key']] && !config('app.product_variant'))
                            <td class="text-left">
                                {{ $sellThroughDataByProduct[$column['key']] }}
                            </td>
                        @elseif ($column['key'] === 'size' && $sellThroughDataByProduct[$column['key']] && !config('app.product_variant'))
                            <td class="text-left">
                                {{ $sellThroughDataByProduct[$column['key']] }}
                            </td>
                        @elseif ($column['key'] === 'attributes' && config('app.product_variant'))
                            <td class="text-left">
                                {{ implode(', ', array_map(fn($attr) => "{$attr['name']}: {$attr['value']}", $sellThroughDataByProduct['attributes'] ?? [])) }}
                            </td>
                        @else
                            <td class="{{$column['bodyClass']}}">
                                {{ $sellThroughDataByProduct[$column['key']] }}
                            </td>
                        @endif
                    @endforeach
                </tr>
            @empty
            <tr>
                <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
            </tr>
            @endforelse

            <tr>
                @if ($colSpan > 0)
                    <td colspan="{{ $colSpan }}">
                        <b>
                            Grand Total
                        </b>
                    </td>
                @endif
                @foreach (array_slice($columns, $colSpan) as $column)
                    <td class="{{ $column['bodyClass'] }}">
                        <b>
                            {{ $sellThroughTotalDataByProducts[$column['key']] }}
                        </b>
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    @if (count($chartRecords) > 0)
    <div class="page-break-inside-avoid">
        <h2> Bar Chart </h2>
        <div id="bar-chart" style="width: 100%; height:400px;"></div>
    </div>
    @endif

    <script type="text/javascript">
        var barChartElement = echarts.init(document.getElementById('bar-chart'));

        var chartLabel = {!!isset($chartRecords['labels']) ? json_encode($chartRecords['labels']) : '[]'!!};
        var chartData = {!!isset($chartRecords['sell_through']) ?
            json_encode($chartRecords['sell_through']) :
                '[]'!!};

        chartLabel = chartLabel.length > 0 ? chartLabel : ['No Records'];
        chartData = chartData.length > 0 ? chartData : [10];

        const preparedRecords = [];
        for (const key in chartLabel) {
            preparedRecords.push({
                name: chartLabel[key],
                value: chartData[key]
            });
        }

        barChartElement.setOption({
            legend: {
                data: 'Sell Through (%)'
            },
            xAxis: {
                data: chartLabel,
                axisLabel: {
                    formatter: function(value) {
                        return labelsInTruncateForm(value)
                    },
                },
            },
            yAxis: {
                type: 'value',
                min: 0
            },
            rotate: {
                min: -90,
                max: 90
            },
            series: [{
                type: 'bar',
                data: chartData,
                label: {
                    position: 'insideBottom',
                    distance: 15,
                    align: 'left',
                    verticalAlign: 'middle',
                    show: true,
                    fontSize: 16,
                    fontWeight: 'bolder',
                    rotate: 90,
                    formatter: function(value) {
                        return formatLabelForChartWithPercentage(value.value)
                    }
                },
                itemStyle: {
                    borderRadius: [4, 4, 0, 0],
                },
                color: getPastelColors()
            }, ]
        });
    </script>
</body>

</html>
