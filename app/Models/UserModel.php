<?php
/**
 * ==============================================================================
 * MODEL NGƯỜI DÙNG (USER MODEL)
 * Vai trò: Thực hiện toàn bộ thao tác truy vấn CSDL liên quan đến bảng users.
 * ==============================================================================
 */
class UserModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Tìm tài khoản người dùng theo email đang hoạt động
     */
    public function findUserByEmail(string $email): ?array {
        $sql = "SELECT u.*, d.department_name, d.department_code 
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.email = :email AND u.is_active = 1 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Cập nhật mốc thời gian đăng nhập gần nhất
     */
    public function updateLastLogin(int $userId): void {
        $sql = "UPDATE users SET last_login_at = NOW() WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
    }
}