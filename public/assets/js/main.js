/* TMU Tài Liệu - Main JavaScript */

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Bootstrap Modal for [data-confirm] actions
    let confirmModalEl = document.getElementById('globalConfirmModal');
    if (!confirmModalEl) {
        confirmModalEl = document.createElement('div');
        confirmModalEl.id = 'globalConfirmModal';
        confirmModalEl.className = 'modal fade';
        confirmModalEl.setAttribute('tabindex', '-1');
        confirmModalEl.setAttribute('aria-hidden', 'true');
        confirmModalEl.innerHTML = `
            <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom py-3 bg-light">
                        <h6 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-question-circle-fill text-warning me-2 fs-5"></i>Xác nhận thao tác
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <p class="mb-0 text-secondary fs-6" id="globalConfirmMessage">Bạn có chắc chắn muốn thực hiện hành động này?</p>
                    </div>
                    <div class="modal-footer border-top bg-light py-2 justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger btn-sm px-4 fw-semibold" id="globalConfirmBtn">Xác nhận</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(confirmModalEl);
    }

    const bsConfirmModal = typeof bootstrap !== 'undefined' ? bootstrap.Modal.getOrCreateInstance(confirmModalEl) : null;
    let pendingAction = null;

    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-confirm]');
        if (!target) return;

        e.preventDefault();
        e.stopPropagation();

        const message = target.dataset.confirm || 'Bạn có chắc chắn muốn thực hiện hành động này?';
        const msgEl = document.getElementById('globalConfirmMessage');
        if (msgEl) msgEl.textContent = message;

        pendingAction = target;
        if (bsConfirmModal) {
            bsConfirmModal.show();
        } else if (confirm(message)) {
            if (target.form) {
                target.form.submit();
            } else if (target.href) {
                window.location.href = target.href;
            }
        }
    });

    const confirmBtn = document.getElementById('globalConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (bsConfirmModal) bsConfirmModal.hide();
            if (pendingAction) {
                const target = pendingAction;
                pendingAction = null;
                if (target.tagName === 'BUTTON' && target.type === 'submit' && target.form) {
                    target.form.submit();
                } else if (target.tagName === 'A' && target.href) {
                    window.location.href = target.href;
                } else if (target.form) {
                    target.form.submit();
                }
            }
        });
    }

    // Favorite toggle (AJAX)
    document.querySelectorAll('.btn-favorite').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const docId = this.dataset.id;
            const icon = this.querySelector('i');
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.content : '';

            fetch(url('documents/' + docId + '/favorite'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_csrf_token=' + encodeURIComponent(csrf)
            })
            .then(async r => {
                if (r.redirected) {
                    window.location.href = r.url;
                    return null;
                }
                const data = await r.json();
                if (!r.ok) {
                    throw new Error(data.error || 'Không thể lưu tài liệu.');
                }
                return data;
            })
            .then(data => {
                if (!data) return;
                const labelEl = this.querySelector('.favorite-label');
                if (labelEl) {
                    icon.className = (data.favorited ? 'bi bi-bookmark-heart-fill' : 'bi bi-bookmark-heart') + ' me-1';
                    labelEl.textContent = data.favorited ? 'Đã lưu' : 'Lưu tài liệu';
                } else {
                    icon.className = data.favorited ? 'bi bi-bookmark-heart-fill text-danger' : 'bi bi-bookmark-heart';
                }
                this.title = data.favorited ? 'Bỏ lưu' : 'Lưu tài liệu';
            })
            .catch(console.error);
        });
    });

    // Scroll Reveal Animations
    if ('IntersectionObserver' in window) {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px 0px -30px 0px'
        });

        const revealTargets = document.querySelectorAll(
            '.document-card, .hero-section .banner-card, .card:not(.ai-chat-window):not(.admin-wrapper .card), .stat-card, .forum-card'
        );

        revealTargets.forEach(el => {
            el.classList.add('scroll-reveal');
            
            // Staggered delay for items inside a .row grid
            const parentRow = el.closest('.row');
            if (parentRow) {
                const siblings = Array.from(parentRow.querySelectorAll('.scroll-reveal'));
                const siblingIndex = siblings.indexOf(el);
                if (siblingIndex > 0) {
                    const delayNum = (siblingIndex % 4) + 1;
                    el.classList.add('stagger-delay-' + delayNum);
                }
            }

            if (prefersReducedMotion) {
                el.classList.add('is-revealed');
            } else {
                revealObserver.observe(el);
            }
        });
    }
});

// Helper for relative URL matching PHP's url()
function url(path) {
    const base = window.location.pathname.includes('/public')
        ? window.location.origin + window.location.pathname.substring(0, window.location.pathname.indexOf('/public') + 7)
        : window.location.origin;
    return base.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
}

// Mobile Responsive Drawer Navigation
document.addEventListener('DOMContentLoaded', function() {
    const openBtn    = document.getElementById('mobileMenuOpenBtn');
    const drawer     = document.getElementById('mobileNavDrawer');
    const backdrop   = document.getElementById('mobileNavBackdrop');
    const closeBtn   = document.getElementById('mobileMenuCloseBtn');
    const closeBtnSub = document.getElementById('mobileMenuCloseBtnSub');
    const backBtn    = document.getElementById('mobileMenuBackBtn');
    const userTrigger = document.getElementById('mobileUserTrigger');
    const panels     = document.getElementById('mobileDrawerPanels');
    const headerMain = document.getElementById('mobileHeaderMain');
    const headerSub  = document.getElementById('mobileHeaderSub');

    if (!openBtn || !drawer || !backdrop) return;

    function showMainLevel() {
        if (panels) panels.classList.remove('show-sub');
        if (headerMain) {
            headerMain.classList.remove('d-none');
            headerMain.classList.add('d-flex');
        }
        if (headerSub) {
            headerSub.classList.add('d-none');
            headerSub.classList.remove('d-flex');
        }
    }

    function showSubLevel() {
        if (panels) panels.classList.add('show-sub');
        if (headerMain) {
            headerMain.classList.add('d-none');
            headerMain.classList.remove('d-flex');
        }
        if (headerSub) {
            headerSub.classList.remove('d-none');
            headerSub.classList.add('d-flex');
        }
    }

    function openDrawer() {
        showMainLevel();
        drawer.classList.add('show');
        backdrop.classList.add('show');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-drawer-open');
    }

    function closeDrawer() {
        drawer.classList.remove('show');
        backdrop.classList.remove('show');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-drawer-open');
    }

    openBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (closeBtnSub) closeBtnSub.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    if (userTrigger) {
        userTrigger.addEventListener('click', showSubLevel);
    }
    if (backBtn) {
        backBtn.addEventListener('click', showMainLevel);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && drawer.classList.contains('show')) {
            closeDrawer();
        }
    });
});

