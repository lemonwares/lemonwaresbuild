@component('mail::message')
# New contact form message

@component('mail::panel')
**From:** {{ $fullName }} ({{ $email }})

**Subject:** {{ $subjectLine }}
@endcomponent

{{ $body }}

Thanks,<br>
**{{ config('site.short_name') }}** website
@endcomponent
