<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= url('/admin/documents') ?>" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
        </a>
        <h4 class="fw-bold mb-0"><i class="bi bi-pencil-square text-warning me-2"></i>Chỉnh sửa tài liệu #<?= (int)$doc['id'] ?></h4>
    </div>
</div>

<div class="card border-0 shadow-sm p-4">
    <?php
    $actionUrl = url('/admin/documents/' . (int)$doc['id'] . '/update');
    require __DIR__ . '/_form.php';
    ?>
</div>
