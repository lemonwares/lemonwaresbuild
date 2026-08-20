import './chat-widget.js';
import './reviews-carousel.js';
import './mobile-nav.js';
import './scroll-reveal.js';
import './confirm-modal.js';
import './complete-profile-modal.js';
import './email-checkout.js';
import './email-plans.js';
import './form-submit.js';

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
