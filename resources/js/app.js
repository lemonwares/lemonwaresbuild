import './chat-widget.js';
import './reviews-carousel.js';
import './mobile-nav.js';
import './locale-switcher.js';
import './nav-email.js';
import './scroll-reveal.js';
import './confirm-modal.js';
import './complete-profile-modal.js';
import './email-checkout.js';
import './site-checkout.js';
import './email-plans.js';
import './form-submit.js';
import './contact-form.js';
import './action-loading.js';
import './domain-search.js';
import './domain-cart.js';
import './header-domain-search.js';
import './theme.js';
import './case-live-preview.js';
import './admin-sidebar.js';
import './account-sidebar.js';
import './admin-chrome.js';
import './admin-customer.js';
import './admin-whmcs-mappings.js';

document.querySelectorAll('[data-accordion]').forEach((accordion) => {
    const items = accordion.querySelectorAll('[data-accordion-item]');
    const galleryRoot = accordion.closest('[data-accordion-gallery]');

    const setGallery = (key) => {
        if (! galleryRoot || ! key) {
            return;
        }

        galleryRoot.querySelectorAll('[data-gallery-image]').forEach((image) => {
            image.classList.toggle('is-active', image.getAttribute('data-gallery-image') === key);
        });
    };

    const setOpen = (item, open) => {
        item.setAttribute('data-open', open ? 'true' : 'false');
        const trigger = item.querySelector('[data-accordion-trigger]');
        if (trigger) {
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (open) {
            setGallery(item.getAttribute('data-gallery-key'));
        }
    };

    items.forEach((item) => {
        if (item.getAttribute('data-open') === 'true') {
            setGallery(item.getAttribute('data-gallery-key'));
        }

        const trigger = item.querySelector('[data-accordion-trigger]');
        if (! trigger) {
            return;
        }

        trigger.addEventListener('click', () => {
            const willOpen = item.getAttribute('data-open') !== 'true';

            if (willOpen) {
                items.forEach((other) => {
                    if (other !== item) {
                        setOpen(other, false);
                    }
                });
            }

            setOpen(item, willOpen);
        });
    });
});
