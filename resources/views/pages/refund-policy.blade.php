@extends('layouts.app')

@section('title', __('legal.refund.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('legal.refund.meta_description'))

@section('content')
    <x-layout.legal-page
        :title="__('legal.refund.title')"
        :lede="__('legal.refund.lede')"
        :badge="__('legal.refund.meta_title')"
        :sections="__('legal.refund.sections')"
    />
@endsection
