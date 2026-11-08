<?php
/**
 * ==============================================================================
 * TẬP TIN HẰNG SỐ TOÀN CỤC CỦA HỆ THỐNG
 * Vai trò:
 * - Định nghĩa đường dẫn tuyệt đối trên web và đường dẫn gốc trên ổ đĩa máy chủ.
 * - Tự động nhận diện cổng mạng (Port 80, 8080, 3000,...) để mọi thành viên chạy không bị lỗi.
 * ==============================================================================
 */

// ĐƯỜNG DẪN URL TRUY CẬP TRÊN TRÌNH DUYỆT (Tự động nhận diện Port & Host của từng máy)
if (!defined('BASE_URL')) {
    if (isset($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST']; // Bao gồm cả port nếu có: localhost:8080, localhost, localhost:3000
        
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $projectName = 'INS3064-G03-Campus-Helpdesk';
        
        // Kiểm tra xem request có đi qua subfolder của dự án trong htdocs không
        if (stripos($scriptName, '/' . $projectName) !== false) {
            $basePath = '/' . $projectName;
        } else {
            // Khi chạy bằng built-in PHP server (VS Code) trỏ thẳng vào thư mục gốc dự án
            $basePath = '';
        }
        
        define('BASE_URL', $protocol . $host . $basePath);
    } else {
        // Fallback mặc định khi chạy qua PHP CLI
        define('BASE_URL', 'http://localhost:8080/INS3064-G03-Campus-Helpdesk');
    }
}

// ĐƯỜNG DẪN VẬT LÝ TRÊN Ổ ĐĨA MÁY CHỦ (Dùng cho các lệnh require, include trong PHP)
if (!defined('APPROOT')) {
    define('APPROOT', dirname(__DIR__));
}
