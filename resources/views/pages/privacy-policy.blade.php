@extends('layouts.app')

@section('title', __('legal.privacy.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('legal.privacy.meta_description'))

@section('content')
    <x-layout.legal-page
        :title="__('legal.privacy.title')"
        :lede="__('legal.privacy.lede')"
        :badge="__('legal.privacy.meta_title')"
        :sections="__('legal.privacy.sections')"
    />
@endsection
