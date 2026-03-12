// ============================================================
// main.js — JS global AppMana
// ============================================================

// Auto-resize textarea hero
document.addEventListener('DOMContentLoaded', () => {

    // Fermeture des modales via overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.add('hidden');
        });
    });

    // Polling notifications (toutes les 30 secondes)
    if (typeof APP_URL !== 'undefined') {
        setInterval(checkNotifications, 30000);
    }
});

async function checkNotifications() {
    try {
        const r    = await fetch(APP_URL + '/api/notifications.php');
        const data = await r.json();
        if (!data.success) return;

        const count = data.data.count;
        const badge = document.getElementById('notifBadge') || document.querySelector('.notif-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (e) { /* silencieux */ }
}
