<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM users WHERE email = ? LIMIT 1",
            [$email]
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM users WHERE username = ? LIMIT 1",
            [$username]
        );
    }

    public function findByResetToken(string $token): ?array
    {
        $hashedToken = hash('sha256', $token);
        return $this->db->fetch(
            "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1",
            [$hashedToken]
        );
    }

    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $params = [$email];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        return (int)$this->db->fetchColumn($sql, $params) > 0;
    }

    public function usernameExists(string $username, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        return (int)$this->db->fetchColumn($sql, $params) > 0;
    }

    public function studentIdExists(string $studentId, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE student_id = ?";
        $params = [$studentId];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        return (int)$this->db->fetchColumn($sql, $params) > 0;
    }

    public function updateLastLogin(int $userId): void
    {
        $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }

    public function setResetToken(int $userId, string $token): void
    {
        $this->update($userId, [
            'reset_token'         => $token,
            'reset_token_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);
    }

    public function clearResetToken(int $userId): void
    {
        $this->update($userId, [
            'reset_token'         => null,
            'reset_token_expires' => null,
        ]);
    }

    public function getAllWithPagination(int $page, int $perPage, string $search = '', string $role = '', string $status = ''): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.student_id LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($role !== '') {
            $where[] = "u.role = ?";
            $params[] = $role;
        }
        if ($status !== '') {
            $where[] = "u.status = ?";
            $params[] = $status;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $countSql = "SELECT COUNT(*) FROM users u {$whereClause}";
        $total = (int)$this->db->fetchColumn($countSql, $params);

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT u.id, u.username, u.email, u.full_name, u.avatar, u.student_id, u.status, u.role, u.created_at, u.last_login,
                       ub.reason AS ban_reason, ub.banned_at, ub.expires_at AS ban_expires_at,
                       admin_u.full_name AS banned_by_name
                FROM users u
                LEFT JOIN user_bans ub ON ub.user_id = u.id
                LEFT JOIN users admin_u ON admin_u.id = ub.banned_by
                {$whereClause}
                ORDER BY u.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        return [
            'items'        => $this->db->fetchAll($sql, $params),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public function getStats(): array
    {
        return [
            'total'      => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM users"),
            'active'     => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'active'"),
            'locked'     => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = 'locked'"),
            'this_month' => (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
            ),
        ];
    }
    public function saveResetToken(int $userId, string $token, string $expiresAt): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE id = :id");
        return $stmt->execute([':token' => $token, ':expires' => $expiresAt, ':id' => $userId]);
    }

    public function findByValidResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW() LIMIT 1");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expires = NULL WHERE id = :id");
        return $stmt->execute([':hash' => $passwordHash, ':id' => $userId]);
    }
}
