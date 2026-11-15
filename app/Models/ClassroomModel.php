<?php
/**
 * ==============================================================================
 * MODEL PHÒNG HỌC (CLASSROOM MODEL)
 * Vai trò: Đọc và ghi bảng classrooms; đọc số liệu từ assets, tickets.
 * Trang mẫu cho master data: liệt kê, thêm, sửa thông tin, đổi is_active.
 * Không thay đổi cấu trúc CSDL: chỉ dùng các cột sẵn có của classrooms, assets, tickets.
 * ==============================================================================
 */
class ClassroomModel {
    private PDO $db;

    /** Trạng thái phiếu được coi là "đang mở" (chưa xong). */
    private const OPEN_TICKET_STATUSES = "'Pending','Assigned','Received','In_Progress'";

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Toàn bộ phòng học kèm số thiết bị, số thiết bị hỏng và số phiếu đang mở.
     */
    public function all(): array {
        $sql = "SELECT c.classroom_id, c.room_number, c.building, c.floor, c.is_active,
                       c.created_at, c.updated_at,
                       (SELECT COUNT(*) FROM assets a
                         WHERE a.classroom_id = c.classroom_id AND a.status <> 'Decommissioned') AS asset_count,
                       (SELECT COUNT(*) FROM assets a
                         WHERE a.classroom_id = c.classroom_id AND a.status IN ('Faulty','Under_Repair')) AS faulty_count,
                       (SELECT COUNT(*) FROM tickets t
                         WHERE t.classroom_id = c.classroom_id AND t.status IN (" . self::OPEN_TICKET_STATUSES . ")) AS open_tickets
                FROM classrooms c
                ORDER BY c.floor ASC, c.room_number ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $classroomId): ?array {
        $sql = "SELECT classroom_id, room_number, building, floor, is_active, created_at, updated_at
                FROM classrooms
                WHERE classroom_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classroomId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Số liệu cho 3 thẻ thống kê đầu trang.
     */
    public function stats(): array {
        $assets = (int) $this->db->query("SELECT COUNT(*) FROM assets WHERE status <> 'Decommissioned'")->fetchColumn();
        $open = (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets WHERE status IN (" . self::OPEN_TICKET_STATUSES . ")"
        )->fetchColumn();
        return ['assets' => $assets, 'open_tickets' => $open];
    }

    /**
     * Danh sách thiết bị (tab All Assets), có thể lọc theo một phòng.
     */
    public function assets(?int $classroomId = null): array {
        $sql = "SELECT a.asset_id, a.asset_code, a.asset_name, a.status, a.fault_count,
                       c.classroom_id, c.room_number, c.floor, tc.category_name
                FROM assets a
                JOIN classrooms c ON c.classroom_id = a.classroom_id
                LEFT JOIN ticket_categories tc ON tc.category_id = a.category_id";
        $params = [];
        if ($classroomId !== null) {
            $sql .= " WHERE a.classroom_id = ?";
            $params[] = $classroomId;
        }
        $sql .= " ORDER BY c.floor ASC, c.room_number ASC, a.asset_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Các phiếu đang mở của một phòng (trang chi tiết).
     */
    public function openTickets(int $classroomId): array {
        $sql = "SELECT ticket_id, ticket_code, title, priority, status, created_at
                FROM tickets
                WHERE classroom_id = ? AND status IN (" . self::OPEN_TICKET_STATUSES . ")
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classroomId]);
        return $stmt->fetchAll();
    }

    public function roomNumberTaken(string $roomNumber, ?int $excludeId = null): bool {
        if ($excludeId === null) {
            $sql = "SELECT classroom_id FROM classrooms WHERE room_number = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roomNumber]);
        } else {
            $sql = "SELECT classroom_id FROM classrooms WHERE room_number = ? AND classroom_id <> ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roomNumber, $excludeId]);
        }
        return (bool) $stmt->fetch();
    }

    /**
     * Thêm phòng mới, trả về classroom_id vừa tạo.
     */
    public function insert(string $roomNumber, string $building, int $floor, int $isActive = 1): int {
        $sql = "INSERT INTO classrooms (room_number, building, floor, is_active)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roomNumber, $building, $floor, $isActive === 1 ? 1 : 0]);
        return (int) $this->db->lastInsertId();
    }

    public function updateInfo(int $classroomId, string $roomNumber, string $building, int $floor, int $isActive): void {
        $sql = "UPDATE classrooms
                SET room_number = ?, building = ?, floor = ?, is_active = ?
                WHERE classroom_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roomNumber, $building, $floor, $isActive === 1 ? 1 : 0, $classroomId]);
    }

    public function setActive(int $classroomId, int $isActive): void {
        $sql = "UPDATE classrooms SET is_active = ? WHERE classroom_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$isActive === 1 ? 1 : 0, $classroomId]);
    }
}
