@component('mail::message')
# {{ __('account.reset_mail_title') }}

{{ __('account.reset_mail_lede') }}

@component('mail::button', ['url' => $url])
{{ __('account.reset_mail_action') }}
@endcomponent

{{ __('account.reset_mail_expire', ['minutes' => $expireMinutes]) }}

{{ __('account.reset_mail_ignore') }}

{{ config('site.short_name') }}
@endcomponent
