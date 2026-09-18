@component('mail::message')
# {{ __('pages.contact.ack_title') }}

{{ __('pages.contact.ack_body', ['name' => $fullName]) }}

@component('mail::panel')
**{{ __('pages.contact.subject') }}:** {{ $subjectLine }}
@endcomponent

{{ __('pages.contact.ack_footer') }}

Thanks,<br>
**{{ config('site.short_name') }}**
@endcomponent
