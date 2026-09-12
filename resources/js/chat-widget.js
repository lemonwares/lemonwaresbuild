const initChatWidget = () => {
    const widget = document.querySelector('[data-chat-widget]');

    if (! widget) {
        return;
    }

    const panel = widget.querySelector('[data-chat-panel]');
    const toggle = widget.querySelector('[data-chat-toggle]');
    const closeBtn = widget.querySelector('[data-chat-close]');
    const backBtn = widget.querySelector('[data-chat-back]');
    const picker = widget.querySelector('[data-chat-picker]');
    const aiView = widget.querySelector('[data-chat-ai]');
    const repView = widget.querySelector('[data-chat-representative]');
    const messagesEl = widget.querySelector('[data-chat-messages]');
    const form = widget.querySelector('[data-chat-form]');
    const input = widget.querySelector('#chat-widget-input');
    const headerText = widget.querySelector('[data-chat-header-text]');

    let currentMode = null;
    let greeted = false;

    const headers = {
        picker: ['Chat With Us', "We're here to help"],
        ai: ['AI Assistant', 'Ask us anything about our services'],
        representative: ['Live Representative', 'Connect with our team'],
    };

    const setHeader = (mode) => {
        const [title, subtitle] = headers[mode] ?? headers.picker;
        headerText.innerHTML = `
            <p class="text-sm font-semibold text-on-blush">${title}</p>
            <p class="truncate text-xs text-on-blush/60">${subtitle}</p>
        `;
    };

    const showView = (view) => {
        [picker, aiView, repView].forEach((el) => {
            el.hidden = el !== view;
        });
        backBtn.hidden = view === picker;
    };

    const openPanel = () => {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        widget.classList.add('chat-widget-open');
    };

    const closePanel = () => {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        widget.classList.remove('chat-widget-open');
    };

    const resetToPicker = () => {
        currentMode = null;
        setHeader('picker');
        showView(picker);
    };

    const openMode = (mode) => {
        currentMode = mode;
        setHeader(mode);

        if (mode === 'ai') {
            showView(aiView);

            if (! greeted) {
                greeted = true;
                appendMessage(
                    'bot',
                    "Hi! I'm the Lemonwares assistant. Ask me about hosting plans, business email, web development, mobile apps, or how to get started.",
                );
            }

            input?.focus();

            return;
        }

        if (mode === 'representative') {
            showView(repView);
        }
    };

    const appendMessage = (role, text) => {
        const bubble = document.createElement('div');
        bubble.className = role === 'user' ? 'chat-widget-message chat-widget-message-user' : 'chat-widget-message chat-widget-message-bot';
        bubble.textContent = text;
        messagesEl.appendChild(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const getAiReply = (message) => {
        const text = message.toLowerCase();

        if (/hello|hi|hey|good (morning|afternoon|evening)/.test(text)) {
            return 'Hello! How can I help you today — hosting, business email, or a custom web project?';
        }

        if (/host|cpanel|plesk|vps|server|cloud/.test(text)) {
            return 'We offer cPanel and Plesk cloud hosting plus AMD EPYC VPS servers. Shared hosting starts with SSL, business email, and 24/7 support. Want a recommendation? Chat with a representative on WhatsApp.';
        }

        if (/email|mail|domain/.test(text)) {
            return 'We provide professional business email tied to your domain — secure, reliable, and easy to manage alongside your hosting.';
        }

        if (/web|website|app|mobile|develop|build|design/.test(text)) {
            return 'Our team builds websites and mobile apps — from WordPress to custom Laravel stacks. Share your project scope and we\'ll point you in the right direction.';
        }

        if (/price|cost|plan|pricing|how much/.test(text)) {
            return 'Pricing depends on your hosting tier or project scope. Tell us what you need and a representative can share the best option — tap Live Representative to chat on WhatsApp.';
        }

        if (/contact|phone|call|whatsapp|human|person|representative|support/.test(text)) {
            return 'You can reach our team on WhatsApp for the fastest reply, or email hello@lemonwares.com. Want me to connect you? Choose Live Representative from the menu.';
        }

        if (/location|address|office|lekki|lagos|where/.test(text)) {
            return 'We\'re at 26, Akin Leigh Crescent, Lekki Phase 1, Lagos, Nigeria. Need directions? You\'ll find a link in our footer.';
        }

        return "I'm not sure about that yet — for detailed help, choose Live Representative to chat with our team on WhatsApp. You can also email hello@lemonwares.com.";
    };

    toggle.addEventListener('click', () => {
        if (panel.hidden) {
            openPanel();
            resetToPicker();
        } else {
            closePanel();
        }
    });

    closeBtn.addEventListener('click', closePanel);

    backBtn.addEventListener('click', resetToPicker);

    widget.querySelectorAll('[data-chat-mode]').forEach((btn) => {
        btn.addEventListener('click', () => openMode(btn.dataset.chatMode));
    });

    form?.addEventListener('submit', (event) => {
        event.preventDefault();

        const message = input.value.trim();

        if (! message) {
            return;
        }

        appendMessage('user', message);
        input.value = '';

        window.setTimeout(() => {
            appendMessage('bot', getAiReply(message));
        }, 400);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ! panel.hidden) {
            closePanel();
        }
    });
};

initChatWidget();

export { initChatWidget };
