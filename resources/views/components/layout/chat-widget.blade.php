<div class="chat-widget" data-chat-widget data-whatsapp="{{ config('site.whatsapp') }}">

    {{-- Panel --}}
    <div class="chat-widget-panel" data-chat-panel hidden
         role="dialog" aria-label="Chat with Lemonwares" aria-modal="true">

        {{-- Header --}}
        <div class="chat-widget-header">
            <div class="min-w-0 flex-1" data-chat-header-text>
                <p class="text-sm font-bold" style="color:var(--color-ink);">Chat With Us</p>
                <p class="text-xs font-light" style="color:var(--color-ink-3);">We're here to help</p>
            </div>
            <button type="button" class="chat-widget-icon-btn" data-chat-back hidden aria-label="Go back">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </button>
            <button type="button" class="chat-widget-icon-btn" data-chat-close aria-label="Close chat">
                <x-ui.icons.x class="size-4" />
            </button>
        </div>

        {{-- Mode picker --}}
        <div class="chat-widget-body" data-chat-picker>
            <p class="mb-4 text-xs font-medium" style="color:var(--color-ink-3);">Choose how you'd like to reach us:</p>
            <div class="flex flex-col gap-2.5">
                <button type="button" class="chat-widget-option" data-chat-mode="ai">
                    <span class="chat-widget-option-icon chat-widget-option-icon-ai">
                        <x-ui.icons.bot class="size-5" />
                    </span>
                    <span class="min-w-0 text-left">
                        <span class="block text-sm font-bold" style="color:var(--color-ink);">AI Assistant</span>
                        <span class="block text-xs font-light" style="color:var(--color-ink-3);">Instant answers about hosting &amp; services</span>
                    </span>
                </button>
                <button type="button" class="chat-widget-option" data-chat-mode="representative">
                    <span class="chat-widget-option-icon chat-widget-option-icon-live">
                        <x-ui.icons.headset class="size-5" />
                    </span>
                    <span class="min-w-0 text-left">
                        <span class="block text-sm font-bold" style="color:var(--color-ink);">Live Representative</span>
                        <span class="block text-xs font-light" style="color:var(--color-ink-3);">Chat with our team on WhatsApp</span>
                    </span>
                </button>
            </div>
        </div>

        {{-- AI chat --}}
        <div class="chat-widget-body flex flex-col" data-chat-ai hidden>
            <div class="chat-widget-messages" data-chat-messages aria-live="polite"></div>
            <form class="chat-widget-form" data-chat-form>
                <label class="sr-only" for="chat-widget-input">Your message</label>
                <input
                    id="chat-widget-input" type="text" name="message"
                    placeholder="Ask about hosting, email, or development…"
                    autocomplete="off"
                    class="footer-input flex-1 rounded-full py-2.5 text-sm"
                >
                <button type="submit" class="btn btn-primary px-4 py-2.5 text-sm" aria-label="Send message">
                    <x-ui.icons.send class="size-4" />
                </button>
            </form>
        </div>

        {{-- WhatsApp representative --}}
        <div class="chat-widget-body" data-chat-representative hidden>
            <div class="rounded-xl border p-5 text-center" style="border-color:var(--color-border);">
                <span class="chat-widget-option-icon chat-widget-option-icon-live mx-auto mb-4">
                    <x-ui.icons.headset class="size-6" />
                </span>
                <h3 class="mb-2 text-sm font-bold" style="color:var(--color-ink);">Talk to Our Team</h3>
                <p class="mb-5 text-xs font-light leading-relaxed" style="color:var(--color-ink-3);">
                    A Lemonwares representative will reply on WhatsApp — usually within minutes during business hours.
                </p>
                <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer"
                   class="btn btn-primary w-full text-sm">
                    <x-ui.icons.message-circle class="size-4" />
                    <span>Continue on WhatsApp</span>
                </a>
                <p class="mt-4 text-xs" style="color:var(--color-ink-3);">
                    Or call
                    <a href="tel:{{ config('site.phone_e164') }}"
                       class="font-semibold transition hover:underline"
                       style="color:var(--color-red);">{{ config('site.phone') }}</a>
                </p>
            </div>
        </div>
    </div>

    {{-- Launcher button --}}
    <button type="button" class="chat-widget-launcher" data-chat-toggle
            aria-expanded="false" aria-controls="chat-widget-panel">
        <x-ui.icons.message-circle class="size-5" />
        <span class="chat-widget-launcher-label">Chat</span>
    </button>

</div>
