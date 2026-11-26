<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($title ?? 'TMU Tài Liệu') ?></title>
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body id="top">

<?php
$isLoggedIn = auth();
$currentNavUser = $isLoggedIn ? currentUser() : null;
$unreadCount = 0;
if ($isLoggedIn) {
    $notifModel = new \App\Models\Notification();
    $unreadCount = $notifModel->getUnreadCount(userId());
}
?>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container-xl">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-nowrap" href="<?= url('/') ?>">
            <img src="<?= asset('images/logo.png') ?>" alt="Logo TMU" style="height: 38px; width: auto; object-fit: contain;" class="bg-white rounded px-1 py-0.5 shadow-sm">
            <span>TMU Tài Liệu</span>
        </a>
        <button class="navbar-toggler d-lg-none border-0 shadow-none" type="button" id="mobileMenuOpenBtn" aria-label="Mở menu điều hướng">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse d-none d-lg-flex" id="navMenu">
            <!-- Search bar -->
            <form class="d-flex mx-lg-3 my-2 my-lg-0 flex-grow-1" style="max-width: 320px;" action="<?= url('/search') ?>" method="GET">
                <input class="form-control me-2" type="search" name="q"
                       placeholder="Tìm kiếm tài liệu..."
                       value="<?= e($_GET['q'] ?? '') ?>">
                <button class="btn btn-light" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link text-nowrap" href="<?= url('/documents') ?>">
                        <i class="bi bi-files me-1"></i>Tài liệu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-nowrap" href="<?= url('/faculties') ?>">
                        <i class="bi bi-diagram-3 me-1"></i>Danh mục
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-nowrap" href="<?= url('/forum') ?>">
                        <i class="bi bi-chat-square-text me-1"></i>Hỏi đáp
                    </a>
                </li>

                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link text-nowrap" href="<?= url('/documents/create') ?>">
                            <i class="bi bi-cloud-upload me-1"></i>Đăng tải
                        </a>
                    </li>

                    <!-- Notifications -->
                    <li class="nav-item">
                        <a class="nav-link position-relative text-nowrap px-2" href="<?= url('/notifications') ?>" title="Thông báo">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- User dropdown -->
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-nowrap" href="#"
                           data-bs-toggle="dropdown">
                            <?php $avatarSrc = avatarUrl((int)$currentNavUser['id'], $currentNavUser['avatar'] ?? null); ?>
                            <?php if ($avatarSrc): ?>
                                <img src="<?= e($avatarSrc) ?>"
                                     class="rounded-circle" width="32" height="32" style="object-fit:cover"
                                     alt="<?= e($currentNavUser['full_name']) ?>">
                            <?php else: ?>
                                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center fw-bold"
                                     style="width:32px;height:32px;font-size:0.9rem">
                                    <?= mb_substr($currentNavUser['full_name'], 0, 1) ?>
                                </div>
                            <?php endif; ?>
                            <span class="d-none d-lg-inline"><?= e($currentNavUser['full_name']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header"><?= e($currentNavUser['email']) ?></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= url('/profile') ?>">
                                <i class="bi bi-person me-2"></i>Hồ sơ cá nhân
                            </a></li>
                            <li><a class="dropdown-item" href="<?= url('/my-documents') ?>">
                                <i class="bi bi-folder me-2"></i>Tài liệu của tôi
                            </a></li>
                            <li><a class="dropdown-item" href="<?= url('/favorites') ?>">
                                <i class="bi bi-bookmark-check me-2"></i>Lưu tài liệu
                            </a></li>
                            <li><a class="dropdown-item" href="<?= url('/history') ?>">
                                <i class="bi bi-clock-history me-2"></i>Lịch sử
                            </a></li>
                            <?php if (isModerator()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= url('/admin') ?>">
                                    <i class="bi bi-shield-check me-2"></i>Quản trị
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="<?= url('/logout') ?>" method="POST">
                                    <?= csrfField() ?>
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light btn-sm text-nowrap" href="<?= url('/login') ?>">Đăng nhập</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light btn-sm text-primary fw-semibold text-nowrap" href="<?= url('/register') ?>">Đăng ký</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile Responsive Drawer -->
<div class="mobile-drawer-backdrop d-lg-none" id="mobileNavBackdrop"></div>
<aside class="mobile-drawer d-lg-none" id="mobileNavDrawer" aria-hidden="true">
    <!-- Drawer Header -->
    <div class="mobile-drawer-header">
        <!-- Level 1 Header -->
        <div class="mobile-drawer-header-main d-flex align-items-center justify-content-between w-100" id="mobileHeaderMain">
            <a class="d-flex align-items-center gap-2 text-white text-decoration-none fw-bold fs-5" href="<?= url('/') ?>">
                <img src="<?= asset('images/logo.png') ?>" alt="Logo TMU" style="height: 34px; width: auto; object-fit: contain;" class="bg-white rounded px-1 py-0.5 shadow-sm">
                <span>TMU Tài Liệu</span>
            </a>
            <button type="button" class="mobile-drawer-close" id="mobileMenuCloseBtn" aria-label="Đóng menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Level 2 Header (User Submenu) -->
        <div class="mobile-drawer-header-sub d-none align-items-center justify-content-between w-100" id="mobileHeaderSub">
            <button type="button" class="mobile-drawer-back d-flex align-items-center gap-2 text-white bg-transparent border-0 p-0 fw-semibold fs-5" id="mobileMenuBackBtn">
                <i class="bi bi-arrow-left fs-5"></i>
                <span id="mobileSubHeaderTitle">Tài khoản</span>
            </button>
            <button type="button" class="mobile-drawer-close" id="mobileMenuCloseBtnSub" aria-label="Đóng menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Drawer Body (Panels Container) -->
    <div class="mobile-drawer-viewport">
        <div class="mobile-drawer-panels" id="mobileDrawerPanels">
            <!-- Panel 1: Main Menu -->
            <div class="mobile-drawer-panel" id="mobilePanelMain">
                <!-- Search Bar -->
                <div class="p-3 border-bottom bg-light">
                    <form class="d-flex gap-2" action="<?= url('/search') ?>" method="GET">
                        <input class="form-control" type="search" name="q"
                               placeholder="Tìm kiếm tài liệu..."
                               value="<?= e($_GET['q'] ?? '') ?>">
                        <button class="btn btn-primary px-3" type="submit" aria-label="Tìm kiếm">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Navigation List (Left-aligned) -->
                <div class="mobile-nav-list">
                    <a href="<?= url('/documents') ?>" class="mobile-nav-item">
                        <span class="mobile-nav-icon"><i class="bi bi-files"></i></span>
                        <span class="mobile-nav-label">Tài liệu</span>
                        <i class="bi bi-chevron-right mobile-nav-arrow"></i>
                    </a>

                    <a href="<?= url('/faculties') ?>" class="mobile-nav-item">
                        <span class="mobile-nav-icon"><i class="bi bi-diagram-3"></i></span>
                        <span class="mobile-nav-label">Danh mục</span>
                        <i class="bi bi-chevron-right mobile-nav-arrow"></i>
                    </a>

                    <a href="<?= url('/forum') ?>" class="mobile-nav-item">
                        <span class="mobile-nav-icon"><i class="bi bi-chat-square-text"></i></span>
                        <span class="mobile-nav-label">Hỏi đáp</span>
                        <i class="bi bi-chevron-right mobile-nav-arrow"></i>
                    </a>

                    <?php if ($isLoggedIn): ?>
                        <a href="<?= url('/documents/create') ?>" class="mobile-nav-item">
                            <span class="mobile-nav-icon"><i class="bi bi-cloud-upload"></i></span>
                            <span class="mobile-nav-label">Đăng tải</span>
                            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
                        </a>

                        <a href="<?= url('/notifications') ?>" class="mobile-nav-item">
                            <span class="mobile-nav-icon"><i class="bi bi-bell"></i></span>
                            <span class="mobile-nav-label">
                                Thông báo
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge rounded-pill bg-danger ms-2"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                                <?php endif; ?>
                            </span>
                            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
                        </a>

                        <div class="mobile-nav-divider"></div>

                        <!-- User Profile Row (opens Level 2) -->
                        <button type="button" class="mobile-nav-item mobile-nav-user-btn" id="mobileUserTrigger">
                            <?php if ($avatarSrc): ?>
                                <img src="<?= e($avatarSrc) ?>" class="mobile-nav-avatar" alt="<?= e($currentNavUser['full_name']) ?>">
                            <?php else: ?>
                                <span class="mobile-nav-avatar-placeholder">
                                    <?= mb_substr($currentNavUser['full_name'], 0, 1) ?>
                                </span>
                            <?php endif; ?>
                            <span class="mobile-nav-user-info">
                                <span class="mobile-nav-label d-block fw-semibold"><?= e($currentNavUser['full_name']) ?></span>
                                <span class="mobile-nav-sublabel d-block text-muted small"><?= e($currentNavUser['email']) ?></span>
                            </span>
                            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
                        </button>
                    <?php else: ?>
                        <div class="mobile-nav-divider"></div>

                        <a href="<?= url('/login') ?>" class="mobile-nav-item">
                            <span class="mobile-nav-icon"><i class="bi bi-box-arrow-in-right"></i></span>
                            <span class="mobile-nav-label">Đăng nhập</span>
                            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
                        </a>

                        <a href="<?= url('/register') ?>" class="mobile-nav-item">
                            <span class="mobile-nav-icon"><i class="bi bi-person-plus"></i></span>
                            <span class="mobile-nav-label">Đăng ký</span>
                            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel 2: User Submenu -->
            <?php if ($isLoggedIn): ?>
            <div class="mobile-drawer-panel" id="mobilePanelUser">
                <div class="mobile-sub-section-title">
                    <?= e($currentNavUser['email']) ?>
                </div>

                <div class="mobile-nav-list">
                    <a href="<?= url('/profile') ?>" class="mobile-sub-item">
                        <i class="bi bi-person"></i>
                        <span>Hồ sơ cá nhân</span>
                    </a>
                    <a href="<?= url('/my-documents') ?>" class="mobile-sub-item">
                        <i class="bi bi-folder"></i>
                        <span>Tài liệu của tôi</span>
                    </a>
                    <a href="<?= url('/favorites') ?>" class="mobile-sub-item">
                        <i class="bi bi-bookmark-check"></i>
                        <span>Lưu tài liệu</span>
                    </a>
                    <a href="<?= url('/history') ?>" class="mobile-sub-item">
                        <i class="bi bi-clock-history"></i>
                        <span>Lịch sử</span>
                    </a>

                    <?php if (isModerator()): ?>
                        <div class="mobile-nav-divider"></div>
                        <a href="<?= url('/admin') ?>" class="mobile-sub-item">
                            <i class="bi bi-shield-check"></i>
                            <span>Quản trị</span>
                        </a>
                    <?php endif; ?>

                    <div class="mobile-nav-divider"></div>
                    <form action="<?= url('/logout') ?>" method="POST" class="m-0">
                        <?= csrfField() ?>
                        <button type="submit" class="mobile-sub-item text-danger w-100 border-0 bg-transparent text-start">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Đăng xuất</span>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</aside>

<!-- Flash Messages -->
<div class="container mt-3" id="flash-container">
    <?php foreach (['success', 'error', 'warning', 'info'] as $type): ?>
        <?php foreach (getFlash($type) as $msg): ?>
            <?php $bsType = $type === 'error' ? 'danger' : $type; ?>
            <div class="alert alert-<?= $bsType ?> alert-dismissible fade show" role="alert">
                <?= $msg /* intentionally unescaped — set only by server */ ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?php
    // Handle serialized errors flash
    $errorsFlash = getFlash('errors');
    foreach ($errorsFlash as $serialized):
        $errArr = unserialize($serialized, ['allowed_classes' => false]);
        if (is_array($errArr)):
    ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Vui lòng kiểm tra lại thông tin:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($errArr as $field => $msgs): ?>
                    <?php foreach ((array)$msgs as $m): ?>
                        <li><?= e($m) ?></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; endforeach; ?>
</div>

<!-- Main Content -->
<main>
    <?= $content ?>
    <div class="container d-flex justify-content-end mt-4">
        <button type="button"
                class="btn btn-primary rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center"
                style="width: 44px; height: 44px;"
                onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                title="Lên đầu trang"
                aria-label="Lên đầu trang">
            <i class="bi bi-arrow-up fs-5"></i>
        </button>
    </div>
</main>

<!-- Footer -->
<?php require APP_PATH . '/Views/partials/footer.php'; ?>

<!-- AI Chatbot (Bottom-Left) -->
<?php require APP_PATH . '/Views/partials/ai_chatbot.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/main.js') ?>"></script>
<script src="<?= asset('js/chatbot.js') ?>"></script>
<?php clearOldInput(); ?>
</body>
</html>
