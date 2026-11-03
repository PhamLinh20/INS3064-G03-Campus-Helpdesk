-- ============================================================================
-- CAMPUS HELPDESK – VERTICAL SLICE STATISTICAL QUERIES (stats.sql)
-- Target Database : campus_helpdesk_midterm
-- Vertical Slice  : classrooms (Main Table) & assets (Related Table)
-- ============================================================================

USE `campus_helpdesk_midterm`;

-- ============================================================================
-- QUERY 1: INNER JOIN
-- ============================================================================
-- [EN Question]: Which active assets are currently deployed in each classroom?
-- [VI Câu hỏi ]: Những thiết bị nào hiện đang được lắp đặt tại từng phòng học cụ thể?
-- ----------------------------------------------------------------------------
SELECT 
    c.room_number,
    c.building,
    c.floor,
    a.asset_code,
    a.asset_name,
    a.status AS asset_status
FROM classrooms c
INNER JOIN assets a ON c.classroom_id = a.classroom_id
ORDER BY c.room_number ASC, a.asset_code ASC;

/*
[EN Result & Explanation]:
- Result     : 11 rows returned; lists all active equipment mapped to specific classrooms (e.g., room A1-201 contains 3 assets).
- Explanation: INNER JOIN matches records present in both tables; classrooms without assigned assets are automatically excluded.

[VI Kết quả & Giải thích viva]:
- Kết quả    : Trả về 11 dòng; thể hiện danh sách thiết bị gắn với mã phòng tương ứng (phòng chưa có thiết bị sẽ không xuất hiện).
- Giải thích : Mệnh đề INNER JOIN chỉ lấy các bản ghi có khóa ngoại trùng khớp ở cả 2 bảng; các phòng trống tự động bị lọc bỏ.
*/


-- ============================================================================
-- QUERY 2: LEFT JOIN + COUNT(column) + GROUP BY (With Zero-Count Verification)
-- ============================================================================
-- [EN Question]: How many assets are assigned to each classroom? (Include vacant classrooms with zero assets).
-- [VI Câu hỏi ]: Mỗi phòng học có bao nhiêu thiết bị? (Bao gồm cả các phòng trống chưa có thiết bị nào).
-- ----------------------------------------------------------------------------
SELECT 
    c.classroom_id,
    c.room_number,
    c.building,
    c.floor,
    COUNT(a.asset_id) AS total_assets
FROM classrooms c
LEFT JOIN assets a ON c.classroom_id = a.classroom_id
GROUP BY c.classroom_id, c.room_number, c.building, c.floor
ORDER BY total_assets ASC, c.room_number ASC;

/*
[EN Result & Explanation]:
- Result     : 10 rows returned; rooms B2-405, C1-101, C1-102, C1-203 display total_assets = 0; other rooms display 1 to 3 assets.
- Explanation: LEFT JOIN preserves all classrooms from the main table; COUNT(a.asset_id) ignores NULL foreign keys, producing exactly 0 instead of 1.

[VI Kết quả & Giải thích viva]:
- Kết quả    : Trả về 10 dòng (toàn bộ phòng học); trong đó 4 phòng (B2-405, C1-101, C1-102, C1-203) hiển thị số lượng chính xác là 0.
- Giải thích : LEFT JOIN giữ lại tất cả bản ghi của bảng chính; dùng COUNT(a.asset_id) thay vì COUNT(*) để khi gặp giá trị NULL sẽ đếm ra 0 thay vì đếm thành 1.
*/


-- ============================================================================
-- QUERY 3: GROUP BY + SUM/COUNT + HAVING
-- ============================================================================
-- [EN Question]: Which classrooms have 2 or more assigned assets, and what is their cumulative fault count?
-- [VI Câu hỏi ]: Những phòng học nào được trang bị từ 2 thiết bị trở lên và tổng số lượt báo lỗi tích lũy là bao nhiêu?
-- ----------------------------------------------------------------------------
SELECT 
    c.classroom_id,
    c.room_number,
    c.building,
    COUNT(a.asset_id) AS total_assets,
    SUM(a.fault_count) AS total_fault_reports
FROM classrooms c
INNER JOIN assets a ON c.classroom_id = a.classroom_id
GROUP BY c.classroom_id, c.room_number, c.building
HAVING COUNT(a.asset_id) >= 2
ORDER BY total_assets DESC, total_fault_reports DESC;

/*
[EN Result & Explanation]:
- Result     : 4 rows returned; high-density rooms identified: A1-201 (3 assets, 4 faults), B2-301 (2 assets, 1 fault), A1-301 (2 assets, 2 faults), A1-202 (2 assets, 0 faults).
- Explanation: GROUP BY aggregates assets per room; HAVING filters calculated groups having 2 or more assets after the grouping takes place.

[VI Kết quả & Giải thích viva]:
- Kết quả    : Trả về 4 phòng học có mật độ thiết bị cao (từ 2 thiết bị trở lên) kèm tổng số lượt báo hỏng để lên lịch bảo trì ưu tiên.
- Giải thích : GROUP BY dùng để gom nhóm tài sản theo phòng; HAVING dùng để lọc các nhóm sau khi gom nhóm (WHERE không lọc được kết quả của hàm COUNT).
*/