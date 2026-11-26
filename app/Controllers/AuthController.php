<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\AuthService;
use App\Services\MailService;
use App\Services\OtpService;

class AuthController extends Controller
{
    private const FORGOT_COOLDOWN = 60; // Giây tối thiểu giữa 2 lần gửi email đặt lại mật khẩu

    private AuthService $authService;
    private User $userModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel   = new User();
    }

    // =========================================================================
    // ĐĂNG NHẬP
    // =========================================================================

    public function loginForm(array $params = []): void
    {
        $this->redirectIfAuthenticated();
        $this->view('auth.login', [
            'title'      => 'Đăng nhập — TMU Tài Liệu',
            'return_url' => $this->safeReturnUrl($_GET['return'] ?? ''),
        ]);
    }

    public function login(array $params = []): void
    {
        $this->redirectIfAuthenticated();
        $this->csrfValidate();

        $email     = strtolower(trim($_POST['email'] ?? ''));
        $password  = $_POST['password'] ?? '';
        $remember  = !empty($_POST['remember']);
        $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? $_GET['return'] ?? '');
        $loginUrl  = '/login' . ($returnUrl !== '' ? '?return=' . urlencode($returnUrl) : '');

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

        clearOldInput();
        $fullName = e(trim((string)($result['user']['full_name'] ?? '')));
        flash('success', 'Chào mừng bạn quay lại, ' . $fullName . '!');

        $this->redirect($returnUrl !== '' ? $returnUrl : '/');
    }

    // =========================================================================
    // ĐĂNG KÝ (bắt buộc xác thực email bằng OTP)
    // =========================================================================

    public function registerForm(array $params = []): void
    {
        $this->redirectIfAuthenticated();
        $this->view('auth.register', [
            'title' => 'Đăng ký tài khoản — TMU Tài Liệu',
        ]);
    }

    /**
     * POST /register (submit form thường, không qua OTP).
     * Trước đây route này tạo tài khoản trực tiếp => có thể bỏ qua bước xác thực email.
     * Nay chỉ kiểm tra dữ liệu và yêu cầu người dùng hoàn tất bước OTP.
     */
    public function register(array $params = []): void
    {
        $this->redirectIfAuthenticated();
        $this->csrfValidate();

        $data   = $this->registerInput();
        $errors = $this->validateRegistration($data);

        storeOldInput();
        if (!empty($errors)) {
            flash('errors', serialize($errors));
        } else {
            flash('error', 'Vui lòng bật JavaScript và xác thực email bằng mã OTP để hoàn tất đăng ký.');
        }
        $this->redirect('/register');
    }

    public function registerSendOtp(array $params = []): void
    {
        try {
            if (auth()) {
                $this->json(['success' => false, 'message' => 'Bạn đã đăng nhập.']);
            }
            $this->csrfValidate();

            $data   = $this->registerInput();
            $errors = $this->validateRegistration($data);
            if (!empty($errors)) {
                $this->json(['success' => false, 'message' => $this->firstError($errors)]);
            }

            // Chỉ lưu mật khẩu ĐÃ BĂM vào session, không giữ mật khẩu gốc
            $payload = [
                'username'      => $data['username'],
                'email'         => $data['email'],
                'full_name'     => $data['full_name'],
                'student_id'    => $data['student_id'],
                'password_hash' => AuthService::hashPassword($data['password']),
            ];

            $this->sendOtp('register', $payload, $data['email'], $data['full_name'], 'Xác thực đăng ký');
        } catch (\Throwable $e) {
            $this->jsonSystemError($e);
        }
    }

    public function registerVerifyOtp(array $params = []): void
    {
        try {
            if (auth()) {
                $this->json(['success' => false, 'message' => 'Bạn đã đăng nhập.']);
            }
            $this->csrfValidate();

            $verify = (new OtpService())->verify('register', (string)($_POST['otp'] ?? ''));
            if (!$verify['success']) {
                $this->json($verify);
            }

            $result = $this->authService->register($verify['payload']);
            if (!$result['success']) {
                // VD: trong lúc chờ OTP, người khác đã đăng ký trùng email / username / MSV
                $this->json(['success' => false, 'expired' => true, 'message' => $this->firstError($result['errors'])]);
            }

            clearOldInput();
            flash('success', 'Đăng ký thành công! Chào mừng bạn đến với TMU Tài Liệu.');
            $this->json([
                'success'      => true,
                'message'      => 'Đăng ký thành công! Đang chuyển hướng...',
                'redirect_url' => url('/'),
            ]);
        } catch (\Throwable $e) {
            $this->jsonSystemError($e);
        }
    }

    // =========================================================================
    // ĐĂNG XUẤT
    // =========================================================================

    public function logout(array $params = []): void
    {
        $this->csrfValidate();
        $this->authService->logout();

        // logout() đã hủy session -> mở session MỚI (id mới) để thông báo không bị mất
        if (session_status() === PHP_SESSION_NONE) {
            session_id(session_create_id());
            session_start();
        }
        flash('success', 'Bạn đã đăng xuất thành công.');
        $this->redirect('/login');
    }

    // =========================================================================
    // QUÊN MẬT KHẨU / ĐẶT LẠI MẬT KHẨU
    // =========================================================================

    public function forgotForm(array $params = []): void
    {
        $this->redirectIfAuthenticated();
        $this->view('auth.forgot_password', ['title' => 'Quên mật khẩu — TMU Tài Liệu']);
    }

    public function forgot(array $params = []): void
    {
        $this->redirectIfAuthenticated();
        $this->csrfValidate();

        $email  = strtolower(trim($_POST['email'] ?? ''));
        $errors = $this->validate(['email' => $email], ['email' => 'required|email']);
        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect('/forgot-password');
        }

        // Chống spam email: mỗi phiên chỉ được gửi 1 lần / FORGOT_COOLDOWN giây
        $elapsed = time() - (int)($_SESSION['forgot_sent_at'] ?? 0);
        if ($elapsed < self::FORGOT_COOLDOWN) {
            storeOldInput();
            flash('error', 'Vui lòng đợi ' . (self::FORGOT_COOLDOWN - $elapsed) . ' giây trước khi gửi lại yêu cầu.');
            $this->redirect('/forgot-password');
        }

        $reset = $this->authService->sendPasswordReset($email);
        if ($reset === null) {
            storeOldInput();
            flash('error', 'Địa chỉ email này không tồn tại. Vui lòng kiểm tra lại!');
            $this->redirect('/forgot-password');
        }

        $sent = (new MailService())->sendResetLinkEmail(
            $email,
            (string)($reset['user']['full_name'] ?? 'Sinh viên TMU'),
            url('/reset-password/' . $reset['token'])
        );

        if (!$sent) {
            storeOldInput();
            flash('error', 'Không thể gửi email lúc này. Vui lòng kiểm tra cấu hình SMTP và thử lại sau.');
            $this->redirect('/forgot-password');
        }

        $_SESSION['forgot_sent_at'] = time();
        clearOldInput();
        flash('success', 'Liên kết đặt lại mật khẩu (hiệu lực 1 giờ) đã được gửi tới email của bạn. Vui lòng kiểm tra hộp thư đến hoặc mục Spam.');
        $this->redirect('/forgot-password');
    }

    public function resetForm(array $params = []): void
    {
        $this->redirectIfAuthenticated();

        // Kiểm tra token ngay khi mở link, tránh để người dùng nhập mật khẩu rồi mới báo lỗi
        $token = (string)($params['token'] ?? '');
        if (!$this->authService->isValidResetToken($token)) {
            flash('error', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng gửi lại yêu cầu.');
            $this->redirect('/forgot-password');
        }

        $this->view('auth.reset_password', [
            'title' => 'Đặt lại mật khẩu — TMU Tài Liệu',
            'token' => $token,
        ]);
    }

    public function reset(array $params = []): void
    {
        $this->redirectIfAuthenticated();
        $this->csrfValidate();

        $token    = (string)($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors   = $this->validate(
            ['password' => $password, 'password_confirmation' => $_POST['password_confirmation'] ?? ''],
            ['password' => 'required|password_rules|confirmed']
        );

        if (!empty($errors)) {
            flash('errors', serialize($errors));
            $this->redirect('/reset-password/' . urlencode($token));
        }

        if (!$this->authService->resetPassword($token, $password)) {
            flash('error', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
            $this->redirect('/forgot-password');
        }

        flash('success', 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.');
        $this->redirect('/login');
    }

    // =========================================================================
    // ĐỔI MẬT KHẨU (đã đăng nhập) VỚI OTP
    // =========================================================================

    public function changePasswordSendOtp(array $params = []): void
    {
        try {
            if (!auth()) {
                $this->json(['success' => false, 'message' => 'Vui lòng đăng nhập lại.'], 401);
            }
            $this->csrfValidate();

            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password'] ?? '';
            $confirm     = $_POST['new_password_confirmation'] ?? '';

            $user = $this->userModel->findById((int)userId());
            if (!$user) {
                $this->json(['success' => false, 'message' => 'Người dùng không tồn tại.']);
            }

            if (!password_verify($currentPass, $user['password_hash'])) {
                $this->json(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng.']);
            }

            $errors = $this->validate(
                ['password' => $newPass, 'password_confirmation' => $confirm],
                ['password' => 'required|password_rules|confirmed']
            );
            if (!empty($errors)) {
                $this->json(['success' => false, 'message' => $this->firstError($errors)]);
            }

            if (password_verify($newPass, $user['password_hash'])) {
                $this->json(['success' => false, 'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại.']);
            }

            $this->sendOtp('change_password', [
                'user_id'       => (int)$user['id'],
                'password_hash' => AuthService::hashPassword($newPass),
            ], (string)$user['email'], (string)$user['full_name'], 'Xác nhận đổi mật khẩu');
        } catch (\Throwable $e) {
            $this->jsonSystemError($e);
        }
    }

    public function changePasswordVerifyOtp(array $params = []): void
    {
        try {
            if (!auth()) {
                $this->json(['success' => false, 'message' => 'Vui lòng đăng nhập lại.'], 401);
            }
            $this->csrfValidate();

            $verify = (new OtpService())->verify('change_password', (string)($_POST['otp'] ?? ''));
            if (!$verify['success']) {
                $this->json($verify);
            }

            // Đảm bảo OTP thuộc đúng tài khoản đang đăng nhập
            $payload = $verify['payload'];
            if ((int)($payload['user_id'] ?? 0) !== (int)userId()) {
                $this->json(['success' => false, 'expired' => true, 'message' => 'Phiên xác thực không hợp lệ. Vui lòng thử lại.']);
            }

            // Trước đây gọi $db->prepare() (không tồn tại trong Database) => luôn lỗi
            $this->userModel->updatePassword((int)$payload['user_id'], (string)$payload['password_hash']);

            flash('success', 'Đổi mật khẩu thành công!');
            $this->json([
                'success'      => true,
                'message'      => 'Đổi mật khẩu thành công!',
                'redirect_url' => url('/profile/edit'),
            ]);
        } catch (\Throwable $e) {
            $this->jsonSystemError($e);
        }
    }

    // =========================================================================
    // HÀM DÙNG CHUNG
    // =========================================================================

    private function redirectIfAuthenticated(): void
    {
        if (auth()) {
            $this->redirect('/');
        }
    }

    /**
     * Chỉ chấp nhận đường dẫn nội bộ dạng "/path" (chặn "//evil.com", "/\evil.com", "http://...").
     */
    private function safeReturnUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url[0] !== '/' || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return '';
        }
        return $url;
    }

    /** Chuẩn hóa dữ liệu form đăng ký. */
    private function registerInput(): array
    {
        return [
            'username'              => trim($_POST['username'] ?? ''),
            'email'                 => strtolower(trim($_POST['email'] ?? '')),
            'full_name'             => preg_replace('/\s+/u', ' ', trim($_POST['full_name'] ?? '')),
            'password'              => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? '',
            'student_id'            => strtoupper(trim($_POST['student_id'] ?? '')),
        ];
    }

    /** Validate định dạng + kiểm tra trùng lặp trong CSDL (dùng chung cho register & registerSendOtp). */
    private function validateRegistration(array $data): array
    {
        $errors = $this->validate($data, [
            'full_name'  => 'required|min:2|max:100|vietnamese_name',
            'student_id' => 'required|tmu_student_id',
            'email'      => 'required|email|tmu_student_email',
            'username'   => 'required|custom_username',
            'password'   => 'required|password_rules|confirmed',
        ]);

        if (empty($errors['student_id']) && $this->userModel->studentIdExists($data['student_id'])) {
            $errors['student_id'][] = 'Mã sinh viên này đã được đăng ký trên hệ thống.';
        }
        if (empty($errors['username']) && $this->userModel->usernameExists($data['username'])) {
            $errors['username'][] = 'Tên đăng nhập này đã được sử dụng.';
        }
        if (empty($errors['email']) && $this->userModel->emailExists($data['email'])) {
            $errors['email'][] = 'Email này đã được sử dụng.';
        }

        return $errors;
    }

    /** Sinh OTP, gửi email và trả JSON (dùng chung cho đăng ký & đổi mật khẩu). */
    private function sendOtp(string $context, array $payload, string $email, string $fullName, string $title): void
    {
        $otpService = new OtpService();
        $gen = $otpService->generate($context, $payload, $email);
        if (!$gen['success']) {
            unset($gen['otp']);
            $this->json($gen);
        }

        if (!(new MailService())->sendOtpEmail($email, $fullName, $gen['otp'], $title)) {
            $otpService->discard($context); // Cho phép thử gửi lại ngay, không bị tính thời gian chờ
            $this->json(['success' => false, 'message' => 'Không thể gửi email OTP. Vui lòng thử lại sau.']);
        }

        $this->json([
            'success'         => true,
            'expires_in'      => $gen['expires_in'],
            'resend_cooldown' => $gen['resend_cooldown'],
            'message'         => "Mã OTP đã được gửi tới {$email}.",
        ]);
    }

    private function firstError(array $errors): string
    {
        $first = reset($errors);
        return (string)(is_array($first) ? ($first[0] ?? 'Dữ liệu không hợp lệ.') : $first);
    }

    /** Ghi log lỗi thật, chỉ trả thông báo chung cho client (không lộ chi tiết hệ thống). */
    private function jsonSystemError(\Throwable $e): void
    {
        error_log('[AuthController] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        $this->json(['success' => false, 'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.'], 500);
    }
}
