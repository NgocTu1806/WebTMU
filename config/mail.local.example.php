<?php
// Copy file này thành config/mail.local.php rồi điền thông tin SMTP thật.
// config/mail.local.php đã nằm trong .gitignore nên sẽ không bị đẩy lên GitHub.
return [
    'username'   => 'your_email@gmail.com',
    'password'   => 'xxxx xxxx xxxx xxxx', // Mật khẩu ứng dụng Google (16 ký tự)
    'from_email' => 'your_email@gmail.com',
];
