<?php
/**
 * Theme Switcher Component
 * Add this to any header or navigation bar
 */
?>
<div class="theme-switcher-wrapper">
    <button class="theme-btn" data-theme="default" onclick="toggleTheme('default')" title="Default Gray">
        <i class="fas fa-adjust"></i>
    </button>
    <button class="theme-btn" data-theme="light" onclick="toggleTheme('light')" title="Light Mode">
        <i class="fas fa-sun"></i>
    </button>
    <button class="theme-btn" data-theme="dark" onclick="toggleTheme('dark')" title="Dark Mode">
        <i class="fas fa-moon"></i>
    </button>
</div>
