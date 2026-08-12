@extends('layouts.app')

@section('title', __('site.home.meta_title'))
@section('meta_description', __('site.home.meta_description'))

@section('content')
    <x-home.hero-banner />
    <x-home.hosting-intro data-reveal />
    <x-home.hosting-plans data-reveal />
    <x-home.features data-reveal />
    <x-home.web-development data-reveal />
    <x-home.tech-partners />
    <x-home.business-email data-reveal />
    <x-home.trust data-reveal />
    <x-home.contact data-reveal />
    <x-home.faq :limit="4" data-reveal />
@endsection
