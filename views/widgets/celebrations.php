<?php
/**
 * Compact Celebrations Widget
 * Birthdays, Work Anniversaries & New Joinees
 */
$cSummary = $celebrations['summary'] ?? ['total' => 0, 'today_count' => 0, 'birthdays' => 0, 'anniversaries' => 0, 'new_joinees' => 0];
$cAll = $celebrations['all'] ?? [];
$cBirthdays = $celebrations['birthdays'] ?? [];
$cAnniversaries = $celebrations['anniversaries'] ?? [];
$cNewJoinees = $celebrations['new_joinees'] ?? [];
$currentUserId = Auth::id();
$currentEmpId = Auth::employeeId();
?>

<!-- Clean Professional Celebrations Card -->
<div class="card celebration-compact-card" id="celebrationWidget">
    <div class="card-header celebration-compact-header">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-cake-candles text-primary" style="font-size: 15px;"></i>
            <h3 class="card-title celebration-heading">Team Celebrations</h3>
            <?php if ($cSummary['today_count'] > 0): ?>
                <span class="badge-today"><?= $cSummary['today_count'] ?> Today</span>
            <?php endif; ?>
        </div>

        <!-- Clean Filter Tabs (No Emojis) -->
        <div class="celebration-compact-tabs" role="tablist">
            <button type="button" class="celebration-c-tab active" data-tab="all" title="All Milestones">
                <span>All</span> <span class="c-tab-num"><?= $cSummary['total'] ?></span>
            </button>
            <button type="button" class="celebration-c-tab" data-tab="birthday" title="Birthdays">
                <span>Birthdays</span> <span class="c-tab-num"><?= $cSummary['birthdays'] ?></span>
            </button>
            <button type="button" class="celebration-c-tab" data-tab="anniversary" title="Work Anniversaries">
                <span>Anniv.</span> <span class="c-tab-num"><?= $cSummary['anniversaries'] ?></span>
            </button>
            <button type="button" class="celebration-c-tab" data-tab="welcome" title="New Joinees">
                <span>Joinees</span> <span class="c-tab-num"><?= $cSummary['new_joinees'] ?></span>
            </button>
        </div>
    </div>

    <div class="card-body celebration-compact-body">
        <?php if (empty($cAll)): ?>
            <div class="celebration-compact-empty">
                <i class="fa-regular fa-calendar-check" style="font-size: 24px; color: #94a3b8; margin-bottom: 6px;"></i>
                <p style="font-size: 12.5px; color: var(--text-muted); margin: 0;">No upcoming milestones in the next 30 days.</p>
            </div>
        <?php else: ?>
            <div class="celebration-compact-list" id="celebrationCardsStream">
                <?php foreach ($cAll as $item): ?>
                    <div class="celebration-row <?= $item['is_today'] ? 'is-today' : '' ?>" data-type="<?= $item['type'] ?>">
                        <!-- Clean Initial Avatar -->
                        <div class="celebration-row-avatar">
                            <?php if (!empty($item['avatar'])): ?>
                                <img src="<?= url($item['avatar']) ?>" alt="<?= e($item['name']) ?>" class="celebration-row-img">
                            <?php else: ?>
                                <span><?= $item['initials'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Details (Name, Milestone, Dept) -->
                        <div class="celebration-row-info">
                            <div class="celebration-row-primary">
                                <span class="celebration-row-name"><?= e($item['name']) ?></span>
                                <?php if ($item['is_today']): ?>
                                    <span class="badge-today-mini">Today</span>
                                <?php endif; ?>
                            </div>
                            <div class="celebration-row-secondary">
                                <span class="celebration-row-milestone"><?= e($item['milestone_text']) ?></span>
                                <span class="c-dot">•</span>
                                <span><?= e($item['department_name']) ?></span>
                            </div>
                        </div>

                        <!-- Right Actions & Date -->
                        <div class="celebration-row-actions">
                            <?php if (!$item['is_today']): ?>
                                <span class="celebration-date-label"><?= $item['urgency_label'] ?></span>
                            <?php endif; ?>

                            <!-- Wishes Count Button -->
                            <?php if ($item['wishes_count'] > 0): ?>
                                <button type="button" class="celebration-mini-wishes-btn" 
                                        data-emp-id="<?= $item['employee_id'] ?>"
                                        data-emp-name="<?= e($item['name']) ?>"
                                        data-type="<?= $item['type'] ?>"
                                        title="<?= $item['wishes_count'] ?> wish(es) received">
                                    <i class="fa-regular fa-comment-dots"></i>
                                    <span id="wishesCount_<?= $item['employee_id'] ?>_<?= $item['type'] ?>"><?= $item['wishes_count'] ?></span>
                                </button>
                            <?php endif; ?>

                            <!-- Wish Trigger Button -->
                            <?php if ($item['employee_id'] === $currentEmpId): ?>
                                <span class="c-pill-you">You</span>
                            <?php elseif ($item['has_wished']): ?>
                                <span class="c-pill-wished"><i class="fa-solid fa-check"></i> Wished</span>
                            <?php else: ?>
                                <button type="button" class="btn-primary-soft wish-trigger-btn" 
                                        id="wishBtn_<?= $item['employee_id'] ?>_<?= $item['type'] ?>"
                                        data-emp-id="<?= $item['employee_id'] ?>"
                                        data-emp-name="<?= e($item['name']) ?>"
                                        data-type="<?= $item['type'] ?>"
                                        data-milestone="<?= e($item['milestone_text']) ?>">
                                    <i class="fa-regular fa-paper-plane"></i> Wish
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Send Wishes Modal -->
<div class="modal-backdrop-custom" id="sendWishModal" style="display: none;" onclick="if(event.target === this) closeWishModal()">
    <div class="modal-dialog-custom modal-dialog-compact">
        <div class="modal-header-custom">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="modal-header-icon" id="wishModalIcon">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <div>
                    <h4 class="modal-title-custom" id="wishModalTitle">Send Greeting</h4>
                    <span class="modal-subtitle-custom" id="wishModalSubtitle">Share your warm wishes</span>
                </div>
            </div>
            <button type="button" class="modal-close-custom" onclick="closeWishModal()">&times;</button>
        </div>

        <form id="sendWishForm" onsubmit="handleSendWishSubmit(event)">
            <?= csrf_field() ?>
            <input type="hidden" name="receiver_id" id="wishReceiverId">
            <input type="hidden" name="celebration_type" id="wishCelebrationType">

            <div class="modal-body-custom" style="padding: 16px 20px;">
                <!-- Preset Greeting Chips -->
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                        Quick Greetings:
                    </label>
                    <div class="wish-chips-container" id="wishChipsContainer">
                        <!-- Dynamically populated chips -->
                    </div>
                </div>

                <!-- Custom Message Input -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="wishMessageText" style="font-size: 12px; font-weight: 600; color: var(--text-main); margin-bottom: 4px; display: block;">
                        Your Message:
                    </label>
                    <textarea name="message" id="wishMessageText" rows="2" class="form-control" 
                              maxlength="255" required placeholder="Write a warm greeting..."></textarea>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                        <span>Visible to team</span>
                        <span id="wishCharCount">0/255</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer-custom" style="padding: 10px 20px;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeWishModal()">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary" id="wishSubmitBtn">
                    <i class="fa-solid fa-paper-plane"></i> Send Greeting 🎉
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Wishes List Modal -->
<div class="modal-backdrop-custom" id="viewWishesModal" style="display: none;" onclick="if(event.target === this) closeViewWishesModal()">
    <div class="modal-dialog-wishes">
        <div class="modal-header-custom">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="modal-header-icon" style="background: #fdf2f8; color: var(--brand-plum);">
                    <i class="fa-solid fa-gift"></i>
                </div>
                <div>
                    <h4 class="modal-title-custom" id="viewWishesTitle">Colleague Wishes</h4>
                    <span class="modal-subtitle-custom" id="viewWishesSubtitle">Warm greetings from team members</span>
                </div>
            </div>
            <button type="button" class="modal-close-custom" onclick="closeViewWishesModal()">&times;</button>
        </div>

        <div class="modal-body-custom" id="viewWishesBody" style="max-height: 420px; overflow-y: auto; padding: 14px 18px;">
            <div style="text-align: center; padding: 24px; color: var(--text-muted);">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 20px;"></i>
                <p style="margin-top: 6px; font-size: 12px;">Loading greetings...</p>
            </div>
        </div>

        <div class="modal-footer-custom" style="padding: 10px 18px; display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeViewWishesModal()">Close</button>
        </div>
    </div>
</div>

<!-- Celebrations JavaScript Logic -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Tab Switching
    const tabButtons = document.querySelectorAll('.celebration-c-tab');
    const rows = document.querySelectorAll('.celebration-row');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const targetTab = btn.getAttribute('data-tab');

            rows.forEach(item => {
                const itemType = item.getAttribute('data-type');
                if (targetTab === 'all' || itemType === targetTab) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // 2. Character counter for message
    const msgText = document.getElementById('wishMessageText');
    const charCount = document.getElementById('wishCharCount');
    if (msgText && charCount) {
        msgText.addEventListener('input', () => {
            charCount.textContent = `${msgText.value.length}/255`;
        });
    }

    // 3. Attach click handlers to wish triggers
    document.querySelectorAll('.wish-trigger-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const empId = btn.getAttribute('data-emp-id');
            const empName = btn.getAttribute('data-emp-name');
            const type = btn.getAttribute('data-type');
            const milestone = btn.getAttribute('data-milestone');
            openWishModal(empId, empName, type, milestone);
        });
    });

    // 4. Attach click handlers to view wishes counter
    document.querySelectorAll('.celebration-mini-wishes-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const empId = btn.getAttribute('data-emp-id');
            const empName = btn.getAttribute('data-emp-name');
            const type = btn.getAttribute('data-type');
            openViewWishesModal(empId, empName, type);
        });
    });
});

