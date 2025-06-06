@component('mail::message', ['emailLogos' => $emailLogos])
# Date: {{ $date }}

{{ $message }}:

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="text-align: left; border: 1px solid #ddd; padding: 8px;">Product Name</th>
            <th style="text-align: right; border: 1px solid #ddd; padding: 8px;">Article Number</th>
            <th style="text-align: right; border: 1px solid #ddd; padding: 8px;">Link</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($preparedData['products'] as $product)
        <tr>
            <td style="text-align: left; border: 1px solid #ddd; padding: 8px;">{{ $product['product_name'] }}</td>
            <td style="text-align: right; border: 1px solid #ddd; padding: 8px;">{{ $product['article_number'] }}</td>
            <td style="text-align: right; border: 1px solid #ddd; padding: 8px;">{!! $product['admin_link'] !!}</td>
        </tr>
        @endforeach
    </tbody>
    @if(array_key_exists('count_link', $preparedData) && $preparedData['count_link'] !== null)
    <tfoot>
        <tr>
            <td colspan="3" style="text-align: center; border: 1px solid #ddd; padding: 8px;">{!! $preparedData['count_link'] !!}</td>
        </tr>
    </tfoot>
    @endif
</table>

Thanks,<br>
{{ config('app.name') }}

@endcomponent