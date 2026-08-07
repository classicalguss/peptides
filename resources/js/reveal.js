/**
 * Reveal-on-scroll.
 *
 * Elements with `.reveal` fade and slide in the first time they enter the
 * viewport. Stagger a group with `style="--reveal-delay: 120ms"`.
 */
const SELECTOR = '.reveal';
const VISIBLE = 'is-visible';

function reveal() {
    const targets = document.querySelectorAll(`${SELECTOR}:not(.${VISIBLE})`);

    if (!targets.length) {
        return;
    }

    // No observer support, or the visitor asked for less motion: show everything.
    if (
        !('IntersectionObserver' in window) ||
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
        targets.forEach((el) => el.classList.add(VISIBLE));

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add(VISIBLE);
                observer.unobserve(entry.target);
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.05 }
    );

    targets.forEach((el) => {
        // Anything already on screen at load animates immediately.
        if (el.getBoundingClientRect().top < window.innerHeight) {
            el.classList.add(VISIBLE);

            return;
        }

        observer.observe(el);
    });
}

document.addEventListener('DOMContentLoaded', reveal);
window.addEventListener('pageshow', reveal);
