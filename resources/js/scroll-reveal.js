const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.querySelectorAll('[data-reveal]').forEach((el) => {
    if (reduceMotion) {
        el.classList.add('is-revealed');
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        },
        {
            threshold: 0.14,
            rootMargin: '0px 0px -6% 0px',
        },
    );

    observer.observe(el);
});
