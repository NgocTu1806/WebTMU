<div id="otpModalOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center;"
     role="dialog" aria-modal="true" aria-labelledby="otpModalTitle">
    <div style="background:#fff; width:100%; max-width:400px; border-radius:10px; padding:24px; box-shadow:0 8px 24px rgba(0,0,0,0.2); text-align:center; font-family:sans-serif;">
        <h3 id="otpModalTitle" style="margin:0 0 8px; color:#1565C0;">Xác thực mã OTP</h3>
        <p id="otpSubText" style="font-size:14px; color:#555; margin:0 0 14px;">Đang gửi mã OTP tới email của bạn...</p>

        <div style="margin-bottom:14px; font-size:14px; font-weight:600;">
            Hiệu lực còn lại: <span id="otpCountdown" style="color:#D32F2F;">01:00</span>
        </div>

        <input type="text" id="otpCodeInput" maxlength="6" placeholder="••••••"
               inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}"
               style="width:75%; padding:10px; font-size:22px; letter-spacing:8px; text-align:center; border:2px solid #ccc; border-radius:8px; margin-bottom:12px;">

        <div id="otpFeedback" aria-live="polite" style="min-height:20px; font-size:13px; margin-bottom:14px; font-weight:500;"></div>

        <div style="display:flex; gap:10px;">
            <button type="button" id="btnVerifyOtp"
                    style="flex:1; padding:10px; background:#1565C0; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
                Xác nhận
            </button>
            <button type="button" id="btnResendOtp"
                    style="padding:10px 14px; background:#f1f3f5; color:#333; border:1px solid #ccc; border-radius:6px; cursor:pointer;">
                Gửi lại OTP
            </button>
        </div>

        <button type="button" id="btnCloseOtpModal"
                style="margin-top:12px; background:none; border:none; color:#777; font-size:13px; cursor:pointer; text-decoration:underline;">
            Đóng
        </button>
    </div>
</div>

