<?php declare(strict_types=1); ?>
<?php
$uid = (int)$viewUser['id'];
$isSelf = ($uid === userId());
$isLocked = ($viewUser['status'] ?? '') === 'locked';
?>
<div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <a href="<?= url('/admin/users') ?>" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
        </a>
        <h4 class="fw-bold mb-0">Chi tiết người dùng: <?= e($viewUser['full_name']) ?></h4>
    </div>
    <?php if (!$isSelf): ?>
        <?php if ($isLocked): ?>
            <form action="<?= url('/admin/users/' . $uid . '/toggle') ?>" method="POST" class="mb-0">
                <?= csrfField() ?>
                <input type="hidden" name="redirect_to" value="show">
                <button type="submit" class="btn btn-success btn-sm"
                        data-confirm="Mở khóa tài khoản này? Hồ sơ khóa sẽ bị xóa.">
                    <i class="bi bi-unlock me-1"></i>Mở khóa tài khoản
                </button>
            </form>
        <?php else: ?>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#banDetailModal">
                <i class="bi bi-lock me-1"></i>Khóa tài khoản
            </button>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($isLocked): ?>
    <div class="alert alert-danger alert-permanent border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-shield-exclamation fs-3 text-danger"></i>
            <div class="flex-grow-1">
                <h6 class="fw-bold mb-1">Tài khoản đang bị khóa</h6>
                <?php if (!empty($banInfo)): ?>
                    <div class="small mb-1">
                        <strong>Lý do:</strong> <?= nl2br(e((string)$banInfo['reason'])) ?>
                    </div>
                    <div class="small text-muted">
                        <strong>Người khóa:</strong> <?= e((string)($banInfo['banned_by_name'] ?? 'Quản trị viên')) ?>
                        &bull;
                        <strong>Thời điểm khóa:</strong> <?= formatDate((string)$banInfo['banned_at'], 'H:i d/m/Y') ?>
                        &bull;
                        <strong>Thời hạn:</strong>
                        <?= !empty($banInfo['expires_at']) ? 'Đến ' . formatDate((string)$banInfo['expires_at'], 'H:i d/m/Y') : 'Vĩnh viễn' ?>
                    </div>
                <?php else: ?>
                    <div class="small">Chưa có chi tiết trong bảng <code>user_bans</code>.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm p-4">
    <div class="row">
        <div class="col-md-6 mb-3">
            <span class="text-muted small">Họ và tên:</span>
            <div class="fw-bold fs-5"><?= e($viewUser['full_name']) ?></div>
        </div>
        <div class="col-md-6 mb-3">
            <span class="text-muted small">Tên đăng nhập:</span>
            <div class="fw-bold">@<?= e($viewUser['username']) ?></div>
        </div>
        <div class="col-md-6 mb-3">
            <span class="text-muted small">Email:</span>
            <div><?= e($viewUser['email']) ?></div>
        </div>
        <div class="col-md-6 mb-3">
            <span class="text-muted small">Mã sinh viên:</span>
            <div><?= e($viewUser['student_id'] ?: 'Chưa cập nhật') ?></div>
        </div>
        <div class="col-md-6 mb-3">
            <span class="text-muted small">Vai trò hiện tại:</span>
            <div><span class="badge bg-primary"><?= e(strtoupper($viewUser['role'])) ?></span></div>
        </div>
        <div class="col-md-6 mb-3">
            <span class="text-muted small">Trạng thái:</span>
            <div>
                <span class="badge bg-<?= $viewUser['status'] === 'active' ? 'success' : 'danger' ?>">
                    <?= $viewUser['status'] === 'active' ? 'Hoạt động' : 'Bị khóa' ?>
                </span>
            </div>
        </div>
        <div class="col-md-12 mb-3">
            <span class="text-muted small">Tiểu sử:</span>
            <div class="bg-light p-2 rounded"><?= nl2br(e($viewUser['bio'] ?: 'Chưa có tiểu sử.')) ?></div>
        </div>
        <div class="col-md-6">
            <span class="text-muted small">Ngày tham gia:</span>
            <div><?= formatDate($viewUser['created_at'], 'H:i d/m/Y') ?></div>
        </div>
        <div class="col-md-6">
            <span class="text-muted small">Đăng nhập lần cuối:</span>
            <div><?= $viewUser['last_login'] ? formatDate($viewUser['last_login'], 'H:i d/m/Y') : 'Chưa đăng nhập' ?></div>
        </div>
    </div>
</div>

<?php if (!$isSelf && !$isLocked): ?>
<div class="modal fade" id="banDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= url('/admin/users/' . $uid . '/toggle') ?>" class="modal-content border-0 shadow">
            <?= csrfField() ?>
            <input type="hidden" name="redirect_to" value="show">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="bi bi-shield-lock me-2"></i>Khóa tài khoản: <?= e($viewUser['full_name']) ?>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="detailBanDuration" class="form-label fw-semibold small">Thời hạn khóa</label>
                    <select name="duration" id="detailBanDuration" class="form-select form-select-sm"
                            onchange="const g=document.getElementById('detailCustomExpiryGroup');const i=document.getElementById('detailCustomExpiry');const c=this.value==='custom';g.classList.toggle('d-none',!c);i.required=c;">
                        <option value="1_day">1 ngày</option>
                        <option value="3_days">3 ngày</option>
                        <option value="7_days" selected>7 ngày</option>
                        <option value="30_days">30 ngày</option>
                        <option value="permanent">Vĩnh viễn</option>
                        <option value="custom">Tùy chỉnh ngày giờ...</option>
                    </select>
                </div>
                <div class="mb-3 d-none" id="detailCustomExpiryGroup">
                    <label for="detailCustomExpiry" class="form-label fw-semibold small">Mở khóa vào lúc</label>
                    <input type="datetime-local" name="expires_at" id="detailCustomExpiry" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label for="detailBanReason" class="form-label fw-semibold small">
                        Lý do khóa <span class="text-danger">*</span>
                    </label>
                    <textarea name="reason" id="detailBanReason" rows="3" class="form-control form-control-sm"
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
<?php endif; ?>

