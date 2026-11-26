<?php declare(strict_types=1); ?>
<?php
$search = trim((string)($filters['search'] ?? $_GET['search'] ?? ''));
$role   = trim((string)($filters['role'] ?? $_GET['role'] ?? ''));
$status = trim((string)($filters['status'] ?? $_GET['status'] ?? ''));
$stats  = $stats ?? [];
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-people-fill text-primary me-2"></i>Quản lý người dùng
            </h3>
            <p class="text-muted small mb-0">
                Quản lý tài khoản sinh viên, phân quyền kiểm duyệt và trạng thái hoạt động
            </p>
        </div>
    </div>

    <!-- Thống kê nhanh -->
    <?php if (!empty($stats)): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <div class="text-muted small">Tổng tài khoản</div>
                    <div class="fs-4 fw-bold text-primary"><?= (int)($stats['total'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <div class="text-muted small">Đang hoạt động</div>
                    <div class="fs-4 fw-bold text-success"><?= (int)($stats['active'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= url('/admin/users?status=locked') ?>" class="card border-0 shadow-sm p-3 text-decoration-none d-block">
                    <div class="text-muted small">Đã bị khóa</div>
                    <div class="fs-4 fw-bold text-danger"><?= (int)($stats['locked'] ?? 0) ?></div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3">
                    <div class="text-muted small">Mới trong tháng</div>
                    <div class="fs-4 fw-bold text-info"><?= (int)($stats['this_month'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bộ lọc tìm kiếm -->
    <div class="card border-0 shadow-sm p-3 mb-4">
        <form action="<?= url('/admin/users') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Tìm kiếm</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       value="<?= e($search) ?>" placeholder="Họ tên, email hoặc tên đăng nhập...">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Vai trò</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">-- Tất cả vai trò --</option>
                    <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>Thành viên (Member)</option>
                    <option value="moderator" <?= $role === 'moderator' ? 'selected' : '' ?>>Kiểm duyệt viên (Moderator)</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Trạng thái</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="locked" <?= $status === 'locked' ? 'selected' : '' ?>>Bị khóa</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i>Lọc
                </button>
                <?php if ($search !== '' || $role !== '' || $status !== ''): ?>
                    <a href="<?= url('/admin/users') ?>" class="btn btn-outline-secondary btn-sm" title="Xóa bộ lọc">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Bảng danh sách người dùng -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Người dùng</th>
                        <th>Mã SV</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users['items'])): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Không tìm thấy tài khoản nào phù hợp.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users['items'] as $u): ?>
                            <?php
                                $uid       = (int)$u['id'];
                                $isSelf    = ($uid === userId());
                                $isLocked  = ($u['status'] ?? '') === 'locked';
                                $avatarSrc = avatarUrl($uid, $u['avatar'] ?? null);
                            ?>
                            <tr>
                                <td class="text-muted small">#<?= $uid ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($avatarSrc): ?>
                                            <img src="<?= e($avatarSrc) ?>" class="rounded-circle" width="36" height="36"
                                                 style="object-fit: cover;" alt="<?= e($u['full_name']) ?>">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold"
                                                 style="width: 36px; height: 36px; font-size: 0.9rem;">
                                                <?= mb_substr((string)$u['full_name'], 0, 1) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold small">
                                                <?= e($u['full_name']) ?>
                                                <?php if ($isSelf): ?>
                                                    <span class="badge bg-info text-dark ms-1">Bạn</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                @<?= e($u['username']) ?> &bull; <?= e($u['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small fw-semibold"><?= e($u['student_id'] ?: '—') ?></td>
                                <td>
                                    <?php if (!$isSelf && isAdmin()): ?>
                                        <form action="<?= url('/admin/users/' . $uid . '/role') ?>" method="POST" class="d-inline-flex gap-1">
                                            <?= csrfField() ?>
                                            <select name="role" class="form-select form-select-sm" style="width: auto;"
                                                    onchange="this.form.submit()">
                                                <option value="member" <?= ($u['role'] ?? '') === 'member' ? 'selected' : '' ?>>Member</option>
                                                <option value="moderator" <?= ($u['role'] ?? '') === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                                                <option value="admin" <?= ($u['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><?= e(strtoupper((string)$u['role'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $isLocked ? 'danger' : 'success' ?>">
                                        <?= $isLocked ? 'Bị khóa' : 'Hoạt động' ?>
                                    </span>
                                    <?php if ($isLocked): ?>
                                        <div class="small text-muted mt-1" style="max-width: 220px;">
                                            <?php if (!empty($u['ban_expires_at'])): ?>
                                                <div><i class="bi bi-clock me-1"></i>Đến <?= formatDate((string)$u['ban_expires_at'], 'H:i d/m/Y') ?></div>
                                            <?php else: ?>
                                                <div><i class="bi bi-infinity me-1"></i>Khóa vĩnh viễn</div>
                                            <?php endif; ?>
                                            <?php if (!empty($u['ban_reason'])): ?>
                                                <div class="text-truncate text-danger" title="<?= e((string)$u['ban_reason']) ?>">
                                                    <?= e((string)$u['ban_reason']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= formatDate((string)$u['created_at']) ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= url('/admin/users/' . $uid) ?>" class="btn btn-sm btn-outline-secondary me-1" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!$isSelf): ?>
                                        <?php if ($isLocked): ?>
                                            <form action="<?= url('/admin/users/' . $uid . '/toggle') ?>"
                                                  method="POST" class="d-inline">
                                                <?= csrfField() ?>
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-success"
                                                        data-confirm="Mở khóa tài khoản này? Hồ sơ khóa sẽ bị xóa.">
                                                    <i class="bi bi-unlock me-1"></i>Mở khóa
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-open-ban-modal"
                                                    data-user-id="<?= $uid ?>"
                                                    data-user-name="<?= e((string)$u['full_name']) ?>"
                                                    data-action="<?= url('/admin/users/' . $uid . '/toggle') ?>">
                                                <i class="bi bi-lock me-1"></i>Khóa
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Phân trang -->
    <?php if (($users['last_page'] ?? 1) > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $users['last_page']; $i++): ?>
                    <?php
                        $query = http_build_query(array_filter([
                            'search' => $search,
                            'role'   => $role,
                            'status' => $status,
                            'page'   => $i,
                        ]));
                    ?>
                    <li class="page-item <?= $i === ($users['current_page'] ?? 1) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('/admin/users?' . $query) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Modal khóa tài khoản (user_bans 1-1) -->
<div class="modal fade" id="banUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="banUserForm" class="modal-content border-0 shadow">
            <?= csrfField() ?>
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="bi bi-shield-lock me-2"></i>Khóa tài khoản: <span id="banModalUserName"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="banDurationSelect" class="form-label fw-semibold small">Thời hạn khóa</label>
                    <select name="duration" id="banDurationSelect" class="form-select form-select-sm">
                        <option value="1_day">1 ngày</option>
                        <option value="3_days">3 ngày</option>
                        <option value="7_days" selected>7 ngày</option>
                        <option value="30_days">30 ngày</option>
                        <option value="permanent">Vĩnh viễn</option>
                        <option value="custom">Tùy chỉnh ngày giờ...</option>
                    </select>
                </div>
                <div class="mb-3 d-none" id="banCustomExpiryGroup">
                    <label for="banCustomExpiry" class="form-label fw-semibold small">Mở khóa vào lúc</label>
                    <input type="datetime-local" name="expires_at" id="banCustomExpiry" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label for="banReasonInput" class="form-label fw-semibold small">
                        Lý do khóa <span class="text-danger">*</span>
                    </label>
                    <textarea name="reason" id="banReasonInput" rows="3" class="form-control form-control-sm"
                              required maxlength="1000"
                              placeholder="Nhập lý do vi phạm (sẽ hiển thị cho người dùng khi đăng nhập)..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-danger btn-sm fw-semibold">
                    <i class="bi bi-lock-fill me-1"></i>Xác nhận khóa
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('banUserModal');
    const formEl = document.getElementById('banUserForm');
    const nameEl = document.getElementById('banModalUserName');
    const durationEl = document.getElementById('banDurationSelect');
    const customGroup = document.getElementById('banCustomExpiryGroup');
    const customInput = document.getElementById('banCustomExpiry');
    const reasonInput = document.getElementById('banReasonInput');

    if (!modalEl || typeof bootstrap === 'undefined') return;
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

    document.querySelectorAll('.btn-open-ban-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            formEl.action = this.dataset.action || '';
            nameEl.textContent = this.dataset.userName || '';
            reasonInput.value = '';
            durationEl.value = '7_days';
            customGroup.classList.add('d-none');
            customInput.required = false;
            customInput.value = '';
            bsModal.show();
        });
    });

    durationEl.addEventListener('change', function () {
        const isCustom = this.value === 'custom';
        customGroup.classList.toggle('d-none', !isCustom);
        customInput.required = isCustom;
    });
});
</script>