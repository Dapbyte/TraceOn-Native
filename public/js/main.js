/**
 * TraceOn — ES Module Entry Point
 * Imports and initializes page-specific modules.
 * All imports must be ES modules. No inline on* handlers.
 */

// Toast and modal are initialized globally (always available)
import { initToastContainer } from './modules/toast.js';

document.addEventListener('DOMContentLoaded', () => {
    initToastContainer();
    // Page-specific module init will be added per phase
});
