@extends('layouts.app')

@section('title', __('site.home.meta_title'))
@section('meta_description', __('site.home.meta_description'))

@section('content')
    <x-home.hero-banner />
    <x-home.hosting-intro />
    <x-home.hosting-plans />
    <x-home.features />
    <x-home.web-development />
    <x-home.tech-partners />
    <x-home.business-email />
    <x-home.trust />
    <x-home.contact />
@endsection
