<?php declare(strict_types=1); ?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/') ?>">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/faculties/' . $doc['faculty_id']) ?>"><?= e($doc['faculty_name'] ?? 'Khoa') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= url('/subjects/' . $doc['subject_id']) ?>"><?= e($doc['subject_name'] ?? 'Môn học') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e(truncate($doc['title'], 40)) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Left details column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-primary fs-6"><?= e($doc['type_name'] ?? 'Tài liệu') ?></span>
                    <?php if ($doc['status'] !== 'approved'): ?>
                        <?php $stInfo = documentStatusInfo((string)$doc['status']); ?>
                        <span class="badge <?= $stInfo['bs'] ?> fs-6">Trạng thái: <?= e($stInfo['label']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($doc['status'] === 'pending' && isModerator()): ?>
                    <div class="alert alert-warning border-0 shadow-sm d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 mb-4 rounded-3">
                        <div>
                            <div class="fw-bold text-dark"><i class="bi bi-shield-exclamation text-warning me-1"></i>Tài liệu đang trong hàng đợi kiểm duyệt</div>
                            <div class="small text-muted">Bạn có quyền kiểm duyệt viên để phê duyệt hoặc từ chối tài liệu này.</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#approveDocModal">
                                <i class="bi bi-check-lg me-1"></i>Phê duyệt
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#rejectDocModal">
                                <i class="bi bi-x-lg me-1"></i>Từ chối
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <h3 class="fw-bold mb-3"><?= e($doc['title']) ?></h3>

                <div class="d-flex flex-wrap gap-3 text-muted small border-bottom pb-3 mb-3">
                    <span><i class="bi bi-person me-1"></i>Đăng bởi: <strong><?= e($doc['uploader_name'] ?? 'Thành viên TMU') ?></strong></span>
                    <span><i class="bi bi-calendar3 me-1"></i><?= formatDate($doc['created_at']) ?></span>
                    <span><i class="bi bi-eye me-1"></i><?= (int)$doc['view_count'] ?> lượt xem</span>
                    <span><i class="bi bi-download me-1"></i><?= (int)$doc['download_count'] ?> lượt tải</span>
                </div>

                <!-- Description -->
                <h5 class="fw-bold mt-2">Mô tả tài liệu</h5>
                <div class="bg-light p-3 rounded mb-4">
                    <?= nl2br(e($doc['description'] ?: 'Chưa có mô tả chi tiết cho tài liệu này.')) ?>
                </div>

                <!-- Document Preview -->
                <?php
                $ext = strtolower(pathinfo((string)$doc['original_filename'], PATHINFO_EXTENSION));
                $isPdf = ($doc['mime_type'] === 'application/pdf' || $ext === 'pdf');
                $isDocx = ($ext === 'docx' || str_contains((string)$doc['mime_type'], 'wordprocessingml'));
                $isDoc = ($ext === 'doc' || str_contains((string)$doc['mime_type'], 'msword'));
                ?>

                <?php if ($isPdf): ?>
                    <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Xem trước trực tiếp (PDF)</h5>
                    <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm border mb-4" style="min-height: 550px;">
                        <iframe src="<?= url('/documents/' . $doc['id'] . '/preview') ?>" allowfullscreen></iframe>
                    </div>
                <?php elseif ($isDocx): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-file-earmark-word text-primary me-2"></i>Xem trước tài liệu Word (.docx)
                            </h5>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                                <i class="bi bi-eye me-1"></i>Trình đọc trực tiếp
                            </span>
                        </div>
                        <div class="card border shadow-sm rounded-3 overflow-hidden">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                                <span class="small text-muted text-truncate" style="max-width: 70%;">
                                    <i class="bi bi-file-text me-1 text-primary"></i><?= e($doc['original_filename']) ?>
                                </span>
                                <div class="d-flex gap-2">
                                    <a href="<?= url('/documents/' . $doc['id'] . '/download') ?>" class="btn btn-xs btn-outline-primary py-1 px-2 small">
                                        <i class="bi bi-download me-1"></i>Tải file gốc
                                    </a>
                                </div>
                            </div>
                            <div id="docx-loading" class="text-center py-5 bg-white">
                                <div class="spinner-border text-primary mb-2" role="status"></div>
                                <div class="text-muted small">Đang tải và hiển thị nội dung tài liệu Word...</div>
                            </div>
                            <div id="docx-wrapper" class="bg-secondary bg-opacity-10 p-3" style="display: none;">
                                <div id="docx-container" class="shadow-sm mx-auto bg-white p-4" style="min-height: 400px; max-height: 700px; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/docx-preview@0.3.3/dist/docx-preview.min.js"></script>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const container = document.getElementById('docx-container');
                        const loading = document.getElementById('docx-loading');
                        const wrapper = document.getElementById('docx-wrapper');
                        if (!container || !loading) return;

                        fetch('<?= url('/documents/' . $doc['id'] . '/preview') ?>')
                            .then(function(res) {
                                if (!res.ok) throw new Error('Không thể tải file docx.');
                                return res.arrayBuffer();
                            })
                            .then(function(buffer) {
                                return docx.renderAsync(buffer, container, null, {
                                    className: 'docx-preview-content',
                                    inWrapper: true,
                                    ignoreWidth: false,
                                    ignoreHeight: false
                                });
                            })
                            .then(function() {
                                loading.style.display = 'none';
                                wrapper.style.display = 'block';
                            })
                            .catch(function(err) {
                                console.error('DOCX preview error:', err);
                                loading.innerHTML = `
                                    <div class="py-4 px-3 text-center">
                                        <i class="bi bi-file-earmark-word text-primary fs-1 mb-2 d-block"></i>
                                        <p class="text-muted small mb-3">Tài liệu Word này có định dạng đặc thù hoặc chứa macro. Vui lòng tải file về máy để xem trọn vẹn bằng Microsoft Word.</p>
                                        <a href="<?= url('/documents/' . $doc['id'] . '/download') ?>" class="btn btn-primary btn-sm px-4">
                                            <i class="bi bi-download me-1"></i>Tải file Word về máy
                                        </a>
                                    </div>
                                `;
                            });
                    });
                    </script>
                <?php elseif ($isDoc): ?>
                    <div class="card border shadow-sm p-4 text-center mb-4 bg-light rounded-3">
                        <div class="my-3">
                            <i class="bi bi-file-earmark-word-fill text-primary" style="font-size: 3.5rem;"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark"><?= e($doc['original_filename']) ?></h6>
                        <p class="text-muted small mb-3">
                            Tài liệu định dạng Word cũ (<strong>.doc Binary</strong>). Do định dạng mã hóa nhị phân độc quyền của Microsoft Office, bạn hãy tải file về để xem trọn vẹn trên máy tính.
                        </p>
                        <div>
                            <a href="<?= url('/documents/' . $doc['id'] . '/download') ?>" class="btn btn-primary px-4 py-2 fw-semibold">
                                <i class="bi bi-download me-2"></i>Tải xuống file Word (<?= formatSize((int)$doc['file_size']) ?>)
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle fs-4 me-3"></i>
                        <div>
                            Tài liệu định dạng <strong><?= e(strtoupper($ext ?: 'TẬP TIN')) ?></strong>. Vui lòng bấm <strong>Tải xuống</strong> để xem nội dung đầy đủ trên máy tính của bạn.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Actions: Download & Favorite -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="<?= url('/documents/' . $doc['id'] . '/download') ?>" class="btn btn-success btn-lg px-4 fw-bold">
                        <i class="bi bi-download me-2"></i>Tải xuống (<?= formatSize((int)$doc['file_size']) ?>)
                    </a>

                    <?php if (auth()): ?>
                        <button class="btn btn-outline-danger btn-lg px-3 btn-favorite" data-id="<?= $doc['id'] ?>" title="<?= $isFavorited ? 'Bỏ lưu' : 'Lưu tài liệu' ?>">
                            <i class="bi bi-bookmark-heart<?= $isFavorited ? '-fill' : '' ?> me-1"></i>
                            <span class="favorite-label"><?= $isFavorited ? 'Đã lưu' : 'Lưu tài liệu' ?></span>
                        </button>
                    <?php else: ?>
                        <a href="<?= url('/login') ?>" class="btn btn-outline-secondary btn-lg px-3">
                            <i class="bi bi-bookmark-heart me-1"></i>Đăng nhập để lưu tài liệu
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Comments Section -->
                <hr>
                <div id="comments" class="mt-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-chat-dots me-2"></i>Bình luận (<?= count($comments) ?>)</h5>

                    <?php if (auth()): ?>
                        <form action="<?= url('/documents/' . $doc['id'] . '/comment') ?>" method="POST" class="mb-4">
                            <?= csrfField() ?>
                            <div class="mb-2">
                                <textarea name="content" class="form-control" rows="3" placeholder="Viết câu hỏi hoặc thảo luận về tài liệu này..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                                <i class="bi bi-send me-1"></i>Gửi bình luận
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-light border small text-muted mb-4">
                            Vui lòng <a href="<?= url('/login') ?>" class="fw-semibold">đăng nhập</a> để tham gia bình luận.
                        </div>
                    <?php endif; ?>

                    <!-- Comments list -->
                    <?php if (empty($comments)): ?>
                        <p class="text-muted small">Chưa có bình luận nào. Hãy là người đầu tiên thảo luận!</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php
                            $commentModel = new \App\Models\Comment();
                            foreach ($comments as $c):
                                $replies = $commentModel->getReplies((int)$c['id']);
                            ?>
                                <div class="list-group-item px-0 py-3">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <strong><?= e($c['full_name']) ?></strong>
                                            <span class="text-muted small"><?= formatDate($c['created_at'], 'H:i d/m/Y') ?></span>
                                        </div>
                                        <?php if (auth() && (userId() === (int)$c['user_id'] || isModerator())): ?>
                                            <form action="<?= url('/comments/' . $c['id'] . '/delete') ?>" method="POST" class="d-inline">
                                                <?= csrfField() ?>
                                                <button type="submit" class="btn btn-sm text-danger p-0 border-0" data-confirm="Xóa bình luận này?">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-2"><?= nl2br(e($c['content'])) ?></p>

                                    <!-- Reply form button -->
                                    <?php if (auth()): ?>
                                        <a href="#reply-form-<?= $c['id'] ?>" class="small text-muted text-decoration-none" data-bs-toggle="collapse">
                                            <i class="bi bi-reply"></i> Trả lời
                                        </a>
                                        <div class="collapse mt-2" id="reply-form-<?= $c['id'] ?>">
                                            <form action="<?= url('/documents/' . $doc['id'] . '/comment') ?>" method="POST">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="content" class="form-control" placeholder="Viết phản hồi..." required>
                                                    <button class="btn btn-primary" type="submit">Gửi</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Nested replies -->
                                    <?php if (!empty($replies)): ?>
                                        <div class="comment-replies ms-2 ms-sm-4 mt-3 border-start ps-2 ps-sm-3">
                                            <?php foreach ($replies as $r): ?>
                                                <div class="mb-2">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                            <span class="fw-semibold small"><?= e($r['full_name']) ?></span>
                                                            <span class="text-muted" style="font-size: 0.75rem;"><?= formatDate($r['created_at'], 'H:i d/m/Y') ?></span>
                                                        </div>
                                                        <?php if (auth() && (userId() === (int)$r['user_id'] || isModerator())): ?>
                                                            <form action="<?= url('/comments/' . $r['id'] . '/delete') ?>" method="POST" class="d-inline">
                                                                <?= csrfField() ?>
                                                                <button type="submit" class="btn btn-sm text-danger p-0 border-0" data-confirm="Xóa phản hồi này?">
                                                                    <i class="bi bi-trash" style="font-size: 0.8rem;"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="mb-1 small text-break"><?= nl2br(e($r['content'])) ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right metadata column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-3 mb-4">
                <h6 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-1"></i>Thông tin học liệu</h6>
                <table class="table table-sm table-borderless small mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Khoa / Viện:</td>
                        <td class="fw-semibold"><?= e($doc['faculty_name'] ?? 'TMU') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Môn học:</td>
                        <td class="fw-semibold"><?= e($doc['subject_name'] ?? 'Chưa rõ') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Loại:</td>
                        <td><span class="badge bg-light text-dark border"><?= e($doc['type_name'] ?? 'Tài liệu') ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tên file gốc:</td>
                        <td class="text-break"><?= e($doc['original_filename']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dung lượng:</td>
                        <td><?= formatSize((int)$doc['file_size']) ?></td>
                    </tr>
                </table>
            </div>

            <?php if (auth() && (userId() === (int)$doc['user_id'] || isAdmin())): ?>
                <div class="card border-0 shadow-sm p-3">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Tác vụ quản lý</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= url('/documents/' . $doc['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i>Chỉnh sửa tài liệu
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteDocModal">
                            <i class="bi bi-trash me-1"></i>Xóa tài liệu
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa tài liệu -->
<?php if (auth() && (userId() === (int)$doc['user_id'] || isAdmin())): ?>
    <div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom bg-danger bg-opacity-10 py-3">
                    <h6 class="modal-title fw-bold text-danger mb-0 d-flex align-items-center">
                        <i class="bi bi-trash3-fill fs-5 me-2"></i>Xác nhận xóa tài liệu
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold mb-2 text-dark"><?= e($doc['title']) ?></h6>
                    <div class="alert alert-danger d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-danger bg-opacity-10 text-danger-emphasis">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                        <div>
                            Bạn có chắc chắn muốn xóa tài liệu này không? Thao tác này sẽ xóa vĩnh viễn tệp tin khỏi hệ thống và không thể khôi phục.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                    <form action="<?= url('/documents/' . $doc['id'] . '/delete') ?>" method="POST" class="d-inline mb-0">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                            <i class="bi bi-trash-fill me-1"></i>Xác nhận xóa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modals duyệt & từ chối (dành cho Moderator/Admin) -->
<?php if ($doc['status'] === 'pending' && isModerator()): ?>
    <!-- Modal duyệt -->
    <div class="modal fade" id="approveDocModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom bg-success bg-opacity-10 py-3">
                    <h6 class="modal-title fw-bold text-success mb-0 d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-5 me-2"></i>Phê duyệt tài liệu
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold mb-2 text-dark"><?= e($doc['title']) ?></h6>
                    <div class="small text-muted mb-3">
                        <span><i class="bi bi-person me-1"></i><?= e($doc['uploader_name'] ?? 'Ẩn danh') ?></span> &bull;
                        <span><i class="bi bi-book me-1"></i><?= e($doc['subject_name'] ?? 'Chưa rõ') ?></span>
                    </div>
                    <div class="alert alert-success d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-success bg-opacity-10 text-success-emphasis">
                        <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0"></i>
                        <div>
                            Tài liệu này sẽ được chuyển sang trạng thái <strong>Đã duyệt</strong> và xuất hiện công khai trên hệ thống TMU Tài Liệu.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                    <form action="<?= url('/admin/documents/' . $doc['id'] . '/approve') ?>" method="POST" class="d-inline mb-0">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i>Xác nhận duyệt
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal từ chối -->
    <div class="modal fade" id="rejectDocModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= url('/admin/documents/' . $doc['id'] . '/reject') ?>" method="POST" class="modal-content border-0 shadow">
                <?= csrfField() ?>
                <div class="modal-header border-bottom bg-danger bg-opacity-10 py-3">
                    <h6 class="modal-title fw-bold text-danger mb-0 d-flex align-items-center">
                        <i class="bi bi-x-octagon-fill fs-5 me-2"></i>Từ chối tài liệu
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-2">Tài liệu: <strong><?= e($doc['title']) ?></strong></p>
                    <div class="mb-2">
                        <label for="reject-reason" class="form-label fw-semibold small">Lý do từ chối <span class="text-danger">*</span></label>
                        <textarea id="reject-reason" name="reason" class="form-control" rows="3" maxlength="1000" required placeholder="Nhập lý do cụ thể để sinh viên chỉnh sửa và gửi lại..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                        <i class="bi bi-send me-1"></i>Xác nhận từ chối
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<style>
.comment-replies {
    max-width: 100%;
}
@media (max-width: 575.98px) {
    .comment-replies {
        margin-left: 0.5rem !important;
        padding-left: 0.5rem !important;
    }
}
</style>

<script>
    window.TMU_DOC_CONTEXT = {
        id: <?= (int)$doc['id'] ?>,
        title: <?= json_encode($doc['title'], JSON_UNESCAPED_UNICODE) ?>
    };
</script>


