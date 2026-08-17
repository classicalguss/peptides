/**
 * Entry acknowledgment gate. Shown once per browser (localStorage), before
 * the visitor is considered to have entered the site.
 */
const STORAGE_KEY = 'pup-entry-acknowledged';

function showGate() {
    const gate = document.getElementById('entry-gate');

    if (!gate || localStorage.getItem(STORAGE_KEY) === '1') {
        return;
    }

    gate.classList.remove('hidden');
    gate.classList.add('flex');
    document.body.classList.add('overflow-hidden');

    document.getElementById('entry-gate-accept')?.addEventListener('click', () => {
        localStorage.setItem(STORAGE_KEY, '1');
        gate.classList.add('hidden');
        gate.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    });
}

document.addEventListener('DOMContentLoaded', showGate);
