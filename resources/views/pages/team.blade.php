@extends('layouts.app')

@section('title', __('pages.team.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.team.meta_description'))

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                        {{ __('pages.team.eyebrow') }}
                    </p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('pages.team.title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                        {{ __('pages.team.lede') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#team-grid" class="btn bg-white text-rose hover:bg-blush">
                            <span>{{ __('pages.team.cta') }}</span>
                        </a>
                        <a href="{{ route('careers') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                            <span>{{ __('pages.team.cta_careers') }}</span>
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </a>
                    </div>
                </div>
                <div class="dev-cutout-hero hidden lg:flex" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset('images/heroes/team.webp') }}" type="image/webp">
                        <img
                            src="{{ asset('images/heroes/team.png') }}"
                            alt=""
                            width="480"
                            height="480"
                            class="dev-cutout-img"
                            loading="eager"
                            decoding="async"
                        >
                    </picture>
                </div>
            </div>
        </div>
    </section>

    <section id="team-grid" class="scroll-mt-28 section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.team.grid_label') }}</p>
                <h2 class="heading">{{ __('pages.team.grid_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.team.grid_lede') }}</p>
            </div>

            <div class="team-member-track" @if ($members->isNotEmpty()) data-team-carousel @endif>
                @forelse ($members as $member)
                    @php
                        $initials = \Illuminate\Support\Str::of($member->name)->explode(' ')->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->take(2)->join('');
                        $social = collect([
                            'x' => $member->x_url,
                            'linkedin' => $member->linkedin_url,
                            'instagram' => $member->instagram_url,
                            'facebook' => $member->facebook_url,
                        ])->filter(fn ($url) => filled($url));
                    @endphp
                    <article class="team-member-card group">
                        <div class="team-member-media">
                            @if ($member->photo_path)
                                <img
                                    src="{{ asset('storage/' . $member->photo_path) }}"
                                    alt="{{ $member->name }}"
                                    class="team-member-photo"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @else
                                <div class="team-member-fallback" aria-hidden="true">
                                    <span>{{ $initials }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="team-member-overlay">
                            <h3 class="team-member-name" title="{{ $member->name }}">{{ $member->name }}</h3>
                            <p class="team-member-role" title="{{ $member->role }}">{{ $member->role }}</p>

                            @if ($social->isNotEmpty())
                                <div class="team-member-social">
                                    @if ($social->has('x'))
                                        <a href="{{ $social->get('x') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on X" class="team-member-social-link">
                                            <x-ui.icons.x class="size-4" />
                                        </a>
                                    @endif
                                    @if ($social->has('linkedin'))
                                        <a href="{{ $social->get('linkedin') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on LinkedIn" class="team-member-social-link">
                                            <x-ui.icons.linkedin class="size-4" />
                                        </a>
                                    @endif
                                    @if ($social->has('instagram'))
                                        <a href="{{ $social->get('instagram') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on Instagram" class="team-member-social-link">
                                            <x-ui.icons.instagram class="size-4" />
                                        </a>
                                    @endif
                                    @if ($social->has('facebook'))
                                        <a href="{{ $social->get('facebook') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on Facebook" class="team-member-social-link">
                                            <x-ui.icons.facebook class="size-4" />
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <article class="rounded-3xl border border-border bg-white p-8 text-center sm:col-span-2 lg:col-span-3">
                        <p class="text-base font-light text-on-blush/75">{{ __('pages.team.empty') }}</p>
                        <a href="{{ route('careers') }}" class="btn btn-primary mt-6 inline-flex">
                            <span>{{ __('pages.team.cta_careers') }}</span>
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </a>
                    </article>
                @endforelse
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-white">
        <div class="container-page py-14 sm:py-16">
            <h2 class="heading mb-3">{{ __('pages.team.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.team.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('contact') }}" class="btn btn-primary">
                    <span>{{ __('pages.team.get_in_touch') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
                <a href="{{ route('careers') }}" class="btn btn-ghost">
                    <span>{{ __('pages.team.cta_careers') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection
