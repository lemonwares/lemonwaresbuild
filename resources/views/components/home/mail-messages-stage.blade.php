@props([
    'float' => false,
])

<div {{ $attributes->class(['mail-messages-stage', 'is-float' => $float]) }} aria-hidden="true">
    @unless ($float)
        <div class="mail-messages-stage-glow"></div>
    @endunless

    <div class="mail-messages-stage-scene">
        <div class="mail-msg-bubble">
            <span class="mail-msg-dot"></span>
            <span class="mail-msg-dot"></span>
            <span class="mail-msg-dot"></span>
            <span class="mail-msg-tail"></span>
        </div>

        <div class="mail-msg-phone">
            <span class="mail-msg-phone-notch"></span>
            <span class="mail-msg-phone-screen">
                <span class="mail-msg-phone-line"></span>
                <span class="mail-msg-phone-line is-short"></span>
                <span class="mail-msg-phone-line"></span>
            </span>
        </div>

        <div class="mail-msg-envelope">
            <span class="mail-msg-envelope-flap"></span>
            <span class="mail-msg-envelope-card"></span>
        </div>

        <div class="mail-msg-heart">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 20s-7-4.35-7-9.2C5 7.5 7.2 5.5 9.6 5.5c1.4 0 2.5.7 2.4 1.9C12 6.2 13.1 5.5 14.5 5.5 16.9 5.5 19 7.5 19 10.8 19 15.65 12 20 12 20z" fill="currentColor"/>
            </svg>
        </div>

        <span class="mail-msg-dash mail-msg-dash-a"></span>
        <span class="mail-msg-dash mail-msg-dash-b"></span>
        <span class="mail-msg-dash mail-msg-dash-c"></span>
    </div>
</div>
