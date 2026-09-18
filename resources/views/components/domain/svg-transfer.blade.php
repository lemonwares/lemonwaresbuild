@props([])

<svg
    {{ $attributes->class(['domain-svg-scene']) }}
    viewBox="0 0 420 320"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <rect class="domain-svg-float" x="70" y="60" width="220" height="150" rx="14" fill="#fff" stroke="currentColor" stroke-width="2.5" />
    <rect x="90" y="82" width="80" height="10" rx="5" fill="color-mix(in srgb, currentColor 14%, #fff)" />
    <rect x="90" y="104" width="140" height="8" rx="4" fill="color-mix(in srgb, currentColor 10%, #fff)" />
    <rect x="90" y="122" width="120" height="8" rx="4" fill="color-mix(in srgb, currentColor 10%, #fff)" />
    <rect x="90" y="150" width="100" height="28" rx="8" fill="var(--color-rose)" opacity="0.9" />

    <g class="domain-svg-float-delay">
        <circle cx="320" cy="150" r="52" stroke="currentColor" stroke-width="2.5" fill="color-mix(in srgb, var(--color-rose) 8%, #fff)" />
        <text x="320" y="156" text-anchor="middle" fill="var(--color-rose)" font-size="16" font-weight="700" font-family="system-ui,sans-serif">.net</text>
        <circle cx="352" cy="178" r="18" stroke="var(--color-rose)" stroke-width="3" fill="#fff" />
        <path d="M345 178h14M352 171v14" stroke="var(--color-rose)" stroke-width="2.5" stroke-linecap="round" />
    </g>

    <g class="domain-svg-float">
        <path d="M150 250h90" stroke="var(--color-rose)" stroke-width="4" stroke-linecap="round" />
        <path d="M150 250l16-12M150 250l16 12" stroke="var(--color-rose)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M240 250l-16-12M240 250l-16 12" stroke="var(--color-rose)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
    </g>

    <g class="domain-svg-coin domain-svg-float-delay">
        <circle cx="90" cy="250" r="18" fill="var(--color-rose)" />
        <text x="90" y="256" text-anchor="middle" fill="#fff" font-size="14" font-weight="700" font-family="system-ui,sans-serif">$</text>
    </g>
    <g class="domain-svg-coin domain-svg-float">
        <circle cx="330" cy="250" r="18" fill="var(--color-rose)" />
        <text x="330" y="256" text-anchor="middle" fill="#fff" font-size="14" font-weight="700" font-family="system-ui,sans-serif">$</text>
    </g>

    <path class="domain-svg-dash" d="M200 220c40 10 70 10 100-20" stroke="var(--color-rose)" stroke-width="2" stroke-dasharray="5 7" fill="none" />
</svg>
