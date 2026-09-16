@props([])

<svg
    {{ $attributes->class(['domain-svg-scene']) }}
    viewBox="0 0 420 320"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <g class="domain-svg-float">
        <rect x="80" y="70" width="180" height="170" rx="16" fill="#fff" stroke="currentColor" stroke-width="2.5" />
        <rect x="100" y="96" width="100" height="10" rx="5" fill="color-mix(in srgb, currentColor 14%, #fff)" />
        <rect x="100" y="122" width="140" height="8" rx="4" fill="color-mix(in srgb, currentColor 10%, #fff)" />
        <rect x="100" y="144" width="120" height="8" rx="4" fill="color-mix(in srgb, currentColor 10%, #fff)" />
        <rect x="100" y="166" width="80" height="8" rx="4" fill="color-mix(in srgb, currentColor 10%, #fff)" />
        <rect x="100" y="198" width="90" height="24" rx="8" fill="var(--color-rose)" />
    </g>

    <g class="domain-svg-float-delay">
        <rect x="240" y="100" width="130" height="90" rx="14" fill="#fff" stroke="currentColor" stroke-width="2" />
        <circle cx="270" cy="130" r="10" fill="var(--color-rose)" />
        <rect x="290" y="124" width="60" height="8" rx="4" fill="color-mix(in srgb, currentColor 12%, #fff)" />
        <circle cx="270" cy="158" r="10" fill="color-mix(in srgb, var(--color-rose) 35%, #fff)" />
        <rect x="290" y="152" width="50" height="8" rx="4" fill="color-mix(in srgb, currentColor 12%, #fff)" />
    </g>

    <g class="domain-svg-float">
        <path d="M300 220l40-20 40 20-40 28z" stroke="currentColor" stroke-width="2" fill="color-mix(in srgb, var(--color-rose) 12%, #fff)" />
        <path d="M300 220v36l40 28 40-28v-36" stroke="currentColor" stroke-width="2" fill="none" />
        <circle class="domain-svg-dot" cx="340" cy="236" r="4" fill="var(--color-rose)" />
    </g>
</svg>
