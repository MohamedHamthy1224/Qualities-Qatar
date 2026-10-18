/**
 * QUALITIES Uniforms & Gifts ERP - Demo UI Helpers
 * Manages UI interactions: Clock, Appearance, Notifications, Quick Customer Modal, Toasts
 */
(function(window, document) {
    'use strict';

    // Appearance
    function initAppearance() {
        try {
            const saved = localStorage.getItem('qualities:appearance');
            if (saved === 'previous') {
                document.documentElement.dataset.appearance = 'previous';
            } else {
                document.documentElement.dataset.appearance = 'clean';
            }
        } catch {
            document.documentElement.dataset.appearance = 'clean';
        }

        const buttons = document.querySelectorAll('[data-appearance-choice]');
        const statusHint = document.querySelector('[data-appearance-status]');

        const updateButtons = (active) => {
            buttons.forEach(btn => {
                const isSelected = btn.dataset.appearanceChoice === active;
                btn.setAttribute('aria-pressed', String(isSelected));
            });
            if (statusHint) {
                statusHint.textContent = active === 'previous' ? 'Previous style is active.' : 'Clean style is active.';
            }
        };

        const current = document.documentElement.dataset.appearance || 'clean';
        updateButtons(current);

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const choice = btn.dataset.appearanceChoice;
                document.documentElement.dataset.appearance = choice;
                try { localStorage.setItem('qualities:appearance', choice); } catch {}
                updateButtons(choice);
            });
        });
    }

    // Doha Clock
    function initClock() {
        const timeEl = document.querySelector('[data-header-clock]');
        if (!timeEl) return;

        function update() {
            try {
                const now = new Date();
                const formatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: 'Asia/Qatar',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                timeEl.textContent = formatter.format(now);
            } catch (e) {
                timeEl.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
        }
        update();
        setInterval(update, 10000);
    }

    // Notifications
    function initNotifications() {
        const dot = document.querySelector('[data-header-notification-dot]');
        const list = document.querySelector('[data-header-notification-list]');
        const empty = document.querySelector('[data-header-notification-empty]');
        const clearBtn = document.querySelector('[data-header-notification-clear]');

        function renderNotices() {
            if (!window.demoStore) return;
            const notices = window.demoStore.getNotices();
            if (dot) dot.hidden = notices.length === 0;
            if (empty) empty.hidden = notices.length > 0;
            if (clearBtn) clearBtn.hidden = notices.length === 0;

            if (list) {
                list.hidden = notices.length === 0;
                list.innerHTML = '';
                notices.slice(0, 8).forEach(n => {
                    const li = document.createElement('li');
                    li.className = 'app-header-notice-item flex items-start gap-2 py-2 border-b border-slate-100 last:border-0 text-xs';
                    const iconColor = n.type === 'success' ? 'text-emerald-500' : (n.type === 'error' ? 'text-rose-500' : 'text-sky-500');
                    li.innerHTML = `
                        <span class="w-2 h-2 rounded-full mt-1 shrink-0 ${n.type === 'success' ? 'bg-emerald-500' : (n.type === 'error' ? 'bg-rose-500' : 'bg-sky-500')}"></span>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-800 flex items-center justify-between">
                                <span class="truncate">${n.title}</span>
                                <span class="text-[10px] text-slate-400 font-normal ml-2">${n.time || ''}</span>
                            </div>
                            <p class="text-slate-600 text-[11px] truncate">${n.message}</p>
                        </div>
                    `;
                    list.appendChild(li);
                });
            }
        }

        renderNotices();

        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (window.demoStore) window.demoStore.clearNotices();
                renderNotices();
            });
        }

        window.addEventListener('qualities:notice', () => {
            renderNotices();
        });
    }

    // Flash Toast / Notice
    window.showToast = function(type, title, message) {
        const container = document.getElementById('appFlashMessages');
        if (!container) return;

        const isSuccess = type === 'success';
        const isError = type === 'error';
        const colorClass = isSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : (isError ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-blue-50 border-blue-200 text-blue-800');
        const iconColor = isSuccess ? 'text-emerald-600' : (isError ? 'text-rose-600' : 'text-blue-600');

        const toast = document.createElement('div');
        toast.className = `mb-4 ${colorClass} border px-4 py-3 rounded-lg text-sm flex items-center justify-between shadow-xs transition-all duration-300`;
        toast.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 ${iconColor} flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${isSuccess ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>' :
                    (isError ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' :
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>')}
                </svg>
                <div>
                    <strong class="font-bold">${title}:</strong>
                    <span>${message}</span>
                </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-700 text-lg leading-none ml-3" aria-label="Dismiss">&times;</button>
        `;

        toast.querySelector('button').addEventListener('click', () => toast.remove());
        container.prepend(toast);

        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, 5000);
    };

    // Quick Customer Modal
    window.openQuickCustomerModal = function() {
        const dialog = document.getElementById('quickCustomerModal');
        if (dialog) {
            dialog.showModal();
            const input = document.getElementById('qc_name');
            if (input) input.focus();
        }
    };

    window.closeQuickCustomerModal = function() {
        const dialog = document.getElementById('quickCustomerModal');
        if (dialog) dialog.close();
    };

    function initQuickCustomerForm() {
        const dialog = document.getElementById('quickCustomerModal');
        const form = document.getElementById('quickCustomerForm');
        if (!dialog || !form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('qc_name')?.value.trim();
            const company = document.getElementById('qc_company_name')?.value.trim();
            const phone = document.getElementById('qc_phone')?.value.trim();
            const email = document.getElementById('qc_email')?.value.trim();
            const address = document.getElementById('qc_address')?.value.trim();

            if (!name) {
                alert('Please enter a customer or organization name.');
                return;
            }

            if (!window.demoStore) return;
            const newCust = window.demoStore.addCustomer({
                name: name,
                company_name: company,
                phone: phone,
                email: email,
                address: address
            });

            // Update any customer select elements on current page
            const selects = document.querySelectorAll('select[name="customer_id"]');
            selects.forEach(select => {
                const opt = new Option(`${newCust.name}${newCust.company_name ? ` (${newCust.company_name})` : ''} [${newCust.customer_code}]`, newCust.id, false, true);
                select.add(opt);
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });

            dialog.close();
            form.reset();
            window.showToast('success', 'Customer Added', `${newCust.name} (${newCust.customer_code})`);

            // If we are on customers page, trigger refresh
            if (window.onCustomerAdded) {
                window.onCustomerAdded(newCust);
            }
        });
    }

    // Sidebar Mobile Toggle
    function initSidebarToggle() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const nav = document.getElementById('sidebarNavigation');
        if (!toggleBtn || !nav) return;

        toggleBtn.addEventListener('click', () => {
            const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            toggleBtn.setAttribute('aria-expanded', String(!isExpanded));
            nav.classList.toggle('hidden', isExpanded);
        });
    }

    // Reset Demo Data
    window.resetDemoData = function() {
        if (confirm('Reset demo data to initial Qatar ERP showcase state? All your additions in this browser will be refreshed.')) {
            if (window.demoStore) {
                window.demoStore.resetData();
                window.location.reload();
            }
        }
    };

    // Close open details when clicking outside
    document.addEventListener('click', (e) => {
        document.querySelectorAll('details.app-header-dropdown[open]').forEach(details => {
            if (!details.contains(e.target)) {
                details.removeAttribute('open');
            }
        });
    });

    // Run on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        initAppearance();
        initClock();
        initNotifications();
        initSidebarToggle();
        initQuickCustomerForm();
    });

})(window, document);
