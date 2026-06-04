/**
 * TraceOn — Sidebar Module
 * Accordion state (sessionStorage), collapse toggle, mobile hamburger overlay.
 */

const STORAGE_KEY = 'traceon_accordion_state';

export function initSidebar() {
    const sidebar      = document.getElementById('sidebar');
    const overlay      = document.getElementById('sidebar-overlay');
    const hamburgerBtn = document.getElementById('hamburger-btn');

    if (!sidebar) return;

    // Accordion
    restoreAccordionState();
    document.querySelectorAll('.sidebar-accordion-trigger').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const targetId = trigger.getAttribute('data-target');
            const content  = document.getElementById(targetId);
            const arrow    = trigger.querySelector('.sidebar-accordion-arrow');
            if (!content) return;
            const isOpen = content.classList.toggle('open');
            trigger.setAttribute('aria-expanded', String(isOpen));
            if (arrow) arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(-90deg)';
            saveAccordionState();
        });
    });

    // Mobile hamburger
    if (hamburgerBtn && overlay) {
        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('visible');
            document.body.style.overflow = 'hidden';
        });
        overlay.addEventListener('click', closeMobile);
    }

    function closeMobile() {
        sidebar.classList.remove('mobile-open');
        overlay?.classList.remove('visible');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) closeMobile();
    });
}

function saveAccordionState() {
    const state = {};
    document.querySelectorAll('.sidebar-accordion-trigger').forEach(t => {
        state[t.getAttribute('data-target')] = t.getAttribute('aria-expanded') === 'true';
    });
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch {}
}

function restoreAccordionState() {
    let state = {};
    try { const r = sessionStorage.getItem(STORAGE_KEY); if (r) state = JSON.parse(r); } catch {}
    document.querySelectorAll('.sidebar-accordion-trigger').forEach(trigger => {
        const targetId = trigger.getAttribute('data-target');
        const content  = document.getElementById(targetId);
        const arrow    = trigger.querySelector('.sidebar-accordion-arrow');
        if (!content) return;
        const isOpen = state[targetId] !== false;
        content.classList.toggle('open', isOpen);
        trigger.setAttribute('aria-expanded', String(isOpen));
        if (arrow) arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(-90deg)';
    });
}
