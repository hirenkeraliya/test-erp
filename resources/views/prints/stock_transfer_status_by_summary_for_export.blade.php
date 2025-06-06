<table>
    <!-- Header rows -->
    <tr>
        <td colspan="4">{{ $company->name }}</td>
    </tr>
    <tr>
        <td colspan="4">Report Name: Stock Transfer Status Summary Report</td>
    </tr>
    <tr>
        <td colspan="4">Report Type: By Summary</td>
    </tr>
    <tr>
        <td colspan="4">Records From {{ $dateRange[0] }} to {{ $dateRange[1] }}</td>
    </tr>
    <tr>
        <td colspan="4">Date: {{ $date }}</td>
    </tr>
    <tr>
        <td colspan="4">Man days start from the {{ $manDaysStatus }} Status.</td>
    </tr>
    @if($locations)
    <tr>
        <td colspan="4">Source Locations: {{ $locations }}</td>
    </tr>
    @endif
    <tr></tr>
    <tr>
        <th>Ref #</th>
        <th>Status</th>
        <th>Date</th>
        <th>Total Man Days</th>
    </tr>

    @forelse($stockTransfers as $stockTransfer)
        @if(!empty($stockTransfer['transactions']))
            <tr>
                <td rowspan="{{ max(5, count($stockTransfer['transactions'])) }}">
                    Transfer Order Number: {{ $stockTransfer['transfer_order_number'] ?? 'N/A' }}<br />
                    Request Order Number: {{ $stockTransfer['request_order_number'] ?? 'N/A' }}<br />
                    Transfer Out Number: {{ $stockTransfer['transfer_out_number'] ?? 'N/A' }}<br />
                    Transfer In Number: {{ $stockTransfer['transfer_in_number'] ?? 'N/A' }}<br />
                    Receiver Location: {{ $stockTransfer['destination_location'] ?? 'N/A' }}
                </td>
                @if(count($stockTransfer['transactions']) > 0)
                    <td>{{ $stockTransfer['transactions'][0]['label'] }}</td>
                    <td>{{ $stockTransfer['transactions'][0]['date'] }} ({{ $stockTransfer['transactions'][0]['human_readable_date'] }})</td>
                @else
                    <td colspan="2">No Transactions Available</td>
                @endif
                <td rowspan="{{ max(5, count($stockTransfer['transactions'])) }}" style="text-align: center; vertical-align: middle;">
                    {{ $stockTransfer['total_man_days'] }}
                </td>
            </tr>

            @foreach($stockTransfer['transactions'] as $key => $transaction)
                @if($key > 0)
                    <tr>
                        <td>{{ $transaction['label'] }}</td>
                        <td>{{ $transaction['date'] }} ({{ $transaction['human_readable_date'] }})</td>
                    </tr>
                @endif
            @endforeach

            @for ($i = count($stockTransfer['transactions']); $i < 5; $i++)
                <tr><td colspan="2"></td></tr>
            @endfor

        @else
            <tr>
                <td rowspan="5">
                    Transfer Order Number: {{ $stockTransfer['transfer_order_number'] ?? 'N/A' }}<br />
                    Request Order Number: {{ $stockTransfer['request_order_number'] ?? 'N/A' }}<br />
                    Transfer Out Number: {{ $stockTransfer['transfer_out_number'] ?? 'N/A' }}<br />
                    Transfer In Number: {{ $stockTransfer['transfer_in_number'] ?? 'N/A' }}<br />
                    Receiver Location: {{ $stockTransfer['destination_location'] ?? 'N/A' }}
                </td>
                <td colspan="2" rowspan="5" style="text-align: center; vertical-align: middle;">No Transactions Available</td>
                <td rowspan="5" style="text-align: center; vertical-align: middle;">{{ $stockTransfer['total_man_days'] }}</td>
            </tr>
            <tr><td colspan="4"></td></tr>
            <tr><td colspan="4"></td></tr>
            <tr><td colspan="4"></td></tr>
        @endif
        <tr><td colspan="5"></td></tr>
    @empty
        <tr>
            <td colspan="5">No Records Found.</td>
        </tr>
    @endforelse
</table>