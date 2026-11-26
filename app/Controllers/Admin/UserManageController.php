<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\User;
use App\Models\UserBan;

class UserManageController extends Controller
{
    public function index(array $params = []): void
    {
        $this->requireRole('admin');

        (new UserBan())->cleanupExpiredBans();

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $search  = trim((string)($_GET['search'] ?? $_GET['q'] ?? ''));
        $role    = trim((string)($_GET['role'] ?? ''));
        $status  = trim((string)($_GET['status'] ?? ''));

        $userModel = new User();
        $this->view('admin.users.index', [
            'title'   => 'Quản lý người dùng — Admin TMU',
            'users'   => $userModel->getAllWithPagination($page, $perPage, $search, $role, $status),
            'stats'   => $userModel->getStats(),
            'filters' => [
                'search' => $search,
                'role'   => $role,
                'status' => $status,
            ],
            'search'  => $search,
            'role'    => $role,
            'status'  => $status,
        ], 'admin');
    }

    public function show(array $params = []): void
    {
        $this->requireRole('admin');
        $userId   = (int)$params['id'];
        $banModel = new UserBan();
        $banModel->cleanupExpiredBans();

        $userModel = new User();
        $user      = $userModel->findById($userId);
        if (!$user) {
            flash('error', 'Người dùng không tồn tại.');
            $this->redirect('/admin/users');
        }

        $this->view('admin.users.show', [
            'title'    => 'Chi tiết người dùng — Admin TMU',
            'viewUser' => $user,
            'banInfo'  => $banModel->findByUserId($userId),
        ], 'admin');
    }

    public function toggleStatus(array $params = []): void
    {
        $this->requireRole('admin');
        $this->csrfValidate();

        $userId      = (int)$params['id'];
        $redirectTo  = ($_POST['redirect_to'] ?? '') === 'show' ? "/admin/users/{$userId}" : '/admin/users';
        $userModel   = new User();
        $banModel    = new UserBan();
        $user        = $userModel->findById($userId);

        if (!$user) {
            flash('error', 'Người dùng không tồn tại.');
            $this->redirect('/admin/users');
        }

        // Cannot lock yourself
        if ($userId === userId()) {
            flash('error', 'Không thể khóa tài khoản của chính bạn.');
            $this->redirect($redirectTo);
        }

        if ($user['status'] === 'locked') {
            $banModel->unbanUser($userId);
            flash('success', 'Tài khoản đã được mở khóa.');
            $this->redirect($redirectTo);
        }

        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') {
            flash('error', 'Vui lòng nhập lý do khóa tài khoản.');
            $this->redirect($redirectTo);
        }
        if (mb_strlen($reason) > 1000) {
            flash('error', 'Lý do khóa tài khoản không được vượt quá 1000 ký tự.');
            $this->redirect($redirectTo);
        }

        $duration  = trim((string)($_POST['duration'] ?? 'permanent'));
        $expiresAt = $this->resolveBanExpiry($duration, trim((string)($_POST['expires_at'] ?? '')));
        if ($duration === 'custom' && $expiresAt === false) {
            flash('error', 'Thời gian mở khóa tùy chỉnh phải hợp lệ và lớn hơn thời điểm hiện tại.');
            $this->redirect($redirectTo);
        }

        $banModel->banUser($userId, (int)userId(), $reason, $expiresAt ?: null);

        $msg = $expiresAt
            ? 'Tài khoản đã bị khóa đến ' . date('H:i d/m/Y', strtotime($expiresAt)) . '.'
            : 'Tài khoản đã bị khóa vĩnh viễn.';
        flash('success', $msg);
        $this->redirect($redirectTo);
    }

    private function resolveBanExpiry(string $duration, string $customInput): string|false|null
    {
        return match ($duration) {
            '1_day'   => date('Y-m-d H:i:s', strtotime('+1 day')),
            '3_days'  => date('Y-m-d H:i:s', strtotime('+3 days')),
            '7_days'  => date('Y-m-d H:i:s', strtotime('+7 days')),
            '30_days' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'custom'  => $this->parseCustomExpiry($customInput),
            default   => null,
        };
    }

    private function parseCustomExpiry(string $customInput): string|false
    {
        if ($customInput === '') {
            return false;
        }
        $ts = strtotime($customInput);
        if ($ts === false || $ts <= time()) {
            return false;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    public function setRole(array $params = []): void
    {
        $this->requireRole('admin');
        $this->csrfValidate();

        $userId = (int)$params['id'];
        $role   = $_POST['role'] ?? '';

        if (!in_array($role, ['admin', 'moderator', 'member'])) {
            flash('error', 'Vai trò không hợp lệ.');
            $this->redirect('/admin/users');
        }

        // Cannot change own role
        if ($userId === userId()) {
            flash('error', 'Không thể thay đổi vai trò của chính bạn.');
            $this->redirect('/admin/users');
        }

        $userModel = new User();
        $userModel->update($userId, ['role' => $role]);

        flash('success', 'Vai trò đã được cập nhật thành công.');
        $this->redirect('/admin/users');
    }
}

