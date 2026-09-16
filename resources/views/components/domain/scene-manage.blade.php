@props([])

<div {{ $attributes->class(['domain-scene domain-scene-manage']) }} aria-hidden="true">
    <div class="domain-scene-glow"></div>
    <div class="domain-scene-orb is-a"></div>
    <div class="domain-scene-orb is-b"></div>

    <div class="domain-scene-dashboard domain-scene-float">
        <div class="domain-scene-dash-head">
            <span class="domain-scene-dash-brand">yourbrand.com</span>
            <span class="domain-scene-dash-status">Live</span>
        </div>
        <div class="domain-scene-dash-grid">
            <div class="domain-scene-dash-tile">
                <span>Hosting</span>
                <strong>Cloud</strong>
            </div>
            <div class="domain-scene-dash-tile">
                <span>Mail</span>
                <strong>Mailemon</strong>
            </div>
        </div>
        <ul class="domain-scene-dash-dns">
            <li><span>A</span> site</li>
            <li><span>MX</span> mail</li>
        </ul>
    </div>

    <div class="domain-scene-mail domain-scene-float-delay">
        <span class="domain-scene-mail-dot"></span>
        hello@yourbrand.com
    </div>
</div>
