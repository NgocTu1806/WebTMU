<?php declare(strict_types=1); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-shield-check text-primary me-2"></i>Kiểm duyệt nội dung
            </h3>
            <p class="text-muted small mb-0">
                Phê duyệt tài liệu và câu hỏi diễn đàn trước khi hiển thị công khai trên hệ thống.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/documents') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-files me-1"></i>Tất cả tài liệu
            </a>
            <a href="<?= url('/forum') ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="bi bi-chat-dots me-1"></i>Xem diễn đàn
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 fw-semibold p-1 bg-light rounded-3 d-inline-flex border">
        <li class="nav-item">
            <a class="nav-link px-3 py-2 <?= ($activeTab ?? 'documents') === 'documents' ? 'active' : 'text-dark' ?>" 
               href="<?= url('/admin/moderation?tab=documents') ?>">
                <i class="bi bi-file-earmark-text me-1"></i>Tài liệu chờ duyệt
                <span class="badge <?= (int)($docs['total'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' ?> ms-1">
                    <?= (int)($docs['total'] ?? 0) ?>
                </span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link px-3 py-2 <?= ($activeTab ?? '') === 'forum' ? 'active' : 'text-dark' ?>" 
               href="<?= url('/admin/moderation?tab=forum') ?>">
                <i class="bi bi-chat-left-quote me-1"></i>Diễn đàn chờ duyệt
                <span class="badge <?= (int)($pendingForum ?? 0) > 0 ? 'bg-warning text-dark' : 'bg-secondary' ?> ms-1">
                    <?= (int)($pendingForum ?? 0) ?>
                </span>
            </a>
        </li>
    </ul>

    <?php if (($activeTab ?? 'documents') === 'forum'): ?>
        <!-- ================= FORUM MODERATION TAB ================= -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tiêu đề &amp; Nội dung câu hỏi</th>
                            <th>Người đăng</th>
                            <th>Khoa / Môn học</th>
                            <th>Ngày gửi</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forumPosts['items'])): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                                    Hiện không có câu hỏi diễn đàn nào đang chờ duyệt.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($forumPosts['items'] as $post): ?>
                                <tr>
                                    <td class="text-muted small">#<?= (int)$post['id'] ?></td>
                                    <td>
                                        <a href="<?= url('/forum/' . $post['id']) ?>" target="_blank"
                                           class="fw-bold text-dark text-decoration-none">
                                            <?= e(truncate($post['title'], 60)) ?>
                                            <i class="bi bi-box-arrow-up-right small text-muted ms-1"></i>
                                        </a>
                                        <div class="text-muted small mt-1">
                                            <?= e(truncate(strip_tags($post['content']), 100)) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($post['full_name'] ?? 'Ẩn danh') ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">@<?= e($post['username'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= e($post['subject_name'] ?? 'Chưa chọn môn') ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= e($post['faculty_name'] ?? '') ?></div>
                                    </td>
                                    <td class="small text-muted">
                                        <?= formatDate((string)$post['created_at'], 'H:i d/m/Y') ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="<?= url('/forum/' . $post['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="Xem chi tiết câu hỏi">
                                            <i class="bi bi-eye me-1"></i>Xem
                                        </a>

                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveForumModal-<?= (int)$post['id'] ?>">
                                            <i class="bi bi-check-lg me-1"></i>Duyệt
                                        </button>

                                        <button type="button" class="btn btn-sm btn-danger ms-1" data-bs-toggle="modal" data-bs-target="#rejectForumModal-<?= (int)$post['id'] ?>">
                                            <i class="bi bi-x-lg me-1"></i>Từ chối
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Forum Pagination -->
        <?php if (($forumPosts['last_page'] ?? 1) > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $forumPosts['last_page']; $i++): ?>
                        <li class="page-item <?= $i === ($forumPosts['current_page'] ?? 1) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/moderation?tab=forum&page=' . $i) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- Modals duyệt & từ chối bài viết diễn đàn -->
        <?php if (!empty($forumPosts['items'])): ?>
            <?php foreach ($forumPosts['items'] as $post): ?>
                <!-- Modal duyệt diễn đàn -->
                <div class="modal fade" id="approveForumModal-<?= (int)$post['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-bottom bg-success bg-opacity-10 py-3">
                                <h6 class="modal-title fw-bold text-success mb-0 d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>Phê duyệt câu hỏi #<?= (int)$post['id'] ?>
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <h6 class="fw-bold mb-2 text-dark"><?= e($post['title']) ?></h6>
                                <div class="small text-muted mb-3">
                                    <span><i class="bi bi-person me-1"></i><?= e($post['full_name'] ?? 'Ẩn danh') ?></span> &bull;
                                    <span><i class="bi bi-book me-1"></i><?= e($post['subject_name'] ?? 'Chưa chọn môn') ?></span>
                                </div>
                                <div class="alert alert-success d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-success bg-opacity-10 text-success-emphasis">
                                    <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0"></i>
                                    <div>
                                        Câu hỏi sẽ được phê duyệt và hiển thị công khai trên diễn đàn học tập để cộng đồng sinh viên TMU cùng thảo luận, trả lời.
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top bg-light py-2">
                                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                                <form action="<?= url('/admin/forum/' . $post['id'] . '/approve') ?>" method="POST" class="d-inline mb-0">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">
                                        <i class="bi bi-check-circle me-1"></i>Xác nhận duyệt
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal từ chối diễn đàn -->
                <div class="modal fade" id="rejectForumModal-<?= (int)$post['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-bottom bg-danger bg-opacity-10 py-3">
                                <h6 class="modal-title fw-bold text-danger mb-0 d-flex align-items-center">
                                    <i class="bi bi-x-octagon-fill fs-5 me-2"></i>Từ chối câu hỏi #<?= (int)$post['id'] ?>
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <h6 class="fw-bold mb-2 text-dark"><?= e($post['title']) ?></h6>
                                <p class="small text-muted mb-3">
                                    Người gửi: <strong><?= e($post['full_name'] ?? 'Ẩn danh') ?></strong> (@<?= e($post['username'] ?? '') ?>)
                                </p>
                                <div class="alert alert-danger d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-danger bg-opacity-10 text-danger-emphasis">
                                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0"></i>
                                    <div>
                                        Bạn có chắc chắn muốn từ chối bài viết câu hỏi này? Bài viết sẽ không được đăng lên diễn đàn.
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top bg-light py-2">
                                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                                <form action="<?= url('/admin/forum/' . $post['id'] . '/reject') ?>" method="POST" class="d-inline mb-0">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                                        <i class="bi bi-x-circle me-1"></i>Xác nhận từ chối
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php else: ?>
        <!-- ================= DOCUMENTS MODERATION TAB ================= -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tên tài liệu</th>
                            <th>Người đăng</th>
                            <th>Khoa / Môn học</th>
                            <th>Loại &amp; Dung lượng</th>
                            <th>Ngày gửi</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($docs['items'])): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                                    Hiện không có tài liệu nào đang chờ kiểm duyệt.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($docs['items'] as $doc): ?>
                                <tr>
                                    <td class="text-muted small">#<?= (int)$doc['id'] ?></td>
                                    <td>
                                        <a href="<?= url('/documents/' . $doc['id']) ?>" target="_blank"
                                           class="fw-bold text-dark text-decoration-none">
                                            <?= e(truncate($doc['title'], 60)) ?>
                                            <i class="bi bi-box-arrow-up-right small text-muted ms-1"></i>
                                        </a>
                                        <?php if (!empty($doc['description'])): ?>
                                            <div class="text-muted small">
                                                <?= e(truncate($doc['description'], 80)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($doc['uploader_name'] ?? 'Ẩn danh') ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">@<?= e($doc['username'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= e($doc['subject_name'] ?? 'Chưa rõ') ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= e($doc['faculty_name'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border"><?= e($doc['type_name'] ?? 'Tài liệu') ?></span>
                                        <div class="text-muted small mt-1"><?= formatSize((int)($doc['file_size'] ?? 0)) ?></div>
                                    </td>
                                    <td class="small text-muted">
                                        <?= formatDate((string)$doc['created_at'], 'H:i d/m/Y') ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#approveModal-<?= (int)$doc['id'] ?>">
                                            <i class="bi bi-check-lg me-1"></i>Duyệt
                                        </button>

                                        <button type="button" class="btn btn-sm btn-danger ms-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rejectModal-<?= (int)$doc['id'] ?>">
                                            <i class="bi bi-x-lg me-1"></i>Từ chối
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Documents Pagination -->
        <?php if (($docs['last_page'] ?? 1) > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $docs['last_page']; $i++): ?>
                        <li class="page-item <?= $i === ($docs['current_page'] ?? 1) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/moderation?tab=documents&page=' . $i) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- Modals phê duyệt & từ chối tài liệu -->
        <?php if (!empty($docs['items'])): ?>
            <?php foreach ($docs['items'] as $doc): ?>
                <!-- Modal phê duyệt tài liệu -->
                <div class="modal fade" id="approveModal-<?= (int)$doc['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-bottom bg-success bg-opacity-10 py-3">
                                <h6 class="modal-title fw-bold text-success mb-0 d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>Phê duyệt tài liệu #<?= (int)$doc['id'] ?>
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="p-3 bg-success bg-opacity-10 rounded-circle text-success flex-shrink-0">
                                        <i class="bi bi-file-earmark-check fs-2"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark"><?= e($doc['title']) ?></h6>
                                        <div class="small text-muted mb-2">
                                            <span><i class="bi bi-person me-1"></i><?= e($doc['uploader_name'] ?? 'Ẩn danh') ?></span> &bull;
                                            <span><i class="bi bi-book me-1"></i><?= e($doc['subject_name'] ?? 'Chưa rõ') ?></span>
                                        </div>
                                        <span class="badge bg-light text-secondary border"><?= e($doc['type_name'] ?? 'Tài liệu') ?></span>
                                        <span class="badge bg-light text-secondary border ms-1"><?= formatSize((int)($doc['file_size'] ?? 0)) ?></span>
                                    </div>
                                </div>

                                <div class="alert alert-success d-flex align-items-center mb-0 py-2 px-3 small border-0 bg-success bg-opacity-10 text-success-emphasis">
                                    <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0"></i>
                                    <div>
                                        Tài liệu này sẽ được chuyển sang trạng thái <strong>Đã duyệt</strong> và xuất hiện công khai trên hệ thống TMU Tài Liệu để tất cả sinh viên có thể xem và tải về.
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

                <!-- Modal từ chối tài liệu -->
                <div class="modal fade" id="rejectModal-<?= (int)$doc['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form action="<?= url('/admin/documents/' . $doc['id'] . '/reject') ?>" method="POST" class="modal-content border-0 shadow">
                            <?= csrfField() ?>
                            <div class="modal-header border-bottom bg-danger bg-opacity-10">
                                <h6 class="modal-title fw-bold text-danger mb-0">
                                    <i class="bi bi-x-octagon-fill me-2"></i>Từ chối tài liệu #<?= (int)$doc['id'] ?>
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted mb-2">
                                    Tài liệu: <strong><?= e($doc['title']) ?></strong>
                                </p>
                                <div class="mb-2">
                                    <label for="reason-<?= (int)$doc['id'] ?>" class="form-label fw-semibold small">
                                        Lý do từ chối <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="reason-<?= (int)$doc['id'] ?>" name="reason" class="form-control"
                                              rows="3" maxlength="1000" required
                                              placeholder="Nhập lý do cụ thể để sinh viên chỉnh sửa và gửi lại..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer border-top bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-send me-1"></i>Xác nhận từ chối
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>
</div>
