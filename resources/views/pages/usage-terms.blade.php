@extends('layouts.app')

@section('title', __('legal.usage.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('legal.usage.meta_description'))

@section('content')
    <x-layout.legal-page
        :title="__('legal.usage.title')"
        :lede="__('legal.usage.lede')"
        :badge="__('legal.usage.meta_title')"
        :sections="__('legal.usage.sections')"
    />
@endsection
