@component('mail::message', ['emailLogos' => $emailLogos])
# Date: {{ $date }}

{{ $message }}:

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="text-align: left; border: 1px solid #ddd; padding: 8px;">Location</th>
            <th style="text-align: right; border: 1px solid #ddd; padding: 8px;">Products Count</th>
            <th style="text-align: right; border: 1px solid #ddd; padding: 8px;">Link</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($preparedData as $data)
        <tr>
            <td style="text-align: left; border: 1px solid #ddd; padding: 8px;">{{ $data['name'] }}</td>
            <td style="text-align: right; border: 1px solid #ddd; padding: 8px;">{{ $data['product_count'] }}</td>
            <td style="text-align: right; border: 1px solid #ddd; padding: 8px;"><a href="{{ $data['route'] }}">Link</a> </td>
        </tr>
        @endforeach
    </tbody>
</table>

Thanks,<br>
{{ config('app.name') }}

@endcomponent