<script>
const OtpManager = (() => {
    let expireTimer = null;
    let resendTimer = null;
    let sendUrl = '';
    let verifyUrl = '';
    let payload = {};
    let fallbackRedirect = '';
    let busy = false;

    const overlay     = document.getElementById('otpModalOverlay');
    const subText     = document.getElementById('otpSubText');
    const countdownEl = document.getElementById('otpCountdown');
    const inputEl     = document.getElementById('otpCodeInput');
    const feedbackEl  = document.getElementById('otpFeedback');
    const btnVerify   = document.getElementById('btnVerifyOtp');
    const btnResend   = document.getElementById('btnResendOtp');
    const btnClose    = document.getElementById('btnCloseOtpModal');
    const resendLabel = btnResend.textContent.trim();

    function setMsg(text, isError = true) {
        feedbackEl.style.color = isError ? '#D32F2F' : '#2E7D32';
        feedbackEl.textContent = text || '';
    }

    function setVerifyEnabled(enabled) {
        btnVerify.disabled = !enabled;
        btnVerify.style.opacity = enabled ? '1' : '0.5';
    }

    function formatTime(sec) {
        return String(Math.floor(sec / 60)).padStart(2, '0') + ':' + String(sec % 60).padStart(2, '0');
    }

    function startExpireTimer(seconds) {
        clearInterval(expireTimer);
        let left = seconds;
        setVerifyEnabled(true);
        countdownEl.textContent = formatTime(left);
        expireTimer = setInterval(() => {
            left--;
            if (left <= 0) {
                clearInterval(expireTimer);
                countdownEl.textContent = '00:00';
                setVerifyEnabled(false);
                setMsg('OTP không hợp lệ (đã hết hạn). Vui lòng nhấn gửi lại OTP.', true);
            } else {
                countdownEl.textContent = formatTime(left);
            }
        }, 1000);
    }

    // Khóa nút "Gửi lại OTP" trong thời gian chờ để tránh spam email
    function startResendCooldown(seconds) {
        clearInterval(resendTimer);
        let left = Math.max(0, parseInt(seconds, 10) || 0);
        const render = () => {
            btnResend.disabled = left > 0;
            btnResend.style.opacity = left > 0 ? '0.6' : '1';
            btnResend.textContent = left > 0 ? `${resendLabel} (${left}s)` : resendLabel;
        };
        render();
        if (left <= 0) return;
        resendTimer = setInterval(() => {
            left--;
            render();
            if (left <= 0) clearInterval(resendTimer);
        }, 1000);
    }

    function getCsrfToken() {
        const el = document.querySelector('input[name="_csrf_token"]');
        return el ? el.value : '';
    }

    async function postForm(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: new URLSearchParams(body)
        });
        try {
            return await res.json();
        } catch (e) {
            return { success: false, message: 'Phản hồi từ máy chủ không hợp lệ. Vui lòng thử lại.' };
        }
    }

    async function requestOtp() {
        if (busy) return;
        busy = true;
        btnResend.disabled = true;
        setMsg('Đang gửi mã OTP...', false);
        try {
            const data = await postForm(sendUrl, payload);
            if (data.success) {
                subText.textContent = data.message;
                setMsg('');
                inputEl.value = '';
                inputEl.focus();
                startExpireTimer(data.expires_in || 60);
                startResendCooldown(data.resend_cooldown || 30);
            } else if (data.cooldown) {
                // Đang trong thời gian chờ: giữ nguyên mã OTP cũ và bộ đếm hiện tại
                setMsg(data.message, true);
                startResendCooldown(data.retry_after);
            } else {
                clearInterval(expireTimer);
                subText.textContent = 'Không thể gửi mã OTP';
                countdownEl.textContent = '--:--';
                setVerifyEnabled(false);
                setMsg(data.message, true);
                startResendCooldown(0);
            }
        } catch (e) {
            setMsg('Lỗi kết nối máy chủ.', true);
            startResendCooldown(0);
        } finally {
            busy = false;
        }
    }

    async function verifyOtp() {
        if (busy || btnVerify.disabled) return;
        const otp = inputEl.value.trim();
        if (!/^\d{6}$/.test(otp)) {
            setMsg('Vui lòng nhập đủ 6 số OTP.', true);
            return;
        }
        busy = true;
        setVerifyEnabled(false);
        try {
            // Chỉ gửi mã OTP — dữ liệu form (kể cả mật khẩu) đã được lưu ở server khi gửi OTP
            const data = await postForm(verifyUrl, { otp });
            if (data.success) {
                clearInterval(expireTimer);
                clearInterval(resendTimer);
                setMsg(data.message, false);
                setTimeout(() => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else if (fallbackRedirect) {
                        window.location.href = fallbackRedirect;
                    } else {
                        window.location.reload();
                    }
                }, 1200);
                return;
            }
            setMsg(data.message, true);
            if (data.locked || data.expired) {
                clearInterval(expireTimer);
            } else {
                setVerifyEnabled(true);
                inputEl.value = '';
                inputEl.focus();
            }
        } catch (e) {
            setMsg('Lỗi kết nối máy chủ.', true);
            setVerifyEnabled(true);
        } finally {
            busy = false;
        }
    }

    function close() {
        clearInterval(expireTimer);
        clearInterval(resendTimer);
        overlay.style.display = 'none';
        inputEl.value = '';
        setMsg('');
        startResendCooldown(0);
    }

    function open(config) {
        sendUrl = config.sendUrl;
        verifyUrl = config.verifyUrl;
        payload = config.payload || {};
        fallbackRedirect = config.redirectUrl || '';
        inputEl.value = '';
        setMsg('');
        subText.textContent = 'Đang gửi mã OTP tới email của bạn...';
        countdownEl.textContent = '--:--';
        setVerifyEnabled(false);
        overlay.style.display = 'flex';
        requestOtp();
    }

    // Chỉ cho nhập chữ số, tối đa 6 ký tự
    inputEl.addEventListener('input', () => {
        inputEl.value = inputEl.value.replace(/\D/g, '').slice(0, 6);
    });
    // Nhấn Enter để xác nhận, Esc để đóng
    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); verifyOtp(); }
    });
    overlay.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });

    btnVerify.addEventListener('click', verifyOtp);
    btnResend.addEventListener('click', requestOtp);
    btnClose.addEventListener('click', close);

    return { open, close };
})();
</script>
