<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function loginForm(array $params = []): void
    {
        $returnUrl = $this->safeReturnUrl($_GET['return'] ?? '', '/admin');

        if (auth()) {
            if (isModerator()) {
                $this->redirect($returnUrl !== '' ? $returnUrl : '/admin');
            }
            http_response_code(403);
            $this->view('errors.403', ['user' => currentUser()], 'main');
            return;
        }

        $this->view('admin.auth.login', [
            'title'      => 'Đăng nhập Quản trị — TMU Admin',
            'return_url' => $returnUrl,
        ], 'none');
    }

    public function login(array $params = []): void
    {
        if (auth() && isModerator()) {
            $this->redirect('/admin');
        }

        $this->csrfValidate();

        $email     = strtolower(trim($_POST['email'] ?? ''));
        $password  = $_POST['password'] ?? '';
        $remember  = !empty($_POST['remember']);
        $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? $_GET['return'] ?? '', '/admin');
        $loginUrl  = '/admin/login' . ($returnUrl !== '' ? '?return=' . urlencode($returnUrl) : '');

        $errors = $this->validate(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );
        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect($loginUrl);
        }

        $result = $this->authService->login($email, $password, $remember);
        if (!$result['success']) {
            storeOldInput();
            flash('error', $result['error']);
            $this->redirect($loginUrl);
        }

        $role = $result['user']['role'] ?? 'member';
        if (!in_array($role, ['admin', 'moderator'], true)) {
            $this->authService->logout();
            if (session_status() === PHP_SESSION_NONE) {
                session_id(session_create_id());
                session_start();
            }
            storeOldInput();
            flash('error', 'Tài khoản của bạn không có quyền truy cập trang quản trị.');
            $this->redirect($loginUrl);
        }

        clearOldInput();
        $fullName = e(trim((string)($result['user']['full_name'] ?? '')));
        flash('success', 'Đăng nhập quản trị thành công. Xin chào, ' . $fullName . '!');
        $this->redirect($returnUrl !== '' ? $returnUrl : '/admin');
    }

    public function logout(array $params = []): void
    {
        $this->csrfValidate();
        $this->authService->logout();

        if (session_status() === PHP_SESSION_NONE) {
            session_id(session_create_id());
            session_start();
        }
        flash('success', 'Bạn đã đăng xuất khỏi trang quản trị.');
        $this->redirect('/admin/login');
    }
}
