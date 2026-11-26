<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\MailService;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public function getForUser(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function getUnreadCount(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
            [$userId]
        );
    }

    public function send(int $userId, string $type, string $title, string $message, string $link = ''): void
    {
    public function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $link = '',
        bool $sendEmail = true
    ): void {
        $this->create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
        ]);
        
        if (!$sendEmail) {
            return;
        }
        // Gửi song song qua email (bọc try/catch để lỗi SMTP không làm hỏng thao tác chính)
        try {
            $user = (new User())->findById($userId);
            if ($user && !empty($user['email'])) {
                (new MailService())->sendNotificationEmail(
                    (string)$user['email'],
                    (string)($user['full_name'] ?? 'Sinh viên TMU'),
                    $title,
                    $message,
                    $link,
                    $type
                );
            }
        } catch (\Throwable $e) {
            error_log('[Notification] Lỗi gửi email thông báo cho user #' . $userId . ': ' . $e->getMessage());
        }
    }
}

