// Remembers how far a visitor had scrolled on listing pages so that going
// back from a product page returns them to the same spot instead of the top.
const key = () => `scroll:${location.pathname}${location.search}`;
let ticking = false;

window.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
        sessionStorage.setItem(key(), String(window.scrollY));
        ticking = false;
    });
}, { passive: true });

const navigation = performance.getEntriesByType('navigation')[0];
const cameBack = navigation?.type === 'back_forward';
const saved = Number(sessionStorage.getItem(key()));

if (cameBack && saved > 0) {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    const restore = () => window.scrollTo({ top: saved, behavior: 'instant' });
    restore();
    // Lazy images settle layout after load; restore again once they have.
    window.addEventListener('load', () => requestAnimationFrame(restore), { once: true });
}
