<?php
// dashboard.php - Quản lý phòng học (Phân hệ Vertical Slice chuẩn kiến trúc SaaS)
require_once 'db.php';

$errors = [];     // Mảng chứa các thông báo lỗi kiểm tra dữ liệu
$is_edit = false; // Cờ xác định trạng thái form: Thêm mới (false) hoặc Sửa (true)
$edit_id = 0;     // ID của phòng học đang được chọn để chỉnh sửa

$room_number = '';
$building = '';
$floor = '';
$is_active = 1;

// -----------------------------------------------------------------------------
// 1. XỬ LÝ XÓA BẢN GHI (DELETE)
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id']; // Lấy ID từ URL và ép kiểu số nguyên an toàn
    try {
        // Chuẩn bị và thực thi câu lệnh SQL xóa bằng Prepared Statement
        $stmt = $pdo->prepare("DELETE FROM classrooms WHERE classroom_id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        // Xóa thành công, chuyển hướng về trang dashboard kèm cờ thông báo
        header("Location: dashboard.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        // Bắt mã lỗi SQLSTATE 23000: Vi phạm toàn vẹn khóa ngoại (ON DELETE RESTRICT)
        // Lỗi này xảy ra khi phòng học vẫn còn thiết bị (assets) đang gắn vào
        if ($e->getCode() == '23000') {
            header("Location: dashboard.php?msg=fk_error");
        } else {
            header("Location: dashboard.php?msg=sys_error");
        }
        exit;
    }
}

// -----------------------------------------------------------------------------
// 2. LẤY DỮ LIỆU BẢN GHI ĐỂ ĐIỀN VÀO FORM SỬA (UPDATE)
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE classroom_id = :id");
    $stmt->execute(['id' => $edit_id]);
    $room = $stmt->fetch();

    if ($room) {
        $is_edit = true; // Kích hoạt cờ sửa để tự động mở Modal ở chế độ Update
        $room_number = $room['room_number'];
        $building    = $room['building'];
        $floor       = $room['floor'];
        $is_active   = (int)$room['is_active'];
    } else {
        header("Location: dashboard.php?msg=not_found");
        exit;
    }
}

