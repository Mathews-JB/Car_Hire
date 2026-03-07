/**
 * Premium Modal Transition System
 * Automatically patches all modal show/hide across the app.
 * Works with any element that has class "modal-overlay".
 */
(function() {
    'use strict';

    // --- Core Animated Open ---
    window.openModalAnimated = function(modalId) {
        const modal = typeof modalId === 'string' 
            ? document.getElementById(modalId) 
            : modalId;
        if (!modal) return;

        // Remove any stale closing state
        modal.classList.remove('modal-closing');
        
        // Show with flex but start invisible (CSS handles initial state)
        modal.style.display = 'flex';
        
        // Force a reflow so the browser registers the display change
        modal.offsetHeight;

        // Now trigger the entrance animation
        modal.classList.add('modal-visible');
        
        // Prevent background scrolling
        document.body.style.overflow = 'hidden';
    };

    // --- Core Animated Close ---
    window.closeModalAnimated = function(modalId) {
        const modal = typeof modalId === 'string' 
            ? document.getElementById(modalId) 
            : modalId;
        if (!modal) return;

        // Add closing class for exit animation
        modal.classList.add('modal-closing');
        modal.classList.remove('modal-visible');

        // Wait for exit animation, then hide fully
        setTimeout(function() {
            modal.style.display = 'none';
            modal.classList.remove('modal-closing');
            // Restore scrolling if no other modals are open
            const openModals = document.querySelectorAll('.modal-overlay.modal-visible');
            if (openModals.length === 0) {
                document.body.style.overflow = '';
            }
        }, 350); // Match CSS transition duration
    };

    // --- Auto-patch existing openModal / closeModal functions ---
    document.addEventListener('DOMContentLoaded', function() {

        // Override global openModal if it exists
        if (typeof window.openModal === 'function') {
            const originalOpen = window.openModal;
            window.openModal = function(id) {
                const el = document.getElementById(id);
                if (el && el.classList.contains('modal-overlay')) {
                    openModalAnimated(id);
                } else {
                    originalOpen(id);
                }
            };
        } else {
            // Create it if it doesn't exist
            window.openModal = function(id) {
                openModalAnimated(id);
            };
        }

        if (typeof window.closeModal === 'function') {
            const originalClose = window.closeModal;
            window.closeModal = function(id) {
                const el = typeof id === 'string' ? document.getElementById(id) : id;
                if (el && el.classList.contains('modal-overlay')) {
                    closeModalAnimated(id);
                } else {
                    originalClose(id);
                }
            };
        } else {
            window.closeModal = function(id) {
                closeModalAnimated(id);
            };
        }

        // --- Click-outside-to-close with animation ---
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay') && e.target.classList.contains('modal-visible')) {
                closeModalAnimated(e.target);
            }
        });

        // --- ESC key to close topmost modal ---
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal-overlay.modal-visible');
                if (openModals.length > 0) {
                    closeModalAnimated(openModals[openModals.length - 1]);
                }
            }
        });

        // --- Patch inline style.display = 'flex' calls ---
        // Watch for modals being shown via raw style.display = 'flex'
        // and upgrade them to the animated version
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const el = mutation.target;
                    if (el.classList.contains('modal-overlay') && 
                        el.style.display === 'flex' && 
                        !el.classList.contains('modal-visible') &&
                        !el.classList.contains('modal-closing')) {
                        // Someone set display:flex directly — upgrade to animated
                        el.style.display = 'flex';
                        el.offsetHeight; // reflow
                        el.classList.add('modal-visible');
                        document.body.style.overflow = 'hidden';
                    }
                    // If someone set display:none directly without animation
                    if (el.classList.contains('modal-overlay') && 
                        el.style.display === 'none' &&
                        el.classList.contains('modal-visible')) {
                        el.classList.remove('modal-visible');
                        document.body.style.overflow = '';
                    }
                }
            });
        });

        // Observe all existing modal overlays
        document.querySelectorAll('.modal-overlay').forEach(function(modal) {
            observer.observe(modal, { attributes: true, attributeFilter: ['style'] });
        });
    });
})();
