<!-- Toast Notification Container -->
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; max-width: 400px;"></div>

<script>
/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {string} type - Type of toast: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duration in milliseconds (default: 4000)
 */
function showToast(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toastContainer');
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;
    
    // Icon based on type
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    // Colors based on type
    const colors = {
        success: { bg: 'rgba(16, 185, 129, 0.95)', border: '#10b981', icon: '#6ee7b7' },
        error: { bg: 'rgba(239, 68, 68, 0.95)', border: '#ef4444', icon: '#fca5a5' },
        warning: { bg: 'rgba(245, 158, 11, 0.95)', border: '#f59e0b', icon: '#fcd34d' },
        info: { bg: 'rgba(59, 130, 246, 0.95)', border: '#3b82f6', icon: '#93c5fd' }
    };
    
    const color = colors[type] || colors.info;
    
    toast.innerHTML = `
        <div style="
            background: ${color.bg};
            border-left: 4px solid ${color.border};
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-family: 'Inter', sans-serif;
            animation: slideInRight 0.3s ease-out;
            backdrop-filter: blur(10px);
            min-width: 300px;
        ">
            <i class="fas ${icons[type]}" style="font-size: 1.3rem; color: ${color.icon};"></i>
            <span style="flex: 1; font-size: 0.9rem; font-weight: 500;">${message}</span>
            <button onclick="this.closest('.toast-notification').remove()" style="
                background: none;
                border: none;
                color: rgba(255,255,255,0.7);
                cursor: pointer;
                font-size: 1.2rem;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: color 0.2s;
            " onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Auto-dismiss after duration
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Add CSS animations
if (!document.getElementById('toastStyles')) {
    const style = document.createElement('style');
    style.id = 'toastStyles';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        @media (max-width: 768px) {
            #toastContainer {
                left: 10px;
                right: 10px;
                max-width: none;
            }
        }
    `;
    document.head.appendChild(style);
}
</script>