// -----------------------------------------------------------------------------
// 3. XỬ LÝ GỬI FORM (THÊM MỚI VÀ CẬP NHẬT VỚI VALIDATION PHÍA MÁY CHỦ)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
    $is_edit = ($edit_id > 0);

    // Chuẩn hóa dữ liệu đầu vào: Cắt khoảng trắng thừa và viết hoa mã phòng
    $room_number = strtoupper(trim($_POST['room_number'] ?? ''));
    $building    = trim($_POST['building'] ?? '');
    $floor       = trim($_POST['floor'] ?? '');
    $is_active   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    // --- RÀNG BUỘC 1: Mã phòng (Bắt buộc, độ dài, và khớp Regex chuẩn [Tòa]-[SốPhòng]) ---
    if ($room_number === '') {
        $errors[] = "Vui lòng nhập mã phòng học.";
    } elseif (strlen($room_number) > 50) {
        $errors[] = "Mã phòng không được vượt quá 50 ký tự.";
    } elseif (!preg_match('/^[A-Z0-9]{2,8}-[0-9]{3,4}$/', $room_number)) {
        $errors[] = "Định dạng mã phòng không hợp lệ. Phải tuân theo chuẩn: [MãTòa]-[SốPhòng] (Ví dụ: A1-201, B2-105, D1-501).";
    }

    // --- RÀNG BUỘC 2: Tên tòa nhà (Bắt buộc, độ dài từ 2 đến 50 ký tự) ---
    if ($building === '') {
        $errors[] = "Vui lòng nhập tên tòa nhà.";
    } elseif (strlen($building) < 2 || strlen($building) > 50) {
        $errors[] = "Tên tòa nhà phải có độ dài từ 2 đến 50 ký tự.";
    }

    // --- RÀNG BUỘC 3: Số tầng (Bắt buộc là số nguyên dương từ tầng 1 đến 50) ---
    if ($floor === '') {
        $errors[] = "Vui lòng nhập số tầng.";
    } elseif (filter_var($floor, FILTER_VALIDATE_INT) === false || (int)$floor < 1 || (int)$floor > 50) {
        $errors[] = "Số tầng phải là số nguyên dương hợp lệ trong khoảng từ 1 đến 50.";
    }

    // --- RÀNG BUỘC 4: Trạng thái hoạt động (Chỉ cho phép nhận 0 hoặc 1) ---
    if (!in_array($is_active, [0, 1], true)) {
        $errors[] = "Trạng thái hoạt động được chọn không hợp lệ.";
    }

    // --- RÀNG BUỘC 5: Kiểm tra trùng mã nghiệp vụ (Unique Business Code) ---
    if (empty($errors)) {
        if ($is_edit) {
            // Khi sửa: Kiểm tra xem mã phòng có bị trùng với phòng KHÁC không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM classrooms WHERE room_number = :room_number AND classroom_id != :id");
            $stmt->execute(['room_number' => $room_number, 'id' => $edit_id]);
        } else {
            // Khi tạo mới: Kiểm tra xem mã phòng đã tồn tại trong CSDL chưa
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM classrooms WHERE room_number = :room_number");
            $stmt->execute(['room_number' => $room_number]);
        }

        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Mã phòng '{$room_number}' đã tồn tại trong hệ thống. Vui lòng chọn mã khác.";
        }
    }

    // --- TIẾN HÀNH LƯU VÀO CSDL KHI TẤT CẢ DỮ LIỆU ĐỀU HỢP LỆ ---
    if (empty($errors)) {
        if ($is_edit) {
            // Cập nhật bản ghi đang có
            $sql = "UPDATE classrooms 
                    SET room_number = :room_number, building = :building, floor = :floor, is_active = :is_active 
                    WHERE classroom_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'room_number' => $room_number,
                'building'    => $building,
                'floor'       => (int)$floor,
                'is_active'   => $is_active,
                'id'          => $edit_id
            ]);
            header("Location: dashboard.php?msg=updated");
            exit;
        } else {
            // Thêm mới một bản ghi phòng học
            $sql = "INSERT INTO classrooms (room_number, building, floor, is_active) 
                    VALUES (:room_number, :building, :floor, :is_active)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'room_number' => $room_number,
                'building'    => $building,
                'floor'       => (int)$floor,
                'is_active'   => $is_active
            ]);
            header("Location: dashboard.php?msg=created");
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 4. LẤY DỮ LIỆU THỐNG KÊ CHO THẺ KPI (+5 ĐIỂM THƯỞNG) VÀ BẢNG DANH SÁCH
// -----------------------------------------------------------------------------
$total_rooms    = (int)$pdo->query("SELECT COUNT(*) FROM classrooms")->fetchColumn();
$active_rooms   = (int)$pdo->query("SELECT COUNT(*) FROM classrooms WHERE is_active = 1")->fetchColumn();
$inactive_rooms = $total_rooms - $active_rooms;
$active_ratio   = ($total_rooms > 0) ? round(($active_rooms / $total_rooms) * 100, 1) : 0;

// Lấy danh sách các tòa nhà không trùng lặp để đưa vào dropdown lọc
$building_stmt = $pdo->query("SELECT DISTINCT building FROM classrooms ORDER BY building ASC");
$unique_buildings = $building_stmt->fetchAll(PDO::FETCH_COLUMN);

