<?php
/**
 * ==============================================================================
 * TẬP TIN KHỞI ĐỘNG HỆ THỐNG (SINGLE ENTRY POINT / BOOTSTRAPPER)
 * Vai trò:
 * - Là cổng duy nhất tiếp nhận tất cả các request gửi đến ứng dụng web.
 * - Khởi tạo phiên làm việc (Session) để phục vụ kiểm soát đăng nhập và phân quyền.
 * - Nạp toàn bộ cấu hình toàn cục và các lớp lõi của mô hình MVC.
 * - Khởi chạy bộ định tuyến App để bắt đầu xử lý yêu cầu.
 * ==============================================================================
 */

// Khởi tạo Session nếu chưa được kích hoạt
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Nạp các tệp cấu hình hệ thống
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

// 2. Nạp các lớp nền tảng của bộ khung MVC
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Core/Controller.php';
require_once __DIR__ . '/app/Core/App.php';

// 3. Khởi chạy bộ định tuyến xử lý request
$app = new App();