@props([])

<svg
    {{ $attributes->class(['domain-chip-field']) }}
    viewBox="0 0 1200 640"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    preserveAspectRatio="xMidYMid slice"
    aria-hidden="true"
>
    {{-- Trace lines feeding the chip --}}
    <g class="domain-chip-traces" stroke="rgba(255,255,255,0.16)" stroke-width="1.25" fill="none">
        <path d="M0 180 H280 L320 220 H420" />
        <path d="M0 260 H240 L290 300 H420" />
        <path d="M0 340 H260 L310 380 H420" />
        <path d="M0 420 H300 L340 460 H420" />
        <path d="M1200 160 H920 L880 200 H780" />
        <path d="M1200 240 H960 L910 280 H780" />
        <path d="M1200 320 H940 L890 360 H780" />
        <path d="M1200 400 H900 L860 440 H780" />
        <path d="M200 0 V140 L260 180" />
        <path d="M400 0 V100 L450 140" />
        <path d="M800 0 V90 L760 130" />
        <path d="M1000 0 V150 L940 190" />
        <path d="M280 640 V500 L340 460" />
        <path d="M520 640 V520 L560 480" />
        <path d="M760 640 V510 L720 470" />
        <path d="M980 640 V490 L920 450" />
    </g>

    {{-- Energy pulses traveling along traces --}}
    <g class="domain-chip-energy">
        <circle class="domain-chip-pulse domain-chip-pulse-a" r="3.5" fill="#fff">
            <animateMotion dur="3.2s" repeatCount="indefinite" path="M0 180 H280 L320 220 H420" />
        </circle>
        <circle class="domain-chip-pulse domain-chip-pulse-b" r="3.5" fill="#ffe0e0">
            <animateMotion dur="4s" begin="0.6s" repeatCount="indefinite" path="M0 340 H260 L310 380 H420" />
        </circle>
        <circle class="domain-chip-pulse domain-chip-pulse-c" r="3.5" fill="#fff">
            <animateMotion dur="3.6s" begin="0.3s" repeatCount="indefinite" path="M1200 240 H960 L910 280 H780" />
        </circle>
        <circle class="domain-chip-pulse domain-chip-pulse-d" r="3.5" fill="#ffe0e0">
            <animateMotion dur="4.4s" begin="1s" repeatCount="indefinite" path="M1200 400 H900 L860 440 H780" />
        </circle>
        <circle class="domain-chip-pulse domain-chip-pulse-e" r="3" fill="#fff">
            <animateMotion dur="3.8s" begin="0.4s" repeatCount="indefinite" path="M200 0 V140 L260 180" />
        </circle>
        <circle class="domain-chip-pulse domain-chip-pulse-f" r="3" fill="#ffe0e0">
            <animateMotion dur="4.2s" begin="1.2s" repeatCount="indefinite" path="M760 640 V510 L720 470" />
        </circle>
        <circle class="domain-chip-pulse domain-chip-pulse-g" r="3" fill="#fff">
            <animateMotion dur="3.5s" begin="0.8s" repeatCount="indefinite" path="M1000 0 V150 L940 190" />
        </circle>
        <circle class="domain-chip-pulse domain-chip-pulse-h" r="3" fill="#ffe0e0">
            <animateMotion dur="4.6s" begin="0.2s" repeatCount="indefinite" path="M280 640 V500 L340 460" />
        </circle>
    </g>

    {{-- Central chip --}}
    <g transform="translate(600 320)">
        <g class="domain-chip-core">
            <rect x="-92" y="-72" width="184" height="144" rx="6" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.28)" stroke-width="2" />
            <rect x="-72" y="-52" width="144" height="104" rx="4" fill="rgba(0,0,0,0.22)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" />

            {{-- Pin stubs --}}
            <g stroke="rgba(255,255,255,0.22)" stroke-width="2">
                <path d="M-92 -40 H-112" /><path d="M-92 -20 H-112" /><path d="M-92 0 H-112" /><path d="M-92 20 H-112" /><path d="M-92 40 H-112" />
                <path d="M92 -40 H112" /><path d="M92 -20 H112" /><path d="M92 0 H112" /><path d="M92 20 H112" /><path d="M92 40 H112" />
                <path d="M-40 -72 V-92" /><path d="M-20 -72 V-92" /><path d="M0 -72 V-92" /><path d="M20 -72 V-92" /><path d="M40 -72 V-92" />
                <path d="M-40 72 V92" /><path d="M-20 72 V92" /><path d="M0 72 V92" /><path d="M20 72 V92" /><path d="M40 72 V92" />
            </g>

            {{-- Die circuitry --}}
            <g stroke="rgba(255,224,224,0.35)" stroke-width="1.25" fill="none">
                <rect x="-40" y="-28" width="80" height="56" rx="4" />
                <path d="M-40 0 H-60 M40 0 H60 M0 -28 V-44 M0 28 V44" />
                <circle cx="-20" cy="-10" r="3" fill="rgba(255,255,255,0.55)" class="domain-chip-die-dot" />
                <circle cx="18" cy="8" r="3" fill="rgba(255,224,224,0.5)" class="domain-chip-die-dot domain-chip-die-dot-delay" />
                <circle cx="0" cy="0" r="5" fill="rgba(255,255,255,0.45)" class="domain-chip-die-core" />
            </g>
        </g>
    </g>
</svg>