// Sắp xếp ID giảm dần (mới nhất lên đầu) để demo viva thấy ngay kết quả tức thì
$stmt = $pdo->query("SELECT * FROM classrooms ORDER BY classroom_id DESC");
$classrooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phòng học &bull; Campus Helpdesk</title>
    <style>
        :root {
            --bg-page: #f8fafc;
            --surface: #ffffff;
            --border-color: #e2e8f0;
            --border-light: #f1f5f9;
            
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
            --indigo-50:  #eef2ff;
            --indigo-100: #e0e7ff;
            
            --emerald-500: #10b981;
            --emerald-50:  #ecfdf5;
            --emerald-200: #a7f3d0;
            --emerald-700: #047857;
            
            --rose-500: #f43f5e;
            --rose-50:  #fff1f2;
            --rose-200: #fecdd3;
            --rose-700: #be123c;
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
            --shadow-lift: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.03);
            --shadow-modal: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-page);
            color: var(--text-body);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Inter", sans-serif;
            font-size: 13.5px;
            line-height: 1.5;
            padding: 32px 36px;
            -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 1220px; margin: 0 auto; }

        /* 1. Header có Breadcrumb và Biểu tượng lớn */
        .header-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .header-icon-box {
            width: 50px; height: 50px;
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
            color: var(--indigo-600);
            box-shadow: var(--shadow-card);
            flex-shrink: 0;
        }
        .breadcrumb-tag {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--indigo-600);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 2px;
        }
        .header-title-box h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
        }
        .header-title-box p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .db-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--surface);
            border: 1px solid var(--border-color);
            padding: 7px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-body);
            box-shadow: var(--shadow-card);
        }
        .pulse-dot {
            width: 8px; height: 8px;
            background-color: var(--emerald-500);
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
        }

        /* 2. Thẻ KPI có viền màu nhận diện */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 860px) { .kpi-row { grid-template-columns: 1fr; } }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 22px 24px;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lift);
        }
        .kpi-card::before {
            content: ''; position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
        }
        .kpi-total::before { background: var(--indigo-600); }
        .kpi-active::before { background: var(--emerald-500); }
        .kpi-inactive::before { background: var(--rose-500); }

        .kpi-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .kpi-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }
        .kpi-icon-pastel {
            width: 42px; height: 42px;
            border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center;
        }
        .kpi-total .kpi-icon-pastel { background: var(--indigo-50); color: var(--indigo-600); }
        .kpi-active .kpi-icon-pastel { background: var(--emerald-50); color: var(--emerald-500); }
        .kpi-inactive .kpi-icon-pastel { background: var(--rose-50); color: var(--rose-500); }

        .kpi-numeric {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-heading);
            margin: 12px 0 6px;
            line-height: 1;
        }
        .progress-slot {
            width: 100%; height: 6px;
            background: #e2e8f0;
            border-radius: 9999px;
            overflow: hidden;
            margin: 8px 0 6px;
        }
        .progress-active-bar {
            height: 100%;
            background: var(--emerald-500);
            border-radius: 9999px;
        }
        .kpi-footer-text {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* 3. Hộp thông báo nổi góc trên bên phải (Toast Notification) */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .toast {
            pointer-events: auto;
            min-width: 320px;
            max-width: 440px;
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.1);
            border-left: 4px solid #cbd5e1;
            font-size: 13px;
            font-weight: 500;
            animation: toastSlideIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .toast.success { border-left-color: var(--emerald-500); color: #065f46; }
        .toast.error { border-left-color: var(--rose-500); color: #9f1239; }
        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
        }
        @keyframes toastSlideIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* 4. Khung bảng dữ liệu Full-width */
        .table-panel {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }
        .table-toolbar {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .toolbar-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            flex-wrap: wrap;
        }
        .search-field {
            position: relative;
            flex: 1;
            min-width: 220px;
            max-width: 340px;
        }
        .search-field svg {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        .search-field input {
            width: 100%;
            padding: 8px 12px 8px 34px;
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: var(--bg-page);
            color: var(--text-heading);
            transition: all 0.15s ease;
        }
        .search-field input:focus {
            outline: none;
            border-color: var(--indigo-600);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .filter-select {
            padding: 8px 12px;
            font-size: 12.5px;
            font-weight: 600;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: #ffffff;
            color: var(--text-body);
            cursor: pointer;
            transition: border-color 0.15s ease;
        }
        .filter-select:focus { outline: none; border-color: var(--indigo-600); }

        .btn-add-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--indigo-600);
            color: #ffffff;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(79, 70, 229, 0.3);
            transition: all 0.15s ease;
        }
        .btn-add-primary:hover {
            background: var(--indigo-700);
            transform: translateY(-1px);
        }

        /* 5. Giao diện bảng và hiệu ứng hover dòng */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 13px 24px;
            border-bottom: 1px solid var(--border-color);
        }
        td {
            padding: 15px 24px;
            border-bottom: 1px solid var(--border-light);
            font-size: 13px;
            color: var(--text-body);
            vertical-align: middle;
            transition: background-color 0.15s ease;
        }
        tbody tr:hover td { background-color: #f8faff; }

        /* Highlight dòng đang được chỉnh sửa */
        .row-editing td {
            background-color: var(--indigo-50) !important;
            border-bottom-color: var(--indigo-100);
        }
        .row-editing td:first-child {
            box-shadow: inset 4px 0 0 var(--indigo-600);
        }

        .col-id { color: #94a3b8; font-weight: 600; font-size: 12px; }
        .col-room { font-size: 14px; font-weight: 800; color: var(--text-heading); }
        .editing-flag {
            font-size: 10px;
            background: var(--indigo-600);
            color: #ffffff;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 6px;
        }

        .bld-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }
        .floor-label {
            font-size: 12.5px;
            color: var(--text-body);
            white-space: nowrap;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pill.active {
            background: var(--emerald-50);
            color: var(--emerald-700);
            border: 1px solid var(--emerald-200);
        }
        .status-pill.active .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--emerald-500);
        }
        .status-pill.inactive {
            background: var(--rose-50);
            color: var(--rose-700);
            border: 1px solid var(--rose-200);
        }
        .status-pill.inactive .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--rose-500);
        }

        /* Nút hành động thao tác dòng */
        .actions-cell { text-align: right; white-space: nowrap; }
        .btn-act {
            padding: 6px 11px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }
        .btn-act-edit { color: var(--indigo-600); background: var(--indigo-50); }
        .btn-act-edit:hover { background: var(--indigo-100); }
        .btn-act-delete { color: var(--rose-500); background: var(--rose-50); margin-left: 6px; }
        .btn-act-delete:hover { background: var(--rose-200); }

        /* Phân trang và chân bảng */
        .table-footer {
            padding: 14px 24px;
            border-top: 1px solid var(--border-color);
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 12px;
        }
        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-page {
            background: var(--surface);
            border: 1px solid var(--border-color);
            color: var(--text-body);
            padding: 5px 12px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-page:hover:not(:disabled) {
            background: var(--bg-page);
            border-color: var(--indigo-600);
            color: var(--indigo-600);
        }
        .btn-page:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Modal Dialog */
        .modal-backdrop {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-backdrop.show { display: flex; }

        .modal-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow-modal);
            animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalPop {
            from { opacity: 0; transform: translateY(10px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-card-header h2 { font-size: 16px; font-weight: 800; color: var(--text-heading); }
        .btn-close-modal {
            background: none; border: none; font-size: 20px; color: var(--text-muted);
            cursor: pointer; line-height: 1;
        }
        .btn-close-modal:hover { color: var(--text-heading); }

        .modal-card-body { padding: 22px 24px; }
        .form-item { margin-bottom: 16px; }
        .form-item label {
            display: block; font-size: 12.5px; font-weight: 700; color: var(--text-body);
            margin-bottom: 6px;
        }
        .form-item input, .form-item select {
            width: 100%; padding: 10px 12px;
            border: 1.5px solid var(--border-color); border-radius: var(--radius-sm);
            font-size: 13px; color: var(--text-heading); background: #ffffff;
            transition: all 0.15s ease;
        }
        .form-item input:focus, .form-item select:focus {
            outline: none; border-color: var(--indigo-600);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }
        .input-hint {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .modal-card-footer {
            display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;
        }
        .btn-modal-cancel {
            background: #f1f5f9; color: var(--text-body); border: 1px solid var(--border-color);
            padding: 9px 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600;
            text-decoration: none; cursor: pointer;
        }
        .btn-modal-cancel:hover { background: #e2e8f0; }
        .btn-modal-submit {
            background: var(--indigo-600); color: #ffffff; border: none;
            padding: 9px 20px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 700;
            cursor: pointer; box-shadow: 0 1px 2px rgba(79, 70, 229, 0.3);
        }
        .btn-modal-submit:hover { background: var(--indigo-700); }
    </style>
</head>
<body>

<!-- Khung chứa thông báo Toast nổi -->
<div class="toast-container" id="toastContainer">
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'created'): ?>
            <div class="toast success" id="mainToast">
                <span>Thêm mới phòng học thành công.</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="toast success" id="mainToast">
                <span>Cập nhật thông tin phòng học thành công.</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="toast success" id="mainToast">
                <span>Đã xóa bản ghi phòng học thành công.</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php elseif ($_GET['msg'] === 'fk_error'): ?>
            <div class="toast error" id="mainToast">
                <span><strong>Lỗi toàn vẹn dữ liệu:</strong> Không thể xóa phòng học này vì đang có thiết bị liên kết (<code>ON DELETE RESTRICT</code>).</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php elseif ($_GET['msg'] === 'not_found'): ?>
            <div class="toast error" id="mainToast">
                <span>Không tìm thấy bản ghi phòng học yêu cầu.</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="container">
    <!-- 1. Header có Breadcrumb và Trạng thái CSDL -->
    <header class="header-panel">
        <div class="header-left">
            <div class="header-icon-box">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l8-4v18"></path>
                    <path d="M19 21V11l-6-4"></path>
                    <path d="M9 9v.01"></path>
                    <path d="M9 13v.01"></path>
                    <path d="M9 17v.01"></path>
                </svg>
            </div>
            <div class="header-title-box">
                <div class="breadcrumb-tag">Hệ thống quản lý / Phòng học</div>
                <h1>Quản lý phòng học</h1>
                <p>Theo dõi danh mục cơ sở vật chất và trạng thái vận hành của phòng học.</p>
            </div>
        </div>
        <div class="db-status-pill">
            <div class="pulse-dot"></div>
            <span>Đã kết nối CSDL (PDO)</span>
        </div>
    </header>

    <!-- 2. Thẻ chỉ số KPI -->
    <div class="kpi-row">
        <div class="kpi-card kpi-total">
            <div class="kpi-card-header">
                <span class="kpi-label">Tổng số phòng học</span>
                <div class="kpi-icon-pastel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                        <line x1="9" y1="22" x2="9" y2="18"></line>
                        <line x1="15" y1="22" x2="15" y2="18"></line>
                        <line x1="8" y1="6" x2="16" y2="6"></line>
                        <line x1="8" y1="10" x2="16" y2="10"></line>
                    </svg>
                </div>
            </div>
            <div class="kpi-numeric"><?= $total_rooms ?></div>
            <div class="kpi-footer-text">Phòng học đã lập chỉ mục trong hệ thống</div>
        </div>

        <div class="kpi-card active">
            <div class="kpi-card-header">
                <span class="kpi-label">Đang hoạt động</span>
                <div class="kpi-icon-pastel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
            </div>
            <div class="kpi-numeric"><?= $active_rooms ?></div>
            <div class="progress-slot">
                <div class="progress-active-bar" style="width: <?= $active_ratio ?>%;"></div>
            </div>
            <div class="kpi-footer-text"><?= $active_rooms ?>/<?= $total_rooms ?> phòng sẵn sàng phục vụ (<?= $active_ratio ?>%)</div>
        </div>

        <div class="kpi-card inactive">
            <div class="kpi-card-header">
                <span class="kpi-label">Bảo trì / Tạm dừng</span>
                <div class="kpi-icon-pastel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
            </div>
            <div class="kpi-numeric"><?= $inactive_rooms ?></div>
            <div class="kpi-footer-text">Đang bảo dưỡng hoặc sửa chữa thiết bị</div>
        </div>
    </div>

    <!-- 3. Bảng dữ liệu chính kèm thanh công cụ lọc -->
    <div class="table-panel">
        <div class="table-toolbar">
            <div class="toolbar-filters">
                <div class="search-field">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="liveSearchInput" placeholder="Tìm kiếm mã phòng, tòa nhà, tầng...">
                </div>

                <select id="filterBuilding" class="filter-select">
                    <option value="all">Tất cả tòa nhà</option>
                    <?php foreach ($unique_buildings as $bld): ?>
                        <option value="<?= htmlspecialchars($bld) ?>"><?= htmlspecialchars($bld) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filterStatus" class="filter-select">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active">Đang hoạt động</option>
                    <option value="inactive">Tạm dừng</option>
                </select>
            </div>

            <button type="button" class="btn-add-primary" id="openAddModalBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Thêm phòng học
            </button>
        </div>

        <div class="table-responsive">
            <table id="classroomTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Mã phòng học</th>
                        <th>Tòa nhà</th>
                        <th style="width: 120px;">Vị trí tầng</th>
                        <th style="width: 130px;">Trạng thái</th>
                        <th style="width: 140px; text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classrooms)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 36px;">Không tìm thấy bản ghi phòng học nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($classrooms as $row): ?>
                            <tr class="data-row <?= ($is_edit && $edit_id === (int)$row['classroom_id']) ? 'row-editing' : '' ?>"
                                data-building="<?= htmlspecialchars($row['building']) ?>"
                                data-status="<?= $row['is_active'] == 1 ? 'active' : 'inactive' ?>">
                                <td class="col-id">#<?= htmlspecialchars($row['classroom_id']) ?></td>
                                <td>
                                    <span class="col-room"><?= htmlspecialchars($row['room_number']) ?></span>
                                    <?php if ($is_edit && $edit_id === (int)$row['classroom_id']): ?>
                                        <span class="editing-flag">ĐANG SỬA</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="bld-badge"><?= htmlspecialchars($row['building']) ?></span>
                                </td>
                                <td>
                                    <span class="floor-label">Tầng <?= htmlspecialchars($row['floor']) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['is_active'] == 1): ?>
                                        <span class="status-pill active"><span class="dot"></span> Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status-pill inactive"><span class="dot"></span> Tạm dừng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <a class="btn-act btn-act-edit" 
                                       href="dashboard.php?action=edit&id=<?= $row['classroom_id'] ?>" 
                                       title="Chỉnh sửa thông tin phòng">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Sửa
                                    </a>
                                    <button type="button" 
                                            class="btn-act btn-act-delete" 
                                            data-id="<?= $row['classroom_id'] ?>" 
                                            data-room="<?= htmlspecialchars($row['room_number']) ?>"
                                            title="Xóa phòng học"
                                            onclick="openDeleteDialog(this)">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path></svg>
                                        Xóa
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Chân bảng điều khiển phân trang -->
        <div class="table-footer">
            <div>
                Đang hiển thị <strong id="pageRangeText">1–8</strong> trên tổng số <strong id="visibleCount"><?= count($classrooms) ?></strong> phòng học
            </div>
            <div class="pagination-controls">
                <button type="button" class="btn-page" id="prevPageBtn" disabled>Trang trước</button>
                <span style="font-weight: 600; padding: 0 4px;" id="pageIndicator">Trang 1 / 1</span>
                <button type="button" class="btn-page" id="nextPageBtn">Trang sau</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Form Thêm mới & Chỉnh sửa phòng học -->
<div class="modal-backdrop <?= ($is_edit || !empty($errors)) ? 'show' : '' ?>" id="crudModal">
    <div class="modal-card">
        <div class="modal-card-header">
            <h2><?= $is_edit ? 'Chỉnh sửa phòng học #' . $edit_id : 'Đăng ký phòng học mới' ?></h2>
            <button type="button" class="btn-close-modal" id="closeModalCross">&times;</button>
        </div>
        <div class="modal-card-body">
            <!-- Hiển thị thông báo lỗi kiểm tra từ máy chủ (nếu có) -->
            <?php if (!empty($errors)): ?>
                <div style="background: var(--rose-50); border: 1px solid var(--rose-200); color: var(--rose-700); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px;">
                    <strong>Vui lòng khắc phục các lỗi sau:</strong>
                    <ul style="margin-left: 18px; margin-top: 4px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="dashboard.php" method="POST">
                <input type="hidden" name="edit_id" value="<?= $is_edit ? $edit_id : 0 ?>">

                <div class="form-item">
                    <label for="room_number">Mã phòng học (Mã duy nhất):</label>
                    <input type="text" id="room_number" name="room_number" 
                           value="<?= htmlspecialchars($room_number) ?>" 
                           placeholder="Ví dụ: A1-201, B2-105, D1-501" 
                           pattern="^[A-Za-z0-9]{2,8}-[0-9]{3,4}$"
                           title="Định dạng chuẩn: [MãTòa]-[SốPhòng] (Ví dụ: A1-201, B2-105)"
                           required>
                    <div class="input-hint">Định dạng chuẩn: <code>[MãTòa]-[SốPhòng]</code> (Ví dụ: A1-201, B2-105)</div>
                </div>

                <div class="form-item">
                    <label for="building">Tòa nhà:</label>
                    <input type="text" id="building" name="building" 
                           value="<?= htmlspecialchars($building) ?>" 
                           placeholder="Ví dụ: Building A1, Building B2" 
                           minlength="2" maxlength="50" required>
                </div>

                <div class="form-item">
                    <label for="floor">Vị trí tầng:</label>
                    <input type="number" id="floor" name="floor" min="1" max="50"
                           value="<?= htmlspecialchars($floor) ?>" 
                           placeholder="Ví dụ: 1, 2, 3" required>
                    <div class="input-hint">Số nguyên dương từ tầng 1 đến tầng 50</div>
                </div>

                <div class="form-item">
                    <label for="is_active">Trạng thái vận hành:</label>
                    <select id="is_active" name="is_active">
                        <option value="1" <?= $is_active === 1 ? 'selected' : '' ?>>Đang hoạt động</option>
                        <option value="0" <?= $is_active === 0 ? 'selected' : '' ?>>Bảo trì / Tạm dừng</option>
                    </select>
                </div>

                <div class="modal-card-footer">
                    <a href="dashboard.php" class="btn-modal-cancel" id="cancelModalBtn">Hủy bỏ</a>
                    <button type="submit" class="btn-modal-submit">
                        <?= $is_edit ? 'Cập nhật phòng' : 'Lưu phòng học' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Hộp thoại xác nhận trước khi xóa (Delete Confirmation Dialog) -->
<div class="modal-backdrop" id="deleteModal">
    <div class="modal-card" style="max-width: 430px;">
        <div class="modal-card-header" style="border-bottom: none; padding-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--rose-50); color: var(--rose-500); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size: 16px; font-weight: 800; color: var(--text-heading);">Xác nhận xóa phòng?</h2>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 1px;">Thao tác này cần sự xác nhận của bạn.</p>
                </div>
            </div>
        </div>
        <div class="modal-card-body" style="padding-top: 14px;">
            <p style="font-size: 13.5px; color: var(--text-body); line-height: 1.5;">
                Bạn có chắc chắn muốn xóa phòng học <strong id="deleteTargetRoom" style="color: var(--text-heading);"></strong>? Thao tác này sẽ không thể khôi phục lại.
            </p>
            <div class="modal-card-footer" style="margin-top: 22px;">
                <button type="button" class="btn-modal-cancel" id="cancelDeleteBtn">Hủy</button>
                <a href="#" id="confirmDeleteLink" class="btn-modal-submit" style="background: var(--rose-500); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                    Xác nhận xóa
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Tự động đóng thông báo Toast sau 4.5 giây
    function dismissToast(btn) {
        const toast = btn.closest('.toast');
        if (toast) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(30px)';
            setTimeout(() => toast.remove(), 300);
        }
    }
    const mainToast = document.getElementById('mainToast');
    if (mainToast) {
        setTimeout(() => {
            mainToast.style.opacity = '0';
            mainToast.style.transform = 'translateX(30px)';
            setTimeout(() => mainToast.remove(), 300);
        }, 4500);
    }

    // 2. Điều khiển Modal Thêm mới và Chỉnh sửa
    const crudModal = document.getElementById('crudModal');
    const openAddBtn = document.getElementById('openAddModalBtn');
    const closeCross = document.getElementById('closeModalCross');
    const cancelBtn = document.getElementById('cancelModalBtn');

    if (openAddBtn) {
        openAddBtn.addEventListener('click', () => {
            document.querySelector('#crudModal form').reset();
            document.querySelector('#crudModal input[name="edit_id"]').value = '0';
            document.querySelector('.modal-card-header h2').textContent = 'Đăng ký phòng học mới';
            crudModal.classList.add('show');
        });
    }

    function closeCrudModal() {
        if (window.location.search.includes('action=edit')) {
            window.location.href = 'dashboard.php';
        } else {
            crudModal.classList.remove('show');
        }
    }

    if (closeCross) closeCross.addEventListener('click', closeCrudModal);
    if (cancelBtn) {
        cancelBtn.addEventListener('click', (e) => {
            if (!window.location.search.includes('action=edit')) {
                e.preventDefault();
                closeCrudModal();
            }
        });
    }

    // 3. Điều khiển Hộp thoại xác nhận xóa riêng biệt
    const deleteModal = document.getElementById('deleteModal');
    const deleteTargetRoom = document.getElementById('deleteTargetRoom');
    const confirmDeleteLink = document.getElementById('confirmDeleteLink');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    function openDeleteDialog(btn) {
        const id = btn.getAttribute('data-id');
        const room = btn.getAttribute('data-room');
        deleteTargetRoom.textContent = room;
        // Gắn ID vào đường dẫn xóa gửi về backend
        confirmDeleteLink.href = 'dashboard.php?action=delete&id=' + id;
        deleteModal.classList.add('show');
    }

    function closeDeleteDialog() {
        deleteModal.classList.remove('show');
    }

    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteDialog);

    // Xử lý sự kiện đóng Modal khi click ra ngoài nền tối hoặc bấm phím Escape
    window.addEventListener('click', (e) => {
        if (e.target === crudModal) closeCrudModal();
        if (e.target === deleteModal) closeDeleteDialog();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (crudModal.classList.contains('show')) closeCrudModal();
            if (deleteModal.classList.contains('show')) closeDeleteDialog();
        }
    });

    // 4. Hệ thống Lọc trực tiếp và Phân trang phía máy khách (Client-side)
    const searchInput = document.getElementById('liveSearchInput');
    const filterBuilding = document.getElementById('filterBuilding');
    const filterStatus = document.getElementById('filterStatus');
    const allRows = Array.from(document.querySelectorAll('#classroomTable tbody tr.data-row'));
    
    const pageRangeText = document.getElementById('pageRangeText');
    const visibleCounter = document.getElementById('visibleCount');
    const pageIndicator = document.getElementById('pageIndicator');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');

    const ITEMS_PER_PAGE = 8; // Mặc định hiển thị 8 phòng mỗi trang
    let currentPage = 1;
    let filteredRows = [...allRows];

    function renderPagination() {
        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
        
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        allRows.forEach(row => row.style.display = 'none');

        const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
        const endIndex = Math.min(startIndex + ITEMS_PER_PAGE, total);

        for (let i = startIndex; i < endIndex; i++) {
            if (filteredRows[i]) filteredRows[i].style.display = '';
        }

        if (visibleCounter) visibleCounter.textContent = total;
        if (pageRangeText) {
            pageRangeText.textContent = total === 0 ? '0–0' : `${startIndex + 1}–${endIndex}`;
        }
        if (pageIndicator) {
            pageIndicator.textContent = `Trang ${currentPage} / ${totalPages}`;
        }

        if (prevPageBtn) prevPageBtn.disabled = (currentPage === 1);
        if (nextPageBtn) nextPageBtn.disabled = (currentPage === totalPages || total === 0);
    }

    function applyFilterAndPaginate() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const buildingVal = filterBuilding ? filterBuilding.value.toLowerCase() : 'all';
        const statusVal = filterStatus ? filterStatus.value.toLowerCase() : 'all';

        filteredRows = allRows.filter(row => {
            const rowText = row.innerText.toLowerCase();
            const rowBuilding = (row.getAttribute('data-building') || '').toLowerCase();
            const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();

            const matchQuery = (query === '') || rowText.includes(query);
            const matchBuilding = (buildingVal === 'all') || (rowBuilding === buildingVal);
            const matchStatus = (statusVal === 'all') || (rowStatus === statusVal);

            return matchQuery && matchBuilding && matchStatus;
        });

        currentPage = 1; // Đưa về trang 1 mỗi khi thay đổi bộ lọc
        renderPagination();
    }

    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPagination();
            }
        });
    }

    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredRows.length / ITEMS_PER_PAGE);
            if (currentPage < totalPages) {
                currentPage++;
                renderPagination();
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilterAndPaginate);
    if (filterBuilding) filterBuilding.addEventListener('change', applyFilterAndPaginate);
    if (filterStatus) filterStatus.addEventListener('change', applyFilterAndPaginate);

    // Kích hoạt phân trang lần đầu khi tải trang
    applyFilterAndPaginate();
</script>

</body>
</html>