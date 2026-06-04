/**
 * TraceOn — ES Module Entry Point
 */
import { initToastContainer } from './modules/toast.js';
import { initSidebar }        from './modules/sidebar.js';

document.addEventListener('DOMContentLoaded', () => {
    initToastContainer();
    initSidebar();
});
