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
        Sell Through from {{ $dateRange[0] }} to {{ $dateRange[1] }}
    </p>

    <p>
        Date: {{ $date }}
    </p>

    <x-filter-label-header :getFilterLabels="$getFilterLabels" />

    @if (array_key_exists('Article Number', $columns))
        <div style="width: 100%">
            <div style="display: flex; width: 100%">
                @php
                    $productsCount = 0;
                @endphp
                @foreach ($saleThroughDataByProducts as $product)
                    @if ($productsCount !== 0 && $productsCount % 3 === 0)
                        </div><div style="display: flex; width: 100%">
                    @endif

                    @if ($productsCount >= 6)
                        @break
                    @endif
                    @if (array_key_exists('Image', $columns) && $product->getDiskBasedFirstMediaUrl('thumbnail'))
                        <div style="width: 33.33%; text-align: center;">
                            <p>{{ $product['name'] }}</p>
                            <p>{{ $product['price'] }}</p>
                            <p>{{ $product['article_number'] }}</p>
                            <p><img src="{{ $product->getDiskBasedFirstMediaUrl('thumbnail') }}" alt="{{ $product['name'] }}" width="100px" height="100px" /></p>
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
                @foreach ($columns as $column => $class)
                    <th class="{{ $class }}">{{ $column }}</th>
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
            @forelse($saleThroughDataByProducts as $k => $saleThroughDataByProduct)
                <tr class="page-break-inside-avoid">
                    <td>
                        {{ $saleThroughDataByProduct['name'] }}
                    </td>

                    @if (array_key_exists('Image', $columns))
                        <td>
                            <img src="{{ $saleThroughDataByProduct->getDiskBasedFirstMediaUrl('thumbnail') }}"
                                alt="{{ $saleThroughDataByProduct['name'] }}" width="100px" height="100px" />
                        </td>
                    @endif

                    @if (array_key_exists('Color', $columns))
                        <td>
                            {{ $saleThroughDataByProduct?->color->name ?? 'N/A' }}
                        </td>
                    @endif

                    @if (array_key_exists('Size', $columns))
                        <td>
                            {{ $saleThroughDataByProduct?->size->name ?? 'N/A' }}
                        </td>
                    @endif

                    <td class="text-left">
                        @if (array_key_exists('Article Number', $columns))
                            {{ $saleThroughDataByProduct['article_number'] }}
                        @elseif (array_key_exists('Upc', $columns))
                            {{ $saleThroughDataByProduct['upc'] }}
                        @endif
                    </td>

                    <td class="text-right">
                        {{ (float) $saleThroughDataByProduct['price'] }}
                    </td>

                    <td class="text-right">
                        {{ (float) $saleThroughDataByProduct['received'] }}
                    </td>

                    <td class="text-right">
                        {{ (float) $saleThroughDataByProduct['sold'] }}
                    </td>

                    <td class="text-right">
                        {{ (float) $saleThroughDataByProduct['returned'] }}
                    </td>

                    <td class="text-right">
                        {{ (float) $saleThroughDataByProduct['received'] - ((float) $saleThroughDataByProduct['sold'] - (float) $saleThroughDataByProduct['returned']) }}
                    </td>

                    <td class="text-right">
                        @if ((float) $saleThroughDataByProduct['received'] === (float) 0)
                            0.00
                        @else
                            @truncateDecimal((((float) $saleThroughDataByProduct['sold'] - (float) $saleThroughDataByProduct['returned']) * 100) / (float) $saleThroughDataByProduct['received'])
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">No Records</td>
                </tr>
            @endforelse

            <tr class="page-break-inside-avoid text-bold">
                <td colspan="{{ array_key_exists('Image', $columns) ? (array_key_exists('Color', $columns) ? 6 : 4) : (array_key_exists('Color', $columns) ? 5 : 3) }}">
                    <b>
                        Grand Total
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByProducts['received'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByProducts['sold'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByProducts['returned'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByProducts['remaining'] }}
                    </b>
                </td>

                <td class="text-right">
                    <b>
                        {{ $saleThroughTotalDataByProducts['sale_through'] }}
                    </b>
                </td>
            </tr>
        </tbody>
    </table>

    @if (count($chartRecords) > 0)
        <div class="page-break-inside-avoid">
            <h2> Pie Chart </h2>
            <div id="pie-chart" style="width: 100%; height:400px;"></div>
        </div>

        <div class="page-break-inside-avoid">
            <h2> Bar Chart </h2>
            <div id="bar-chart" style="width: 100%; height:400px;"></div>
        </div>
    @endif

    <script type="text/javascript">
        var pieChartElement = echarts.init(document.getElementById('pie-chart'));
        var barChartElement = echarts.init(document.getElementById('bar-chart'));

        var chartLabel = {!! isset($chartRecords['labels']) ? json_encode($chartRecords['labels']) : '[]' !!};
        var chartData = {!! isset($chartRecords['sale_through']) ? json_encode($chartRecords['sale_through']) : '[]' !!};

        chartLabel = chartLabel.length > 0 ? chartLabel : ['No Records'];
        chartData = chartData.length > 0 ? chartData : [10];

        const preparedRecords = [];
        for (const key in chartLabel) {
            preparedRecords.push({
                name: chartLabel[key],
                value: chartData[key]
            });
        }

        pieChartElement.setOption({
            tooltip: {
                valueFormatter: function(value) {
                    return formatLabelForChartWithPercentage(value.value)
                }
            },
            legend: {
                data: chartLabel,
                top: -6,
                type: 'scroll',
            },
            grid: {},
            series: [{
                scale: true,
                scaleSize: 20,
                type: 'pie',
                data: preparedRecords,
                label: {
                    fontSize: 13,
                    fontWeight: 'bold',
                    formatter: function(value) {
                        return formatLabelAndValueForChartWithPercentage(value.name, value.value);
                    }
                },
                itemStyle: {
                    shadowBlur: 5,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                },
                color: getPastelColors()
            }, ],
        });

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
                    fontWeight: 'bold',
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
