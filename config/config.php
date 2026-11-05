<?php
/**
 * ==============================================================================
 * TẬP TIN HẰNG SỐ TOÀN CỤC CỦA HỆ THỐNG
 * Vai trò:
 * - Định nghĩa đường dẫn tuyệt đối trên web và đường dẫn gốc trên ổ đĩa máy chủ.
 * - Tránh hoàn toàn lỗi gãy liên kết (broken links) khi nhúng CSS, JS, ảnh hoặc require file.
 * ==============================================================================
 */

// ĐƯỜNG DẪN URL TRUY CẬP TRÊN TRÌNH DUYỆT (Dùng cho thẻ <link>, <script>, <a href>, form action)
// Lưu ý: Đổi tên thư mục 'INS3064-G03-Campus-Helpdesk' nếu đặt tên khác trong htdocs
define('BASE_URL', 'http://localhost:8080/INS3064-G03-Campus-Helpdesk');

// ĐƯỜNG DẪN VẬT LÝ TRÊN Ổ ĐĨA MÁY CHỦ (Dùng cho các lệnh require, include trong PHP)
// dirname(dirname(__FILE__)) sẽ trỏ ngược về thư mục gốc của dự án
define('APPROOT', dirname(dirname(__FILE__)));