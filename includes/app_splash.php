<!-- Premium Glass Splash Preloader -->
<div id="appSplash" class="app-splash">
    <div class="splash-logo-container">
        <img src="<?php echo $is_in_portal ? '../' : ''; ?>public/images/splash_logo.png" class="splash-logo-img" alt="Logo">
    </div>
    <div class="splash-loader-wrapper">
        <div id="splashProgress" class="splash-loader-progress"></div>
    </div>
    <div style="margin-top: 15px; font-size: 0.7rem; color: rgba(255, 255, 255, 0.4); text-transform: uppercase; letter-spacing: 3px; font-weight: 700;">
        Synchronizing Portal
    </div>
</div>

<script>
    // High-end App Splash Handler
    window.addEventListener('load', function() {
        const splash = document.getElementById('appSplash');
        const progress = document.getElementById('splashProgress');
        
        // Initializing delay
        setTimeout(() => {
            if(progress) progress.style.width = '100%';
            setTimeout(() => {
                document.body.classList.add('splash-loaded');
                // Optional: remove from DOM after fade
                setTimeout(() => {
                    if(splash) splash.style.display = 'none';
                }, 1000);
            }, 600);
        }, 400);
    });
</script>
