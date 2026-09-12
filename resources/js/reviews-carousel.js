const AUTOPLAY_MS = 5000;

const initReviewsCarousel = () => {
    document.querySelectorAll('[data-reviews-carousel]').forEach((carousel) => {
        const slides = carousel.querySelectorAll('[data-reviews-slide]');
        const dots = carousel.querySelectorAll('[data-reviews-dot]');
        const prevBtn = carousel.querySelector('[data-reviews-prev]');
        const nextBtn = carousel.querySelector('[data-reviews-next]');

        if (slides.length <= 1) {
            return;
        }

        let index = 0;
        let timer = null;
        let isTransitioning = false;

        const show = (nextIndex) => {
            if (isTransitioning) {
                return;
            }

            const targetIndex = (nextIndex + slides.length) % slides.length;

            if (targetIndex === index) {
                return;
            }

            isTransitioning = true;

            slides[index].classList.remove('is-active');
            slides[index].setAttribute('aria-hidden', 'true');

            index = targetIndex;

            slides[index].classList.add('is-active');
            slides[index].setAttribute('aria-hidden', 'false');

            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === index;
                dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
                dot.classList.toggle('is-active', isActive);
            });

            window.setTimeout(() => {
                isTransitioning = false;
            }, 500);
        };

        const next = () => show(index + 1);
        const prev = () => show(index - 1);

        const stop = () => {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        };

        const start = () => {
            stop();
            timer = setInterval(next, AUTOPLAY_MS);
        };

        prevBtn?.addEventListener('click', () => {
            prev();
            start();
        });

        nextBtn?.addEventListener('click', () => {
            next();
            start();
        });

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                show(Number(dot.dataset.reviewsDot));
                start();
            });
        });

        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', (event) => {
            if (! carousel.contains(event.relatedTarget)) {
                start();
            }
        });

        start();
    });
};

initReviewsCarousel();

export { initReviewsCarousel };
