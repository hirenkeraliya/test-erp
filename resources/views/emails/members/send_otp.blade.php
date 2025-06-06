@component('mail::message',['emailLogos' => $emailLogos])

## Dear {{ $member->getFullName() }},

{{ $message }}

Best regards,<br/>
The {{ config('app.name') }} Team
@endcomponent
