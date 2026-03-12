/**
 * Theme Switcher - Car Hire
 * Applies theme on page load and exposes window.toggleTheme globally
 */

// Apply saved theme immediately (runs in <head>, body may not exist yet)
(function applyThemeEarly() {
    try {
        var saved = localStorage.getItem('site-theme') || 'default';
        document.documentElement.setAttribute('data-theme', saved);
        document.documentElement.className = document.documentElement.className
            .replace(/\btheme-\S+/g, '')
            .trim();
        if (saved !== 'default') {
            document.documentElement.classList.add('theme-' + saved);
        }
    } catch (e) {}
})();

// Expose global toggle function (safe to call from onclick="toggleTheme(...)")
window.toggleTheme = function(theme) {
    // 1. Set data-theme attribute
    document.documentElement.setAttribute('data-theme', theme);

    // 2. Also set/remove class on <html> for extra CSS targeting
    document.documentElement.classList.remove('theme-light', 'theme-dark', 'theme-default');
    if (theme !== 'default') {
        document.documentElement.classList.add('theme-' + theme);
    }

    // 3. Persist selection
    try { localStorage.setItem('site-theme', theme); } catch(e){}

    // 4. Update button active states
    document.querySelectorAll('.theme-btn').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-theme') === theme);
    });

    // 5. Dispatch event for theme-aware components (like charts)
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme } }));
};

// Restore button states when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    var current = 'default';
    try { current = localStorage.getItem('site-theme') || 'default'; } catch(e){}

    // Make sure data-theme is applied (belt-and-suspenders)
    document.documentElement.setAttribute('data-theme', current);
    document.documentElement.classList.remove('theme-light', 'theme-dark', 'theme-default');
    if (current !== 'default') {
        document.documentElement.classList.add('theme-' + current);
    }

    // Mark active button
    document.querySelectorAll('.theme-btn').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-theme') === current);
    });
});
