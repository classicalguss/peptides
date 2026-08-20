// Mobile-only expandable rows on the lab reports table. Each product row
// carries data-coa-row and is followed by a data-coa-detail row whose
// panel animates open via the grid-template-rows 0fr -> 1fr technique
// (a table row itself cannot transition its height). Desktop shows the
// full columns instead, so the toggle is never visible there.
document.querySelectorAll('[data-coa-row]').forEach((row) => {
    const detail = row.nextElementSibling;
    const button = row.querySelector('[data-coa-toggle]');
    const panel = detail?.querySelector('[data-coa-panel]');
    const content = detail?.querySelector('[data-coa-content]');

    if (! detail?.hasAttribute('data-coa-detail') || ! button || ! panel || ! content) {
        return;
    }

    row.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            return;
        }

        const open = panel.style.gridTemplateRows !== '1fr';
        panel.style.gridTemplateRows = open ? '1fr' : '0fr';
        content.classList.toggle('opacity-0', ! open);
        button.setAttribute('aria-expanded', String(open));
        button.querySelector('svg')?.classList.toggle('rotate-180', open);
    });
});
