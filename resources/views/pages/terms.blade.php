@extends('layouts.app')

@section('title', __('legal.terms.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('legal.terms.meta_description'))

@section('content')
    <x-layout.legal-page
        :title="__('legal.terms.title')"
        :lede="__('legal.terms.lede')"
        :badge="__('legal.terms.meta_title')"
        :sections="__('legal.terms.sections')"
    />
@endsection
