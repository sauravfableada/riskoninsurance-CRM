/**
 * Theme Management System
 * Handles dark/light mode switching and persistence.
 */
(function () {
    // 1. Initial theme application (to prevent flash of unstyled content)
    const savedTheme = localStorage.getItem('crm-theme') || 'light';
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    // 2. Initialize interactive elements when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('darkModeToggle');
        const toggleIcon = document.getElementById('darkModeIcon');

        if (!toggleBtn) return;

        /**
         * Applies the specified theme and updates local storage/UI.
         * @param {string} theme - 'light' or 'dark'
         */
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('crm-theme', theme);
            
            if (toggleIcon) {
                toggleIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
        }

        // Synchronize icon state with current theme
        const currentTheme = localStorage.getItem('crm-theme') || 'light';
        applyTheme(currentTheme);

        // Toggle event listener
        toggleBtn.addEventListener('click', function () {
            const nextTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);
        });
    });
})();
