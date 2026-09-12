@component('mail::message')
# New contact form message

**From:** {{ $fullName }} ({{ $email }})

**Subject:** {{ $subjectLine }}

---

{{ $body }}

Thanks,<br>
{{ config('site.short_name') }} website
@endcomponent
