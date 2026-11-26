<?php declare(strict_types=1); ?>
<?php
$isEdit      = !empty($doc);
$valTitle    = old('title', $doc['title'] ?? '');
$valFaculty  = old('faculty_id', $doc['faculty_id'] ?? '');
$valSubject  = old('subject_id', $doc['subject_id'] ?? '');
$valType     = old('document_type_id', $doc['document_type_id'] ?? '');
$valStatus   = old('status', $doc['status'] ?? 'approved');
$valReason   = old('rejection_reason', $doc['rejection_reason'] ?? '');
$valDesc     = old('description', $doc['description'] ?? '');
?>

<form action="<?= e($actionUrl) ?>" method="POST" enctype="multipart/form-data" novalidate>
    <?= csrfField() ?>

    <div class="mb-3">
        <label for="admin_doc_title" class="form-label fw-semibold">
            Tên tài liệu <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control" id="admin_doc_title" name="title"
               value="<?= $valTitle ?>" placeholder="Nhập tiêu đề tài liệu..."
               required minlength="5" maxlength="500">
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="admin_faculty_id" class="form-label fw-semibold">Khoa / Viện</label>
            <select class="form-select" id="admin_faculty_id" name="faculty_id">
                <option value="">-- Tất cả Khoa / Viện --</option>
                <?php foreach ($faculties as $f): ?>
                    <option value="<?= (int)$f['id'] ?>" <?= (string)$valFaculty === (string)$f['id'] ? 'selected' : '' ?>>
                        <?= e($f['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="admin_subject_id" class="form-label fw-semibold">
                Môn học <span class="text-danger">*</span>
            </label>
            <select class="form-select" id="admin_subject_id" name="subject_id" required>
                <option value="">-- Chọn môn học --</option>
                <?php foreach ($subjects as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" data-faculty="<?= (int)$s['faculty_id'] ?>"
                        <?= (string)$valSubject === (string)$s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?><?= !empty($s['code']) ? ' (' . e($s['code']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="admin_type_id" class="form-label fw-semibold">
                Loại tài liệu <span class="text-danger">*</span>
            </label>
            <select class="form-select" id="admin_type_id" name="document_type_id" required>
                <option value="">-- Chọn loại tài liệu --</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= (string)$valType === (string)$t['id'] ? 'selected' : '' ?>>
                        <?= e($t['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="admin_doc_status" class="form-label fw-semibold">
                Trạng thái hiển thị <span class="text-danger">*</span>
            </label>
            <select class="form-select" id="admin_doc_status" name="status" required>
                <option value="approved" <?= $valStatus === 'approved' ? 'selected' : '' ?>>Đã duyệt (Công khai)</option>
                <option value="pending" <?= $valStatus === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                <option value="hidden" <?= $valStatus === 'hidden' ? 'selected' : '' ?>>Đã ẩn</option>
                <option value="rejected" <?= $valStatus === 'rejected' ? 'selected' : '' ?>>Bị từ chối</option>
            </select>
        </div>
    </div>

    <div class="mb-3 <?= $valStatus === 'rejected' ? '' : 'd-none' ?>" id="admin_rejection_group">
        <label for="admin_rejection_reason" class="form-label fw-semibold">
            Lý do từ chối <span class="text-danger">*</span>
        </label>
        <textarea class="form-control" id="admin_rejection_reason" name="rejection_reason" rows="2"
                  maxlength="1000" placeholder="Nhập lý do từ chối..."><?= $valReason ?></textarea>
    </div>

    <div class="mb-3">
        <label for="admin_document_file" class="form-label fw-semibold">
            <?= $isEdit ? 'Thay thế tệp tài liệu' : 'Tệp tài liệu' ?>
            <?php if (!$isEdit): ?><span class="text-danger">*</span><?php else: ?><span class="text-muted fw-normal">(Không bắt buộc)</span><?php endif; ?>
        </label>
        <?php if ($isEdit && !empty($doc['original_filename'])): ?>
            <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 px-3 mb-2">
                <div class="small">
                    <i class="bi bi-file-earmark-text text-primary me-1"></i>
                    <strong>Tệp hiện tại:</strong> <?= e($doc['original_filename']) ?>
                    <span class="text-muted">(<?= formatSize((int)($doc['file_size'] ?? 0)) ?>)</span>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/admin/documents/' . (int)$doc['id'] . '/preview') ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 small">
                        <i class="bi bi-eye me-1"></i>Xem file
                    </a>
                    <a href="<?= url('/admin/documents/' . (int)$doc['id'] . '/download') ?>" class="btn btn-xs btn-outline-success py-0 px-2 small">
                        <i class="bi bi-download me-1"></i>Tải file
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <input type="file" class="form-control" id="admin_document_file" name="document_file"
               accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" <?= $isEdit ? '' : 'required' ?>>
        <div class="form-text">
            Hỗ trợ: <strong>PDF, DOC, DOCX, PPT, PPTX, TXT</strong>. Dung lượng tối đa: <strong>50MB</strong>.
        </div>
    </div>

    <div class="mb-4">
        <label for="admin_doc_description" class="form-label fw-semibold">Mô tả tài liệu</label>
        <textarea class="form-control" id="admin_doc_description" name="description" rows="4"
                  placeholder="Tóm tắt nội dung tài liệu..."><?= $valDesc ?></textarea>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= url('/admin/documents') ?>" class="btn btn-outline-secondary">Hủy bỏ</a>
        <button type="submit" class="btn btn-primary fw-semibold px-4">
            <i class="bi bi-check-circle me-1"></i><?= $isEdit ? 'Lưu thay đổi' : 'Đăng tải tài liệu' ?>
        </button>
    </div>
</form>

<script>
(function () {
    const allSubjects = <?= json_encode(array_map(function ($s) {
        return [
            'id'         => (int)$s['id'],
            'faculty_id' => (int)$s['faculty_id'],
            'name'       => $s['name'] . (!empty($s['code']) ? ' (' . $s['code'] . ')' : ''),
        ];
    }, $subjects), JSON_UNESCAPED_UNICODE) ?>;

    const facultySelect = document.getElementById('admin_faculty_id');
    const subjectSelect = document.getElementById('admin_subject_id');
    const statusSelect  = document.getElementById('admin_doc_status');
    const reasonGroup   = document.getElementById('admin_rejection_group');
    const reasonInput   = document.getElementById('admin_rejection_reason');
    const initialSub    = <?= json_encode((string)$valSubject) ?>;

    function filterSubjects(facultyId) {
        const selectedVal = subjectSelect.value || initialSub;
        subjectSelect.innerHTML = '<option value="">-- Chọn môn học --</option>';
        const list = facultyId ? allSubjects.filter(s => String(s.faculty_id) === String(facultyId)) : allSubjects;
        list.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            if (String(s.id) === String(selectedVal)) opt.selected = true;
            subjectSelect.appendChild(opt);
        });
    }

    facultySelect?.addEventListener('change', function () {
        filterSubjects(this.value);
    });
    if (facultySelect && facultySelect.value) {
        filterSubjects(facultySelect.value);
    }

    function syncRejectionField() {
        const isRejected = statusSelect && statusSelect.value === 'rejected';
        reasonGroup?.classList.toggle('d-none', !isRejected);
        if (reasonInput) reasonInput.required = isRejected;
    }

    statusSelect?.addEventListener('change', syncRejectionField);
    syncRejectionField();
})();
</script>