// Modal Open / Close helpers
function openWishModal(empId, empName, type, milestone) {
    document.getElementById('wishReceiverId').value = empId;
    document.getElementById('wishCelebrationType').value = type;
    document.getElementById('wishModalTitle').textContent = `Wish ${empName.split(' ')[0]}`;
    document.getElementById('wishModalSubtitle').textContent = milestone || 'Celebrate their milestone';
    
    // Set custom icon
    const iconEl = document.getElementById('wishModalIcon');
    if (type === 'birthday') {
        iconEl.innerHTML = '<i class="fa-solid fa-cake-candles"></i>';
        iconEl.style.background = '#fdf2f8';
        iconEl.style.color = '#ec4899';
    } else if (type === 'anniversary') {
        iconEl.innerHTML = '<i class="fa-solid fa-award"></i>';
        iconEl.style.background = '#fffbeb';
        iconEl.style.color = '#f59e0b';
    } else {
        iconEl.innerHTML = '<i class="fa-solid fa-hand-sparkles"></i>';
        iconEl.style.background = '#f0fdfa';
        iconEl.style.color = '#06b6d4';
    }

    // Populate Preset Chips
    const chipsContainer = document.getElementById('wishChipsContainer');
    chipsContainer.innerHTML = '';
    
    let presets = [];
    const firstName = empName.split(' ')[0];
    if (type === 'birthday') {
        presets = [
            `🎂 Wishing you a very Happy Birthday, ${firstName}! Have a wonderful year ahead! 🎉`,
            `🌟 Happy Birthday ${firstName}! May your year be filled with happiness and great achievements! 🥳`
        ];
    } else if (type === 'anniversary') {
        presets = [
            `🎖️ Congratulations on your milestone at Advancells, ${firstName}! Proud to work with you! 🥂`,
            `👏 Happy Work Anniversary ${firstName}! Thank you for your great contributions! 🚀`
        ];
    } else {
        presets = [
            `👋 Welcome to Advancells, ${firstName}! Excited to have you on board with the team! ✨`,
            `🚀 Welcome aboard ${firstName}! Wishing you great success and growth here! 🌟`
        ];
    }

    presets.forEach((msg) => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'wish-preset-chip';
        chip.textContent = msg;
        chip.onclick = () => {
            document.getElementById('wishMessageText').value = msg;
            document.getElementById('wishCharCount').textContent = `${msg.length}/255`;
        };
        chipsContainer.appendChild(chip);
    });

    if (presets.length > 0) {
        document.getElementById('wishMessageText').value = presets[0];
        document.getElementById('wishCharCount').textContent = `${presets[0].length}/255`;
    }

    document.getElementById('sendWishModal').style.display = 'flex';
}

