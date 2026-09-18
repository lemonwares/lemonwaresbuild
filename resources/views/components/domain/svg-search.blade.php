@props([])

<svg
    {{ $attributes->class(['domain-svg-scene']) }}
    viewBox="0 0 420 320"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <rect class="domain-svg-float" x="48" y="48" width="160" height="110" rx="12" stroke="currentColor" stroke-width="2" fill="color-mix(in srgb, currentColor 6%, #fff)" opacity="0.55" transform="skewY(-6)" />
    <rect class="domain-svg-float-delay" x="250" y="36" width="120" height="90" rx="12" stroke="currentColor" stroke-width="2" fill="color-mix(in srgb, currentColor 5%, #fff)" opacity="0.45" transform="skewY(-6)" />

    <g class="domain-svg-float">
        <rect x="110" y="90" width="200" height="140" rx="16" fill="#fff" stroke="currentColor" stroke-width="2.5" />
        <circle cx="210" cy="148" r="36" stroke="var(--color-rose)" stroke-width="3" fill="color-mix(in srgb, var(--color-rose) 10%, #fff)" />
        <path d="M190 148h40M210 128v40" stroke="var(--color-rose)" stroke-width="3" stroke-linecap="round" />
        <circle cx="228" cy="166" r="10" stroke="var(--color-rose)" stroke-width="2.5" fill="none" />
    </g>

    <g class="domain-svg-float-delay">
        <rect x="70" y="210" width="210" height="44" rx="22" fill="#fff" stroke="currentColor" stroke-width="2" />
        <circle cx="96" cy="232" r="10" stroke="var(--color-rose)" stroke-width="2.5" fill="none" />
        <path d="M103 239l8 8" stroke="var(--color-rose)" stroke-width="2.5" stroke-linecap="round" />
        <rect x="120" y="224" width="110" height="10" rx="5" fill="color-mix(in srgb, currentColor 12%, #fff)" />
        <rect x="248" y="218" width="24" height="24" rx="8" fill="var(--color-rose)" />
    </g>

    <g class="domain-svg-float">
        <circle cx="340" cy="200" r="48" stroke="currentColor" stroke-width="3" fill="color-mix(in srgb, var(--color-rose) 8%, #fff)" />
        <circle cx="340" cy="200" r="28" stroke="var(--color-rose)" stroke-width="2.5" fill="none" />
        <path d="M360 220l28 28" stroke="var(--color-rose)" stroke-width="6" stroke-linecap="round" />
        <text x="340" y="206" text-anchor="middle" fill="var(--color-rose)" font-size="14" font-weight="700" font-family="system-ui,sans-serif">.com</text>
    </g>

    <path class="domain-svg-dash" d="M250 180c30-20 50-10 70 10" stroke="var(--color-rose)" stroke-width="2" stroke-dasharray="6 6" fill="none" />
</svg>
