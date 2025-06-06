@component('mail::message', ['emailLogos' => $emailLogos])
# Date: {{ $date }}

{{ $message }}

Thanks,<br>
{{ config('app.name') }}

@endcomponent
