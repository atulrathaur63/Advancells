/**
 * Advancells HRMS - Frontend Application JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Live Digital Clock
    function updateClocks() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        
        document.querySelectorAll('.clock-live').forEach(el => {
            el.textContent = timeStr;
        });

        const dateStr = now.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        document.querySelectorAll('.date-live').forEach(el => {
            el.textContent = dateStr;
        });
    }
    updateClocks();
    setInterval(updateClocks, 1000);

    // 2. Tab Navigation
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const target = btn.dataset.tab;
            if (!target) return;

            const parentContainer = btn.closest('.card') || document;
            parentContainer.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            parentContainer.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            const targetContent = parentContainer.querySelector(`#${target}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });

    // 3. Mobile Sidebar Toggle
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.app-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // 4. Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        });
    }, 5000);
});

function confirmAction(message) {
    return confirm(message || 'Are you sure you want to perform this action?');
}
