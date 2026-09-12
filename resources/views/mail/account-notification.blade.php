@component('mail::message')
# {{ $title }}

{{ $body }}

@component('mail::button', ['url' => $url])
{{ $action }}
@endcomponent

{{ config('site.short_name') }}
@endcomponent
