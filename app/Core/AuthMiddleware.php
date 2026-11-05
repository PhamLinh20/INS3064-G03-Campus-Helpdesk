<?php
/**
 * ==============================================================================
 * BỘ KIỂM SOÁT PHÂN QUYỀN TRUY CẬP (RBAC MIDDLEWARE)
 * Vai trò:
 * - Kiểm tra trạng thái đăng nhập của người dùng.
 * - Chặn truy cập trái phép nếu không đúng vai trò (Role).
 * ==============================================================================
 */
class AuthMiddleware {
    /**
     * Bắt buộc phải đăng nhập mới được vào trang
     */
    public static function requireLogin(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }

    /**
     * Bắt buộc phải có đúng Vai trò (Role) mới được vào trang
     * @param array $allowedRoles Danh sách vai trò được phép (Ví dụ: ['Manager', 'Technician'])
     */
    public static function requireRole(array $allowedRoles): void {
        self::requireLogin();

        if (!in_array($_SESSION['role'], $allowedRoles, true)) {
            // Chuyển hướng về trang báo lỗi 403 Forbidden hoặc trang tương ứng với vai trò của họ
            http_response_code(403);
            die("Lỗi 403: Bạn không có quyền truy cập vào phân hệ này.");
        }
    }

    /**
     * Chuyển hướng người dùng đã đăng nhập về đúng trang chủ theo Role
     */
    public static function redirectIfLoggedIn(): void {
        if (isset($_SESSION['user_id'], $_SESSION['role'])) {
            switch ($_SESSION['role']) {
                case 'Manager':
                    header('Location: ' . BASE_URL . '/manager/tickets');
                    exit;
                case 'Technician':
                    header('Location: ' . BASE_URL . '/technician/dashboard');
                    exit;
                default: // Student, Lecturer
                    header('Location: ' . BASE_URL . '/user/dashboard');
                    exit;
            }
        }
    }
}