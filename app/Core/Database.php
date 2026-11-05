<?php
/**
 * ==============================================================================
 * LỚP QUẢN LÝ KẾT NỐI CƠ SỞ DỮ LIỆU (DATABASE SINGLETON)
 * Vai trò:
 * - Khởi tạo và quản lý một kết nối PDO duy nhất trong suốt vòng đời của mỗi request.
 * - Tiết kiệm tài nguyên máy chủ, tránh mở nhiều kết nối gây quá tải cổng MySQL.
 * - Thiết lập chế độ chống tấn công SQL Injection bằng Prepared Statements.
 * ==============================================================================
 */
class Database {
    // Biến tĩnh lưu trữ instance kết nối PDO duy nhất
    private static ?PDO $instance = null;

    // Chặn khởi tạo đối tượng trực tiếp từ bên ngoài bằng 'new Database()'
    private function __construct() {}

    // Chặn sao chép đối tượng bằng cú pháp 'clone'
    private function __clone() {}

    /**
     * Lấy phiên kết nối CSDL PDO
     * @return PDO Đối tượng kết nối PDO đã cấu hình
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                // Tạo chuỗi kết nối DSN (Data Source Name) hỗ trợ bảng mã utf8mb4
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                
                // Cấu hình các thuộc tính bảo mật và định dạng trả về của PDO
                $options = [
                    // Kích hoạt chế độ tự động quăng Exception khi câu truy vấn SQL bị lỗi
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    
                    // Mặc định trả về dữ liệu dạng mảng kết hợp (Associative Array: $row['ten_cot'])
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    
                    // Tắt mô phỏng prepared statements để MySQL tự biên dịch, chống SQL Injection triệt để
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Dừng thực thi và thông báo nếu không thể kết nối tới cơ sở dữ liệu
                die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}