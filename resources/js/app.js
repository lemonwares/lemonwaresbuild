import './chat-widget.js';
import './reviews-carousel.js';

document.querySelectorAll('[data-accordion]').forEach((accordion) => {
    const items = accordion.querySelectorAll('details');

    items.forEach((item) => {
        item.addEventListener('toggle', () => {
            if (! item.open) {
                return;
            }

            items.forEach((other) => {
                if (other !== item) {
                    other.open = false;
                }
            });
        });
    });
});

