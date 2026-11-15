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

    /**
     * Hồ sơ đầy đủ, kể cả tài khoản đang tắt. Có password_hash để đối chiếu khi đổi mật khẩu.
     */
    public function findById(int $userId): ?array {
        $sql = "SELECT u.user_id, u.user_code, u.full_name, u.email, u.password_hash, u.phone,
                       u.role, u.department_id, u.is_active, u.last_login_at, u.created_at, u.updated_at,
                       d.department_code, d.department_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.user_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Danh sách tài khoản cho trang Users Management. Không trả password_hash ra view.
     */
    public function all(): array {
        $sql = "SELECT u.user_id, u.user_code, u.full_name, u.email, u.phone, u.role,
                       u.department_id, u.is_active, u.last_login_at, u.created_at, u.updated_at,
                       d.department_code, d.department_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.department_id
                ORDER BY u.is_active DESC,
                         FIELD(u.role, 'Manager', 'Technician', 'Lecturer', 'Student'),
                         u.full_name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool {
        if ($excludeId === null) {
            $sql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
        } else {
            $sql = "SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email, $excludeId]);
        }
        return (bool) $stmt->fetch();
    }

    public function userCodeExists(string $userCode, ?int $excludeId = null): bool {
        if ($excludeId === null) {
            $sql = "SELECT user_id FROM users WHERE user_code = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userCode]);
        } else {
            $sql = "SELECT user_id FROM users WHERE user_code = ? AND user_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userCode, $excludeId]);
        }
        return (bool) $stmt->fetch();
    }

    public function insert(
        ?string $userCode,
        string $fullName,
        string $email,
        string $passwordHash,
        ?string $phone,
        string $role,
        ?int $departmentId,
        int $isActive = 1
    ): void {
        $sql = "INSERT INTO users
                    (user_code, full_name, email, password_hash, phone, role, department_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userCode, $fullName, $email, $passwordHash, $phone, $role, $departmentId, $isActive === 1 ? 1 : 0]);
    }

    public function updateInfo(
        int $userId,
        ?string $userCode,
        string $fullName,
        string $email,
        ?string $phone,
        string $role,
        ?int $departmentId,
        ?string $passwordHash = null
    ): void {
        if ($passwordHash === null) {
            $sql = "UPDATE users
                    SET user_code = ?, full_name = ?, email = ?, phone = ?, role = ?, department_id = ?
                    WHERE user_id = ?";
            $params = [$userCode, $fullName, $email, $phone, $role, $departmentId, $userId];
        } else {
            $sql = "UPDATE users
                    SET user_code = ?, full_name = ?, email = ?, phone = ?, role = ?,
                        department_id = ?, password_hash = ?
                    WHERE user_id = ?";
            $params = [$userCode, $fullName, $email, $phone, $role, $departmentId, $passwordHash, $userId];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function setActive(int $userId, int $isActive): void {
        $sql = "UPDATE users SET is_active = ? WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$isActive === 1 ? 1 : 0, $userId]);
    }

    /**
     * Người dùng tự sửa hồ sơ. Không đụng role, phòng ban, mã số hay is_active.
     */
    public function updateProfile(int $userId, string $fullName, string $email, ?string $phone, ?string $passwordHash = null): void {
        if ($passwordHash === null) {
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?";
            $params = [$fullName, $email, $phone, $userId];
        } else {
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, password_hash = ? WHERE user_id = ?";
            $params = [$fullName, $email, $phone, $passwordHash, $userId];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}