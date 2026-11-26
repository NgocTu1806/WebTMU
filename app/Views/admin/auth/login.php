<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($title ?? 'Đăng nhập Quản trị — TMU Admin') ?></title>
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 55%, #0f3460 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .admin-login-card {
            width: 100%;
            max-width: 440px;
            border-radius: 1rem;
            overflow: hidden;
        }
        .admin-login-header {
            background: #1a1a2e;
            color: #fff;
            padding: 1.75rem 1.5rem 1.25rem;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="card border-0 shadow-lg admin-login-card">
    <div class="admin-login-header">
        <img src="<?= asset('images/logo.png') ?>" alt="Logo TMU"
             style="height: 54px; width: auto; object-fit: contain;"
             class="bg-white rounded px-2 py-1 mb-2 shadow-sm">
        <h4 class="fw-bold mb-1">TMU Admin Portal</h4>
        <p class="small text-white-50 mb-0">Đăng nhập hệ thống quản trị &amp; kiểm duyệt</p>
    </div>

    <div class="card-body p-4 bg-white">
        <?php foreach (['success', 'error', 'warning', 'info'] as $t): ?>
            <?php foreach (getFlash($t) as $msg): ?>
                <?php $bt = $t === 'error' ? 'danger' : $t; ?>
                <div class="alert alert-<?= $bt ?> alert-dismissible fade show small py-2">
                    <?= e($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?php foreach (getFlash('errors') as $serialized): ?>
            <?php $errArr = @unserialize($serialized, ['allowed_classes' => false]); if (is_array($errArr)): ?>
                <div class="alert alert-danger alert-dismissible fade show small py-2">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errArr as $msgs): ?>
                            <?php foreach ((array)$msgs as $m): ?>
                                <li><?= e($m) ?></li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <form action="<?= url('/admin/login') ?>" method="POST" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="return_url" value="<?= e($return_url ?? '') ?>">

            <div class="mb-3">
                <label for="admin_email" class="form-label fw-semibold small">Email quản trị</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="admin_email" name="email"
                           value="<?= old('email') ?>" placeholder="admin@tmu.edu.vn"
                           required autofocus autocomplete="username">
                </div>
            </div>

            <div class="mb-3">
                <label for="admin_password" class="form-label fw-semibold small">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="admin_password" name="password"
                           placeholder="Nhập mật khẩu" required autocomplete="current-password">
                    <button class="btn btn-outline-secondary" type="button" id="toggleAdminPwd" title="Hiện/ẩn mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="admin_remember" name="remember">
                    <label class="form-check-label small" for="admin_remember">Ghi nhớ đăng nhập</label>
                </div>
                <a href="<?= url('/forgot-password') ?>" class="text-decoration-none small">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                <i class="bi bi-shield-lock me-1"></i>Đăng nhập Admin
            </button>
        </form>

        <hr class="my-3">
        <div class="text-center">
            <a href="<?= url('/') ?>" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>Quay về trang người dùng
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('toggleAdminPwd')?.addEventListener('click', function () {
    const pwd = document.getElementById('admin_password');
    const icon = this.querySelector('i');
    const show = pwd.type === 'password';
    pwd.type = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>
<?php clearOldInput(); ?>
</body>
</html>
