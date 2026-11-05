<?php
/**
 * ==============================================================================
 * TẬP TIN THIẾT LẬP THÔNG SỐ KẾT NỐI CƠ SỞ DỮ LIỆU
 * Vai trò: 
 * - Khai báo tập trung các hằng số cấu hình tài khoản và CSDL MySQL/MariaDB trên XAMPP.
 * - Giúp dễ dàng thay đổi môi trường làm việc giữa các máy mà không phải sửa code core.
 * ==============================================================================
 */

// Địa chỉ máy chủ CSDL (mặc định môi trường local là localhost hoặc 127.0.0.1)
define('DB_HOST', 'localhost');

// Tên người dùng truy cập CSDL trên XAMPP (mặc định: root)
define('DB_USER', 'root');

// Mật khẩu người dùng (trên XAMPP mặc định để trống)
define('DB_PASS', '');

// Tên cơ sở dữ liệu đã import file campus_helpdesk.sql
define('DB_NAME', 'campus_helpdesk');

// Cổng mạng dịch vụ MySQL (mặc định: 3306)
define('DB_PORT', '3306');