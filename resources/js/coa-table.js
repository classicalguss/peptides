// Mobile-only expandable rows on the lab reports table. Each product row
// carries data-coa-row and is followed by a hidden data-coa-detail row;
// tapping the row (or its chevron) toggles the detail. Desktop shows the
// full columns instead, so the toggle is never visible there.
document.querySelectorAll('[data-coa-row]').forEach((row) => {
    const detail = row.nextElementSibling;
    const button = row.querySelector('[data-coa-toggle]');

    if (! detail?.hasAttribute('data-coa-detail') || ! button) {
        return;
    }

    row.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            return;
        }

        const open = detail.classList.toggle('hidden') === false;
        button.setAttribute('aria-expanded', String(open));
        button.querySelector('svg')?.classList.toggle('rotate-180', open);
    });
});
