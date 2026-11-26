<?php
declare(strict_types=1);

namespace App\Services;

class OtpService
{
    private const OTP_TTL         = 60;   // Hạn OTP: 60 giây (1 phút)
    private const MAX_WRONG_TRIES = 5;    // Quá 5 lần sai (từ lần sai thứ 6) sẽ khóa
    private const LOCK_SECONDS    = 300;  // Khóa tính năng trong 300 giây (5 phút)
    private const RESEND_COOLDOWN = 30;   // Tối thiểu 30 giây giữa 2 lần gửi OTP tới cùng một email

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function checkLock(string $context): array
    {
        $lockUntil = (int)($_SESSION['otp_lock'][$context] ?? 0);
        if (time() < $lockUntil) {
            $remain = $lockUntil - time();
            $min = str_pad((string)intdiv($remain, 60), 2, '0', STR_PAD_LEFT);
            $sec = str_pad((string)($remain % 60), 2, '0', STR_PAD_LEFT);
            return [
                'locked'  => true,
                'message' => "Bạn đã nhập sai OTP quá 5 lần. Tính năng bị khóa trong {$min}:{$sec}.",
            ];
        }
        unset($_SESSION['otp_lock'][$context]);
        return ['locked' => false];
    }

    /**
     * Sinh OTP mới cho một ngữ cảnh (register, change_password...).
     *
     * @param string $target Email nhận OTP — dùng để áp dụng thời gian chờ giữa 2 lần gửi.
     */
    public function generate(string $context, array $payload = [], string $target = ''): array
    {
        $lock = $this->checkLock($context);
        if ($lock['locked']) {
            return ['success' => false, 'locked' => true, 'message' => $lock['message']];
        }

        $oldData = $_SESSION['otp_data'][$context] ?? [];

        // Chống spam email: cùng một email phải chờ RESEND_COOLDOWN giây mới được gửi lại
        $elapsed = time() - (int)($oldData['sent_at'] ?? 0);
        if ($target !== '' && ($oldData['target'] ?? '') === $target && $elapsed < self::RESEND_COOLDOWN) {
            $wait = self::RESEND_COOLDOWN - $elapsed;
            return [
                'success'     => false,
                'cooldown'    => true,
                'retry_after' => $wait,
                'message'     => "Vui lòng đợi {$wait} giây trước khi gửi lại OTP.",
            ];
        }

        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $_SESSION['otp_data'][$context] = [
            'code'       => $otp,
            'expires_at' => time() + self::OTP_TTL,
            'sent_at'    => time(),
            'target'     => $target,
            'attempts'   => (int)($oldData['attempts'] ?? 0), // Giữ nguyên số lần sai để chống lách bằng nút Gửi lại
            'payload'    => !empty($payload) ? $payload : ($oldData['payload'] ?? []),
        ];

        return [
            'success'         => true,
            'otp'             => $otp,
            'expires_in'      => self::OTP_TTL,
            'resend_cooldown' => self::RESEND_COOLDOWN,
        ];
    }

    /**
     * Hủy OTP vừa tạo (dùng khi gửi email thất bại để người dùng có thể thử lại ngay).
     */
    public function discard(string $context): void
    {
        if (isset($_SESSION['otp_data'][$context])) {
            $_SESSION['otp_data'][$context]['sent_at']    = 0;
            $_SESSION['otp_data'][$context]['expires_at'] = 0;
        }
    }

    public function verify(string $context, string $inputCode): array
    {
        $lock = $this->checkLock($context);
        if ($lock['locked']) {
            return ['success' => false, 'locked' => true, 'message' => $lock['message']];
        }

        $data = $_SESSION['otp_data'][$context] ?? null;
        if (!$data) {
            return ['success' => false, 'expired' => true, 'message' => 'OTP không hợp lệ. Vui lòng nhấn gửi lại OTP.'];
        }

        // Kiểm tra hết hạn 1 phút
        if (time() > (int)$data['expires_at']) {
            return [
                'success' => false,
                'expired' => true,
                'message' => 'OTP không hợp lệ (đã hết hạn). Vui lòng nhấn gửi lại OTP.',
            ];
        }

        // Kiểm tra sai mã OTP
        if (!hash_equals((string)$data['code'], trim($inputCode))) {
            $attempts = (int)$data['attempts'] + 1;
            $_SESSION['otp_data'][$context]['attempts'] = $attempts;

            // Quá 5 lần sai -> khóa 5 phút
            if ($attempts > self::MAX_WRONG_TRIES) {
                $_SESSION['otp_lock'][$context] = time() + self::LOCK_SECONDS;
                unset($_SESSION['otp_data'][$context]);
                return [
                    'success' => false,
                    'locked'  => true,
                    'message' => 'Bạn đã nhập sai OTP quá 5 lần. Tính năng tạm khóa trong 5 phút.',
                ];
            }

            return [
                'success' => false,
                'message' => "Mã OTP không đúng, vui lòng nhập lại (sai {$attempts}/" . self::MAX_WRONG_TRIES . " lần).",
            ];
        }

        // Nhập đúng -> xóa session OTP và trả về dữ liệu đã lưu
        $payload = $data['payload'];
        unset($_SESSION['otp_data'][$context], $_SESSION['otp_lock'][$context]);

        return [
            'success' => true,
            'payload' => $payload,
        ];
    }
}