function closeWishModal() {
    document.getElementById('sendWishModal').style.display = 'none';
}

function openViewWishesModal(empId, empName, type) {
    const titleEl = document.getElementById('viewWishesTitle');
    const subtitleEl = document.getElementById('viewWishesSubtitle');
    const body = document.getElementById('viewWishesBody');

    if (titleEl) titleEl.textContent = `Greetings for ${empName}`;
    if (subtitleEl) subtitleEl.textContent = 'Warm milestone greetings from teammates';

    body.innerHTML = `
        <div style="text-align: center; padding: 28px 16px; color: var(--text-muted);">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 22px; color: var(--brand-plum);"></i>
            <p style="margin-top: 8px; font-size: 12px;">Loading greetings...</p>
        </div>
    `;
    document.getElementById('viewWishesModal').style.display = 'flex';

    fetch(`<?= url('celebrations/wishes') ?>?employee_id=${empId}&type=${type || ''}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success || !data.wishes || data.wishes.length === 0) {
                body.innerHTML = `
                    <div class="wishes-empty-box">
                        <i class="fa-regular fa-comments"></i>
                        <div class="wishes-empty-title">No Greetings Yet</div>
                        <p class="wishes-empty-desc">Warm wishes sent by colleagues for this milestone will appear here in real-time.</p>
                    </div>
                `;
                return;
            }

            const count = data.wishes.length;
            let html = `
                <div class="wishes-summary-bar">
                    <div class="wishes-summary-left">
                        <i class="fa-solid fa-heart" style="color: #ec4899;"></i>
                        <span><strong>${count}</strong> ${count === 1 ? 'Greeting' : 'Greetings'} Received</span>
                    </div>
                    <span style="font-size: 11px; color: var(--text-muted); font-weight: 500;">Advancells Team</span>
                </div>
                <div class="wishes-organized-stream">
            `;

            data.wishes.forEach(w => {
                const name = w.sender_name || 'Colleague';
                const initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                
                let avatarClass = 'avatar-colleague';
                let roleClass = 'role-colleague';
                let roleLabel = 'Colleague';
                let roleIcon = 'fa-user';

                if (w.sender_role === 'super_admin' || w.sender_role === 'hr_admin') {
                    avatarClass = 'avatar-admin';
                    roleClass = 'role-admin';
                    roleLabel = w.sender_role === 'super_admin' ? 'Leadership' : 'HR Admin';
                    roleIcon = 'fa-shield-halved';
                } else if (w.sender_role === 'manager') {
                    avatarClass = 'avatar-manager';
                    roleClass = 'role-manager';
                    roleLabel = 'Manager';
                    roleIcon = 'fa-user-tie';
                }

                const deptParts = [w.sender_designation, w.sender_department].filter(Boolean);
                const deptInfo = deptParts.join(' • ');

                let avatarHtml = `<div class="wish-sender-avatar ${avatarClass}">${initials}</div>`;
                if (w.sender_avatar) {
                    avatarHtml = `
                        <div class="wish-sender-avatar">
                            <img src="${escapeHtml(w.sender_avatar)}" alt="${escapeHtml(name)}" class="wish-sender-avatar-img">
                        </div>
                    `;
                }

                html += `
                    <div class="organized-wish-card">
                        <div class="wish-card-header">
                            <div class="wish-sender-group">
                                ${avatarHtml}
                                <div class="wish-sender-details">
                                    <div class="wish-sender-name-row">
                                        <span class="wish-sender-name">${escapeHtml(name)}</span>
                                        <span class="wish-role-pill ${roleClass}">
                                            <i class="fa-solid ${roleIcon}"></i> ${roleLabel}
                                        </span>
                                    </div>
                                    ${deptInfo ? `<span class="wish-sender-dept" title="${escapeHtml(deptInfo)}">${escapeHtml(deptInfo)}</span>` : ''}
                                </div>
                            </div>
                            <span class="wish-timestamp" title="${escapeHtml(w.created_at || '')}">
                                <i class="fa-regular fa-clock"></i> ${formatRelativeTime(w.created_at)}
                            </span>
                        </div>
                        <div class="wish-message-quote">
                            <i class="fa-solid fa-quote-left wish-quote-icon"></i>
                            ${escapeHtml(w.message)}
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            body.innerHTML = html;
        })
        .catch(() => {
            body.innerHTML = `
                <div style="text-align: center; padding: 24px; color: var(--danger); font-size: 12.5px;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 24px; margin-bottom: 6px; display: block;"></i>
                    Could not load greetings. Please try again.
                </div>
            `;
        });
}

