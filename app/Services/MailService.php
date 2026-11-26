<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $mailCfg = Config::get('mail');
        if (!is_array($mailCfg) || empty($mailCfg)) {
            $config = defined('ROOT_PATH') && file_exists(ROOT_PATH . '/config/config.php')
                ? require ROOT_PATH . '/config/config.php'
                : [];
            $mailCfg = $config['mail'] ?? [];
        }

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host     = !empty($mailCfg['host']) ? $mailCfg['host'] : 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        // Không hardcode tài khoản SMTP trong mã nguồn — đọc từ config/mail.local.php hoặc biến môi trường
        $this->mailer->Username = (string)($mailCfg['username'] ?? '');
        $this->mailer->Password = (string)($mailCfg['password'] ?? '');

        $encryption = strtolower(!empty($mailCfg['encryption']) ? $mailCfg['encryption'] : 'tls');
        $this->mailer->SMTPSecure = $encryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        $this->mailer->Port    = (int)(!empty($mailCfg['port']) ? $mailCfg['port'] : 587);
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Timeout = 15; // Mặc định PHPMailer chờ 300s nếu SMTP không phản hồi -> treo request

        $fromEmail = !empty($mailCfg['from_address'])
            ? $mailCfg['from_address']
            : (!empty($mailCfg['from_email']) ? $mailCfg['from_email'] : ($this->mailer->Username ?: 'no-reply@tmu.edu.vn'));
        $fromName  = !empty($mailCfg['from_name']) ? $mailCfg['from_name'] : 'TMU Tài Liệu';
        $this->mailer->setFrom($fromEmail, $fromName);
    }

    public function sendOtpEmail(string $toEmail, string $fullName, string $otp, string $title = 'Xác thực OTP'): bool
    {
        $this->writeDevLog(sprintf('To: %s (%s) | Title: %s | OTP: %s', $toEmail, $fullName, $title, $otp));

        $safeName  = $this->esc($fullName);
        $safeTitle = $this->esc($title);
        $safeOtp   = $this->esc($otp);

        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px;'>
                <h2 style='color: #1565C0; margin-top: 0;'>{$safeTitle}</h2>
                <p>Xin chào <strong>{$safeName}</strong>,</p>
                <p>Mã xác thực OTP của bạn là:</p>
                <div style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #D32F2F; background: #F5F5F5; padding: 12px; text-align: center; border-radius: 6px;'>
                    {$safeOtp}
                </div>
                <p style='color: #555; font-size: 13px; margin-top: 15px;'>
                    * Mã có hiệu lực trong vòng <strong>1 phút (60 giây)</strong>.<br>
                    * Tuyệt đối không chia sẻ mã này cho bất kỳ ai.
                </p>
            </div>
        ";

        // Không đưa mã OTP vào tiêu đề (tránh lộ mã trên thông báo xem trước của điện thoại)
        return $this->send($toEmail, $fullName, "[TMU Tài Liệu] {$title}", $body, 'OTP');
    }

    public function sendResetLinkEmail(string $toEmail, string $fullName, string $resetLink): bool
    {
        $this->writeDevLog(sprintf('To: %s (%s) | ResetLink: %s', $toEmail, $fullName, $resetLink));

        $safeName = $this->esc($fullName);
        $safeLink = $this->esc($resetLink);

        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px;'>
                <h2 style='color: #1565C0; margin-top: 0;'>Đặt lại mật khẩu</h2>
                <p>Xin chào <strong>{$safeName}</strong>,</p>
                <p>Nhấn vào nút bên dưới để đặt lại mật khẩu cho tài khoản của bạn (liên kết có hiệu lực trong 1 giờ):</p>
                <p style='text-align: center; margin: 24px 0;'>
                    <a href='{$safeLink}' style='background: #1565C0; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>
                        Đặt lại mật khẩu ngay
                    </a>
                </p>
                <p style='font-size: 12px; color: #777;'>Nếu nút không hoạt động, copy đường dẫn sau vào trình duyệt:<br>{$safeLink}</p>
                <p style='font-size: 12px; color: #777;'>Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.</p>
            </div>
        ";

        return $this->send($toEmail, $fullName, '[TMU Tài Liệu] Yêu cầu đặt lại mật khẩu', $body, 'ResetLink');
    }

    /**
     * Gửi email HTML dùng chung cho mọi loại thư.
     */
    private function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $logTag): bool
    {
        if (!$this->isConfigured()) {
            error_log("[MailService] Chưa cấu hình tài khoản SMTP (config/mail.local.php hoặc biến môi trường MAIL_USERNAME/MAIL_PASSWORD).");
            return false;
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $htmlBody;
            $this->mailer->AltBody = trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("MailService {$logTag} Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    private function isConfigured(): bool
    {
        return $this->mailer->Username !== ''
            && $this->mailer->Password !== ''
            && !str_contains($this->mailer->Username, 'email_cua_ban');
    }

    /**
     * Ghi OTP / link reset ra file log CHỈ ở môi trường development (hỗ trợ test khi chưa có SMTP).
     */
    private function writeDevLog(string $line): void
    {
        $env   = (string)Config::get('app.env', 'production');
        $debug = (bool)Config::get('app.debug', false);
        if (!$debug || !in_array($env, ['development', 'local'], true)) {
            return;
        }

        $logDir = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logDir . '/otp.log', '[' . date('Y-m-d H:i:s') . "] {$line}\n", FILE_APPEND | LOCK_EX);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
