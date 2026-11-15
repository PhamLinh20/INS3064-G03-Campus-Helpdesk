<?php
/**
 * ==============================================================================
 * MODEL DANH MỤC LỖI / DỊCH VỤ (CATEGORY MODEL)
 * Vai trò: Đọc và ghi bảng ticket_categories, kèm danh sách departments để gán FK.
 * Nhân bản thao tác của ClassroomModel, đổi tên cột SQL.
 * ==============================================================================
 */
class CategoryModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Danh mục dịch vụ (ticket_categories) kèm phòng ban phụ trách.
     */
    public function all(): array {
        $sql = "SELECT c.category_id, c.department_id, c.category_code, c.category_name,
                       c.description, c.routing_keywords, c.default_priority,
                       c.sla_response_minutes, c.sla_resolution_minutes, c.is_active,
                       c.created_at, c.updated_at,
                       d.department_code, d.department_name
                FROM ticket_categories c
                INNER JOIN departments d ON c.department_id = d.department_id
                ORDER BY c.is_active DESC, d.department_code ASC, c.category_name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $categoryId): ?array {
        $sql = "SELECT c.category_id, c.department_id, c.category_code, c.category_name,
                       c.description, c.routing_keywords, c.default_priority,
                       c.sla_response_minutes, c.sla_resolution_minutes, c.is_active,
                       d.department_code, d.department_name
                FROM ticket_categories c
                INNER JOIN departments d ON c.department_id = d.department_id
                WHERE c.category_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Phòng ban dùng cho dropdown của danh mục dịch vụ và tài khoản.
     */
    public function departments(): array {
        $sql = "SELECT department_id, department_code, department_name, is_active
                FROM departments
                ORDER BY is_active DESC, department_name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function findDepartment(int $departmentId): ?array {
        $sql = "SELECT department_id, department_code, department_name, is_active
                FROM departments
                WHERE department_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$departmentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function codeTaken(string $categoryCode, ?int $excludeId = null): bool {
        if ($excludeId === null) {
            $sql = "SELECT category_id FROM ticket_categories WHERE category_code = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryCode]);
        } else {
            $sql = "SELECT category_id FROM ticket_categories WHERE category_code = ? AND category_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryCode, $excludeId]);
        }
        return (bool) $stmt->fetch();
    }

    public function nameTaken(int $departmentId, string $categoryName, ?int $excludeId = null): bool {
        if ($excludeId === null) {
            $sql = "SELECT category_id FROM ticket_categories
                    WHERE department_id = ? AND category_name = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$departmentId, $categoryName]);
        } else {
            $sql = "SELECT category_id FROM ticket_categories
                    WHERE department_id = ? AND category_name = ? AND category_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$departmentId, $categoryName, $excludeId]);
        }
        return (bool) $stmt->fetch();
    }

    public function insert(
        int $departmentId,
        string $categoryCode,
        string $categoryName,
        ?string $description,
        ?string $routingKeywords,
        string $defaultPriority,
        int $slaResponseMinutes,
        int $slaResolutionMinutes
    ): void {
        $sql = "INSERT INTO ticket_categories
                    (department_id, category_code, category_name, description, routing_keywords,
                     default_priority, sla_response_minutes, sla_resolution_minutes, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $departmentId,
            $categoryCode,
            $categoryName,
            $description,
            $routingKeywords,
            $defaultPriority,
            $slaResponseMinutes,
            $slaResolutionMinutes,
        ]);
    }

    public function updateInfo(
        int $categoryId,
        int $departmentId,
        string $categoryCode,
        string $categoryName,
        ?string $description,
        ?string $routingKeywords,
        string $defaultPriority,
        int $slaResponseMinutes,
        int $slaResolutionMinutes
    ): void {
        $sql = "UPDATE ticket_categories
                SET department_id = ?, category_code = ?, category_name = ?, description = ?,
                    routing_keywords = ?, default_priority = ?,
                    sla_response_minutes = ?, sla_resolution_minutes = ?
                WHERE category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $departmentId,
            $categoryCode,
            $categoryName,
            $description,
            $routingKeywords,
            $defaultPriority,
            $slaResponseMinutes,
            $slaResolutionMinutes,
            $categoryId,
        ]);
    }

    public function setActive(int $categoryId, int $isActive): void {
        $sql = "UPDATE ticket_categories SET is_active = ? WHERE category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$isActive === 1 ? 1 : 0, $categoryId]);
    }

    // ------------------------------------------------------------------
    // Phòng ban (departments): đọc, thêm, sửa, bật/tắt, xóa. Không đổi cấu trúc bảng.
    // ------------------------------------------------------------------

    /** Danh sách phòng ban kèm số danh mục đang liên kết. */
    public function departmentsFull(): array {
        $sql = "SELECT d.department_id, d.department_code, d.department_name, d.description,
                       d.contact_email, d.contact_phone, d.is_active, d.created_at,
                       (SELECT COUNT(*) FROM ticket_categories c WHERE c.department_id = d.department_id) AS category_count,
                       (SELECT COUNT(*) FROM ticket_categories c WHERE c.department_id = d.department_id AND c.is_active = 1) AS active_category_count
                FROM departments d
                ORDER BY d.department_id ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function departmentCodeTaken(string $code, ?int $excludeId = null): bool {
        $stmt = $this->db->prepare("SELECT department_id FROM departments WHERE department_code = ? AND department_id <> ? LIMIT 1");
        $stmt->execute([$code, $excludeId ?? 0]);
        return (bool) $stmt->fetch();
    }

    public function departmentNameTaken(string $name, ?int $excludeId = null): bool {
        $stmt = $this->db->prepare("SELECT department_id FROM departments WHERE department_name = ? AND department_id <> ? LIMIT 1");
        $stmt->execute([$name, $excludeId ?? 0]);
        return (bool) $stmt->fetch();
    }

    public function insertDepartment(string $code, string $name, ?string $description, ?string $email, ?string $phone, int $isActive = 1): void {
        $stmt = $this->db->prepare("INSERT INTO departments
                    (department_code, department_name, description, contact_email, contact_phone, is_active)
                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $description, $email, $phone, $isActive === 1 ? 1 : 0]);
    }

    public function updateDepartment(int $id, string $code, string $name, ?string $description, ?string $email, ?string $phone, int $isActive): void {
        $stmt = $this->db->prepare("UPDATE departments
                SET department_code = ?, department_name = ?, description = ?, contact_email = ?, contact_phone = ?, is_active = ?
                WHERE department_id = ?");
        $stmt->execute([$code, $name, $description, $email, $phone, $isActive === 1 ? 1 : 0, $id]);
    }

    public function setDepartmentActive(int $id, int $isActive): void {
        $stmt = $this->db->prepare("UPDATE departments SET is_active = ? WHERE department_id = ?");
        $stmt->execute([$isActive === 1 ? 1 : 0, $id]);
    }

    /** Xóa hẳn phòng ban; CSDL sẽ chặn (lỗi 1451) nếu còn danh mục/tài khoản/phiếu tham chiếu. */
    public function deleteDepartment(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->execute([$id]);
    }

    /** Xóa hẳn danh mục; CSDL sẽ chặn nếu đã có phiếu hoặc thiết bị dùng danh mục này. */
    public function delete(int $categoryId): void {
        $stmt = $this->db->prepare("DELETE FROM ticket_categories WHERE category_id = ?");
        $stmt->execute([$categoryId]);
    }
}
