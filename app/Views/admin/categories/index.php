<?php declare(strict_types=1); ?>
<?php
$faculties = $faculties ?? [];
$subjects  = $subjects ?? [];
$types     = $types ?? ($documentTypes ?? []);
?>
<div class="container-fluid">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">
            <i class="bi bi-diagram-3-fill text-primary me-2"></i>Quản lý Khoa / Môn học / Loại tài liệu
        </h4>
        <p class="text-muted small mb-0">Cấu hình danh mục đào tạo và phân loại học liệu trên toàn hệ thống</p>
    </div>

    <!-- Tabs chuyển đổi 3 danh mục -->
    <ul class="nav nav-tabs mb-4" id="categoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="faculties-tab" data-bs-toggle="tab" data-bs-target="#faculties-pane" type="button" role="tab">
                <i class="bi bi-building me-1"></i>Khoa / Viện (<?= count($faculties) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="subjects-tab" data-bs-toggle="tab" data-bs-target="#subjects-pane" type="button" role="tab">
                <i class="bi bi-book me-1"></i>Môn học (<?= count($subjects) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="types-tab" data-bs-toggle="tab" data-bs-target="#types-pane" type="button" role="tab">
                <i class="bi bi-tags me-1"></i>Loại tài liệu (<?= count($types) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="categoryTabsContent">
        <!-- TAB 1: KHOA / VIỆN -->
        <div class="tab-pane fade show active" id="faculties-pane" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-primary me-1"></i>Thêm Khoa / Viện mới</h6>
                        <form action="<?= url('/admin/faculties') ?>" method="POST">
                            <?= csrfField() ?>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Tên Khoa / Viện <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" required placeholder="VD: Khoa Hệ thống thông tin kinh tế và TMĐT">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Mã khoa</label>
                                <input type="text" name="code" class="form-control form-control-sm text-uppercase" placeholder="VD: HTTTKT">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Mô tả</label>
                                <textarea name="description" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">Thêm Khoa</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên Khoa / Viện</th>
                                        <th>Mã</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($faculties)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có dữ liệu Khoa.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($faculties as $f): ?>
                                            <?php $fid = (int)$f['id']; $fActive = !empty($f['is_active']); ?>
                                            <tr>
                                                <td class="text-muted">#<?= $fid ?></td>
                                                <td class="fw-semibold"><?= e($f['name']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= e($f['code'] ?? '—') ?></span></td>
                                                <td>
                                                    <span class="badge bg-<?= $fActive ? 'success' : 'secondary' ?>">
                                                        <?= $fActive ? 'Hiển thị' : 'Đang ẩn' ?>
                                                    </span>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2"
                                                            data-bs-toggle="modal" data-bs-target="#editFaculty-<?= $fid ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form action="<?= url('/admin/faculties/' . $fid . '/toggle') ?>" method="POST" class="d-inline">
                                                        <?= csrfField() ?>
                                                        <button type="submit" class="btn btn-xs btn-outline-warning py-0 px-2" title="Bật/Tắt">
                                                            <i class="bi bi-eye<?= $fActive ? '-slash' : '' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2"
                                                            data-bs-toggle="modal" data-bs-target="#deleteFacultyModal-<?= $fid ?>" title="Xóa Khoa">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: MÔN HỌC -->
        <div class="tab-pane fade" id="subjects-pane" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-primary me-1"></i>Thêm Môn học mới</h6>
                        <form action="<?= url('/admin/subjects') ?>" method="POST">
                            <?= csrfField() ?>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Thuộc Khoa / Viện <span class="text-danger">*</span></label>
                                <select name="faculty_id" class="form-select form-select-sm" required>
                                    <option value="">-- Chọn Khoa --</option>
                                    <?php foreach ($faculties as $f): ?>
                                        <option value="<?= (int)$f['id'] ?>"><?= e($f['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Tên môn học <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" required placeholder="VD: Phân tích thiết kế hệ thống thông tin">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Mã học phần</label>
                                <input type="text" name="code" class="form-control form-control-sm text-uppercase" placeholder="VD: ISYS2111">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">Thêm Môn học</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên môn học</th>
                                        <th>Khoa / Viện</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($subjects)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có dữ liệu Môn học.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($subjects as $s): ?>
                                            <?php $sid = (int)$s['id']; $sActive = !empty($s['is_active']); ?>
                                            <tr>
                                                <td class="text-muted">#<?= $sid ?></td>
                                                <td>
                                                    <div class="fw-semibold"><?= e($s['name']) ?></div>
                                                    <?php if (!empty($s['code'])): ?>
                                                        <span class="text-muted" style="font-size: 0.72rem;">Mã HP: <?= e($s['code']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted"><?= e($s['faculty_name'] ?? '—') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $sActive ? 'success' : 'secondary' ?>">
                                                        <?= $sActive ? 'Hiển thị' : 'Đang ẩn' ?>
                                                    </span>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2"
                                                            data-bs-toggle="modal" data-bs-target="#editSubject-<?= $sid ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form action="<?= url('/admin/subjects/' . $sid . '/toggle') ?>" method="POST" class="d-inline">
                                                        <?= csrfField() ?>
                                                        <button type="submit" class="btn btn-xs btn-outline-warning py-0 px-2" title="Bật/Tắt">
                                                            <i class="bi bi-eye<?= $sActive ? '-slash' : '' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2"
                                                            data-bs-toggle="modal" data-bs-target="#deleteSubjectModal-<?= $sid ?>" title="Xóa Môn học">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: LOẠI TÀI LIỆU -->
        <div class="tab-pane fade" id="types-pane" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-primary me-1"></i>Thêm Loại tài liệu</h6>
                        <form action="<?= url('/admin/document-types') ?>" method="POST">
                            <?= csrfField() ?>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Tên loại tài liệu <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" required placeholder="VD: Đề thi giữa kỳ / Slide bài giảng">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Mô tả</label>
                                <textarea name="description" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">Thêm Loại tài liệu</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên loại tài liệu</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($types)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">Chưa có dữ liệu Loại tài liệu.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($types as $t): ?>
                                            <?php $tid = (int)$t['id']; $tActive = !empty($t['is_active']); ?>
                                            <tr>
                                                <td class="text-muted">#<?= $tid ?></td>
                                                <td class="fw-semibold"><?= e($t['name']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $tActive ? 'success' : 'secondary' ?>">
                                                        <?= $tActive ? 'Hiển thị' : 'Đang ẩn' ?>
                                                    </span>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2"
                                                            data-bs-toggle="modal" data-bs-target="#editType-<?= $tid ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form action="<?= url('/admin/document-types/' . $tid . '/toggle') ?>" method="POST" class="d-inline">
                                                        <?= csrfField() ?>
                                                        <button type="submit" class="btn btn-xs btn-outline-warning py-0 px-2" title="Bật/Tắt">
                                                            <i class="bi bi-eye<?= $tActive ? '-slash' : '' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2"
                                                            data-bs-toggle="modal" data-bs-target="#deleteTypeModal-<?= $tid ?>" title="Xóa Loại tài liệu">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals chỉnh sửa Khoa / Môn / Loại -->
<?php foreach ($faculties as $f): $fid = (int)$f['id']; ?>
    <div class="modal fade" id="editFaculty-<?= $fid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= url('/admin/faculties/' . $fid . '/update') ?>" method="POST" class="modal-content">
                <?= csrfField() ?>
                <div class="modal-header"><h6 class="modal-title fw-bold">Sửa Khoa / Viện</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small fw-semibold">Tên Khoa</label><input type="text" name="name" class="form-control form-control-sm" value="<?= e($f['name']) ?>" required></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Mã khoa</label><input type="text" name="code" class="form-control form-control-sm" value="<?= e($f['code'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Mô tả</label><textarea name="description" class="form-control form-control-sm" rows="2"><?= e($f['description'] ?? '') ?></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button></div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php foreach ($subjects as $s): $sid = (int)$s['id']; ?>
    <div class="modal fade" id="editSubject-<?= $sid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= url('/admin/subjects/' . $sid . '/update') ?>" method="POST" class="modal-content">
                <?= csrfField() ?>
                <div class="modal-header"><h6 class="modal-title fw-bold">Sửa Môn học</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Thuộc Khoa</label>
                        <select name="faculty_id" class="form-select form-select-sm" required>
                            <?php foreach ($faculties as $f): ?>
                                <option value="<?= (int)$f['id'] ?>" <?= (int)($s['faculty_id'] ?? 0) === (int)$f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Tên môn học</label><input type="text" name="name" class="form-control form-control-sm" value="<?= e($s['name']) ?>" required></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Mã học phần</label><input type="text" name="code" class="form-control form-control-sm" value="<?= e($s['code'] ?? '') ?>"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button></div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php foreach ($types as $t): $tid = (int)$t['id']; ?>
    <div class="modal fade" id="editType-<?= $tid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= url('/admin/document-types/' . $tid . '/update') ?>" method="POST" class="modal-content">
                <?= csrfField() ?>
                <div class="modal-header"><h6 class="modal-title fw-bold">Sửa Loại tài liệu</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small fw-semibold">Tên loại</label><input type="text" name="name" class="form-control form-control-sm" value="<?= e($t['name']) ?>" required></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Mô tả</label><textarea name="description" class="form-control form-control-sm" rows="2"><?= e($t['description'] ?? '') ?></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button></div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<!-- ================= MODALS XÁC NHẬN XÓA ================= -->
<!-- Xóa Khoa / Viện -->
<?php foreach ($faculties as $f): $fid = (int)$f['id']; ?>
    <div class="modal fade" id="deleteFacultyModal-<?= $fid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom bg-danger bg-opacity-10 py-3">
                    <h6 class="modal-title fw-bold text-danger mb-0 d-flex align-items-center">
                        <i class="bi bi-trash3-fill fs-5 me-2"></i>Xác nhận xóa Khoa / Viện
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="p-3 bg-danger bg-opacity-10 rounded-circle text-danger flex-shrink-0">
                            <i class="bi bi-building fs-2"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark"><?= e($f['name']) ?></h6>
                            <?php if (!empty($f['code'])): ?>
                                <span class="badge bg-light text-secondary border">Mã khoa: <?= e($f['code']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="alert alert-danger d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-danger bg-opacity-10 text-danger-emphasis">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                        <div>
                            <strong>Lưu ý:</strong> Bạn chỉ có thể xóa Khoa / Viện này nếu hiện tại không còn môn học nào thuộc khoa quản lý.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                    <form action="<?= url('/admin/faculties/' . $fid . '/delete') ?>" method="POST" class="d-inline mb-0">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                            <i class="bi bi-trash-fill me-1"></i>Xác nhận xóa Khoa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Xóa Môn học -->
<?php foreach ($subjects as $s): $sid = (int)$s['id']; ?>
    <div class="modal fade" id="deleteSubjectModal-<?= $sid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom bg-danger bg-opacity-10 py-3">
                    <h6 class="modal-title fw-bold text-danger mb-0 d-flex align-items-center">
                        <i class="bi bi-trash3-fill fs-5 me-2"></i>Xác nhận xóa Môn học
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="p-3 bg-danger bg-opacity-10 rounded-circle text-danger flex-shrink-0">
                            <i class="bi bi-book fs-2"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark"><?= e($s['name']) ?></h6>
                            <div class="small text-muted mb-1">
                                <span>Khoa: <?= e($s['faculty_name'] ?? '—') ?></span>
                                <?php if (!empty($s['code'])): ?>
                                    &bull; <span>Mã HP: <?= e($s['code']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-danger d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-danger bg-opacity-10 text-danger-emphasis">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                        <div>
                            <strong>Lưu ý:</strong> Bạn chỉ có thể xóa môn học này nếu hiện tại chưa có tài liệu nào thuộc môn học.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                    <form action="<?= url('/admin/subjects/' . $sid . '/delete') ?>" method="POST" class="d-inline mb-0">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                            <i class="bi bi-trash-fill me-1"></i>Xác nhận xóa Môn học
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Xóa Loại tài liệu -->
<?php foreach ($types as $t): $tid = (int)$t['id']; ?>
    <div class="modal fade" id="deleteTypeModal-<?= $tid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom bg-danger bg-opacity-10 py-3">
                    <h6 class="modal-title fw-bold text-danger mb-0 d-flex align-items-center">
                        <i class="bi bi-trash3-fill fs-5 me-2"></i>Xác nhận xóa Loại tài liệu
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="p-3 bg-danger bg-opacity-10 rounded-circle text-danger flex-shrink-0">
                            <i class="bi bi-tags fs-2"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark"><?= e($t['name']) ?></h6>
                            <?php if (!empty($t['description'])): ?>
                                <div class="small text-muted"><?= e($t['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="alert alert-danger d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-danger bg-opacity-10 text-danger-emphasis">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                        <div>
                            <strong>Lưu ý:</strong> Bạn chỉ có thể xóa loại tài liệu này nếu hiện tại chưa có tài liệu nào đang sử dụng loại này.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                    <form action="<?= url('/admin/document-types/' . $tid . '/delete') ?>" method="POST" class="d-inline mb-0">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                            <i class="bi bi-trash-fill me-1"></i>Xác nhận xóa Loại
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        const tabTrigger = document.querySelector(`button[data-bs-target="#${hash}-pane"]`);
        if (tabTrigger && typeof bootstrap !== 'undefined') {
            bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
        }
    }
});
</script>