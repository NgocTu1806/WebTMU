<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-files text-primary me-2"></i>Tất cả tài liệu hệ thống</h4>
        <p class="text-muted small mb-0">Quản lý toàn bộ tài liệu đã đăng tải trên website</p>
    </div>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm p-3 mb-4">
    <form action="<?= url('/admin/documents') ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-4 col-lg-3">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm kiếm theo tiêu đề tài liệu..." value="<?= e($filters['query'] ?? '') ?>">
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <select name="subject_id" class="form-select form-select-sm">
                <option value="">-- Tất cả môn học --</option>
                <?php foreach ($subjects ?? [] as $subject): ?>
                    <option value="<?= (int)$subject['id'] ?>" <?= (int)($filters['subject_id'] ?? 0) === (int)$subject['id'] ? 'selected' : '' ?>>
                        <?= e($subject['name']) ?><?= !empty($subject['faculty_name']) ? ' (' . e($subject['faculty_name']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <select name="type_id" class="form-select form-select-sm">
                <option value="">-- Tất cả loại --</option>
                <?php foreach ($types ?? [] as $type): ?>
                    <option value="<?= (int)$type['id'] ?>" <?= (int)($filters['type_id'] ?? 0) === (int)$type['id'] ? 'selected' : '' ?>>
                        <?= e($type['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-6 col-lg-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Đã duyệt (Công khai)</option>
                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Bị từ chối</option>
                <option value="hidden" <?= ($filters['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Đã ẩn</option>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-6 col-lg-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-bold">
                <i class="bi bi-funnel me-1"></i>Lọc
            </button>
            <?php if (!empty($filters['query']) || !empty($filters['status']) || !empty($filters['subject_id']) || !empty($filters['type_id'])): ?>
                <a href="<?= url('/admin/documents') ?>" class="btn btn-outline-secondary btn-sm" title="Xóa bộ lọc">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tiêu đề</th>
                    <th>Người đăng</th>
                    <th>Môn học</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Lượt tải</th>
                    <th>Ngày đăng</th>
                    <th class="text-end">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($docs['items'])): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">Không tìm thấy tài liệu nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($docs['items'] as $doc): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/documents/' . $doc['id']) ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                    <?= e(truncate($doc['title'], 45)) ?>
                                </a>
                            </td>
                            <td><?= e($doc['uploader_name'] ?? '—') ?></td>
                            <td class="small text-muted"><?= e($doc['subject_name'] ?? '—') ?></td>
                            <td><span class="badge bg-light text-secondary border"><?= e($doc['type_name'] ?? 'Tài liệu') ?></span></td>
                            <td>
                                <?php $stInfo = documentStatusInfo((string)$doc['status']); ?>
                                <span class="badge <?= $stInfo['bs'] ?>"><?= e($stInfo['label']) ?></span>
                            </td>
                            <td><?= (int)$doc['download_count'] ?></td>
                            <td class="small text-muted"><?= formatDate($doc['created_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= url('/documents/' . $doc['id']) ?>" class="btn btn-sm btn-outline-info" title="Xem" target="_blank">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($doc['status'] === 'approved'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Ẩn tài liệu"
                                            data-bs-toggle="modal" data-bs-target="#hideDocModal-<?= (int)$doc['id'] ?>">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                <?php elseif ($doc['status'] === 'hidden'): ?>
                                    <form action="<?= url('/admin/documents/' . $doc['id'] . '/unhide') ?>" method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Hiện lại tài liệu">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (isAdmin()): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Xóa"
                                            data-bs-toggle="modal" data-bs-target="#deleteDocModal-<?= (int)$doc['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($docs['last_page'] > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $docs['last_page']; $i++): ?>
                <li class="page-item <?= $i === $docs['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('/admin/documents?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modals xác nhận ẩn tài liệu -->
<?php if (!empty($docs['items'])): ?>
    <?php foreach ($docs['items'] as $doc): ?>
        <?php if ($doc['status'] === 'approved'): ?>
            <div class="modal fade" id="hideDocModal-<?= (int)$doc['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-bottom bg-warning bg-opacity-10 py-3">
                            <h6 class="modal-title fw-bold text-warning-emphasis mb-0 d-flex align-items-center">
                                <i class="bi bi-eye-slash-fill fs-5 me-2"></i>Xác nhận ẩn tài liệu #<?= (int)$doc['id'] ?>
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="p-3 bg-warning bg-opacity-10 rounded-circle text-warning flex-shrink-0">
                                    <i class="bi bi-eye-slash fs-2"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark"><?= e($doc['title']) ?></h6>
                                    <div class="small text-muted mb-2">
                                        <span><i class="bi bi-person me-1"></i><?= e($doc['uploader_name'] ?? '—') ?></span> &bull;
                                        <span><i class="bi bi-book me-1"></i><?= e($doc['subject_name'] ?? '—') ?></span>
                                    </div>
                                    <span class="badge bg-light text-secondary border"><?= e($doc['type_name'] ?? 'Tài liệu') ?></span>
                                </div>
                            </div>

                            <div class="alert alert-warning d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-warning bg-opacity-10 text-warning-emphasis">
                                <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0"></i>
                                <div>
                                    Tài liệu sẽ tạm thời bị ẩn khỏi danh sách công khai và người dùng sẽ không thể xem hoặc tải xuống cho đến khi được hiện lại.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light py-2">
                            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                            <form action="<?= url('/admin/documents/' . $doc['id'] . '/hide') ?>" method="POST" class="d-inline mb-0">
                                <?= csrfField() ?>
                                <button type="submit" class="btn btn-warning btn-sm px-3 fw-semibold">
                                    <i class="bi bi-eye-slash-fill me-1"></i>Xác nhận ẩn
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modals xác nhận xóa tài liệu -->
<?php if (isAdmin() && !empty($docs['items'])): ?>
    <?php foreach ($docs['items'] as $doc): ?>
        <div class="modal fade" id="deleteDocModal-<?= (int)$doc['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom bg-danger bg-opacity-10 py-3">
                        <h6 class="modal-title fw-bold text-danger mb-0 d-flex align-items-center">
                            <i class="bi bi-trash3-fill fs-5 me-2"></i>Xác nhận xóa tài liệu #<?= (int)$doc['id'] ?>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="p-3 bg-danger bg-opacity-10 rounded-circle text-danger flex-shrink-0">
                                <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark"><?= e($doc['title']) ?></h6>
                                <div class="small text-muted mb-2">
                                    <span><i class="bi bi-person me-1"></i><?= e($doc['uploader_name'] ?? '—') ?></span> &bull;
                                    <span><i class="bi bi-book me-1"></i><?= e($doc['subject_name'] ?? '—') ?></span>
                                </div>
                                <span class="badge bg-light text-secondary border"><?= e($doc['type_name'] ?? 'Tài liệu') ?></span>
                            </div>
                        </div>

                        <div class="alert alert-danger d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-danger bg-opacity-10 text-danger-emphasis">
                            <i class="bi bi-shield-slash-fill fs-5 me-2 flex-shrink-0"></i>
                            <div>
                                <strong>Cảnh báo:</strong> Thao tác này sẽ xóa vĩnh viễn tệp tin và toàn bộ dữ liệu liên quan (lượt tải, bình luận, yêu thích) khỏi máy chủ và không thể khôi phục!
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light py-2">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                        <form action="<?= url('/admin/documents/' . $doc['id'] . '/delete') ?>" method="POST" class="d-inline mb-0">
                            <?= csrfField() ?>
                            <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                                <i class="bi bi-trash-fill me-1"></i>Xác nhận xóa vĩnh viễn
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

