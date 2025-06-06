<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>
@if (isset($emailLogos) && array_key_exists('header', $emailLogos) && $emailLogos['header'] !== null)
    <img src="{{ $emailLogos['header'] }}" width="100"/>
@endif
<br/>

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

@if (isset($emailLogos))
<img src="{{ $emailLogos['footer'] }}" alt="footer-image">
@endif
{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
