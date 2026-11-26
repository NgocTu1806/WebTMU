<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Model quản lý thông tin khóa tài khoản (quan hệ 1-1 với bảng users).
 */
class UserBan extends Model
{
    protected static string $table = 'user_bans';
    protected static string $primaryKey = 'user_id';

    /**
     * Lấy thông tin khóa tài khoản kèm tên quản trị viên thực hiện khóa.
     */
    public function findByUserId(int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT ub.*, admin_u.full_name AS banned_by_name, admin_u.username AS banned_by_username
             FROM user_bans ub
             LEFT JOIN users admin_u ON admin_u.id = ub.banned_by
             WHERE ub.user_id = ?
             LIMIT 1",
            [$userId]
        );
    }

    /**
     * Tạo hoặc cập nhật bản ghi khóa tài khoản (1-1) và đồng bộ trạng thái user.
     */
    public function banUser(int $userId, int $bannedBy, string $reason, ?string $expiresAt = null): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO user_bans (user_id, banned_by, reason, banned_at, expires_at)
                 VALUES (?, ?, ?, NOW(), ?)
                 ON DUPLICATE KEY UPDATE
                     banned_by = VALUES(banned_by),
                     reason = VALUES(reason),
                     banned_at = NOW(),
                     expires_at = VALUES(expires_at)",
                [$userId, $bannedBy, $reason, $expiresAt]
            );

            $this->db->update('users', [
                'status'         => 'locked',
                'remember_token' => null,
            ], ['id' => $userId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Xóa bản ghi khóa và khôi phục trạng thái hoạt động cho tài khoản.
     */
    public function unbanUser(int $userId): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->query("DELETE FROM user_bans WHERE user_id = ?", [$userId]);
            $this->db->update('users', ['status' => 'active'], ['id' => $userId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Kiểm tra bản ghi khóa đã hết hạn hay chưa.
     */
    public function isExpired(?array $ban): bool
    {
        if (!$ban || empty($ban['expires_at'])) {
            return false;
        }
        return strtotime((string)$ban['expires_at']) <= time();
    }

    /**
     * Xử lý tài khoản đang bị khóa: tự động mở khóa nếu hết hạn,
     * ngược lại trả về thông báo lý do khóa chi tiết.
     *
     * @return string|null Trả về null nếu đã tự động mở khóa thành công.
     */
    public function handleLockedUser(int $userId): ?string
    {
        $ban = $this->findByUserId($userId);
        if ($this->isExpired($ban)) {
            $this->unbanUser($userId);
            return null;
        }

        return $this->formatLockMessage($ban);
    }

    /**
     * Dọn dẹp các lệnh khóa đã hết hạn trên toàn hệ thống.
     */
    public function cleanupExpiredBans(): void
    {
        $expiredIds = $this->db->fetchAll(
            "SELECT user_id FROM user_bans WHERE expires_at IS NOT NULL AND expires_at <= NOW()"
        );

        if (empty($expiredIds)) {
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "UPDATE users u
                 INNER JOIN user_bans ub ON ub.user_id = u.id
                 SET u.status = 'active'
                 WHERE ub.expires_at IS NOT NULL AND ub.expires_at <= NOW()"
            );
            $this->db->query(
                "DELETE FROM user_bans WHERE expires_at IS NOT NULL AND expires_at <= NOW()"
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Tạo thông báo chi tiết khi người dùng bị khóa đăng nhập.
     */
    public function formatLockMessage(?array $ban): string
    {
        if (!$ban) {
            return 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
        }

        $durationText = !empty($ban['expires_at'])
            ? ' đến ' . date('H:i d/m/Y', strtotime((string)$ban['expires_at']))
            : ' vĩnh viễn';

        $reason = trim((string)($ban['reason'] ?? ''));
        $reasonText = $reason !== '' ? '. Lý do: ' . $reason : '. Vui lòng liên hệ quản trị viên.';

        return 'Tài khoản của bạn đã bị khóa' . $durationText . $reasonText;
    }
}