function closeViewWishesModal() {
    document.getElementById('viewWishesModal').style.display = 'none';
}

function handleSendWishSubmit(e) {
    e.preventDefault();
    const form = document.getElementById('sendWishForm');
    const submitBtn = document.getElementById('wishSubmitBtn');
    const formData = new FormData(form);

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

    fetch('<?= url('celebrations/send-wish') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Greeting 🎉';

        if (data.success) {
            closeWishModal();

            // Update wish button on row
            const wishBtn = document.getElementById(`wishBtn_${data.receiver_id}_${data.type}`);
            if (wishBtn) {
                const wishedSpan = document.createElement('span');
                wishedSpan.className = 'c-pill-wished';
                wishedSpan.innerHTML = '<i class="fa-solid fa-check"></i> Wished';
                wishBtn.parentNode.replaceChild(wishedSpan, wishBtn);
            }

            // Update counter pill
            const counter = document.getElementById(`wishesCount_${data.receiver_id}_${data.type}`);
            if (counter) {
                counter.textContent = data.wishes_count;
            }

            showCelebrationToast(data.message || 'Greeting sent! 🎉');
        } else {
            alert(data.message || 'Error sending greeting.');
        }
    })
    .catch(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Greeting 🎉';
        alert('Network error while posting wishes.');
    });
}

function showCelebrationToast(msg) {
    const toast = document.createElement('div');
    toast.className = 'celebration-toast';
    toast.innerHTML = `<i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 6px;"></i> ${escapeHtml(msg)}`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.classList.add('show'); }, 50);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatRelativeTime(dateStr) {
    if (!dateStr) return 'Just now';
    const parsedStr = dateStr.replace(/-/g, '/');
    const d = new Date(parsedStr);
    const now = new Date();
    const diffSec = Math.floor((now - d) / 1000);
    if (diffSec < 60) return 'Just now';
    if (diffSec < 3600) return `${Math.floor(diffSec/60)}m ago`;
    if (diffSec < 86400) return `${Math.floor(diffSec/3600)}h ago`;
    if (diffSec < 172800) return 'Yesterday';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
}
</script>
