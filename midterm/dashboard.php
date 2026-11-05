<?php
// ==============================================================================
// Tên file: dashboard.php
// Đề tài: Campus Helpdesk - Phân hệ Quản lý phòng học (Classrooms Vertical Slice)
// Đặc tả kỹ thuật: Đầy đủ CRUD, Server-side Validation, Chống XSS, Bắt lỗi khóa ngoại PDO
// ==============================================================================

// 1. Nhúng file kết nối CSDL (sử dụng đối tượng $pdo cấu hình chuẩn bảo mật)
require_once 'db.php';

// Khởi tạo các biến lưu trữ lỗi và dữ liệu nhập lại trên form
$errors = [];
$form_data = [
    'classroom_id' => '',
    'room_number'  => '',
    'building'     => '',
    'floor'        => '',
    'is_active'    => '1'
];

// ------------------------------------------------------------------------------
// 2. XỬ LÝ THAO TÁC XÓA (DELETE) - Ràng buộc khóa ngoại ON DELETE RESTRICT
// ------------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Ép kiểu an toàn sang số nguyên để ngăn chặn thao tác qua URL
    $delete_id = (int)$_GET['id'];

    try {
        // Dùng Prepared Statement để xóa an toàn, không nối chuỗi SQL
        $stmt = $pdo->prepare("DELETE FROM classrooms WHERE classroom_id = :id");
        $stmt->execute(['id' => $delete_id]);

        // Áp dụng mô hình Post/Redirect/Get để tránh gửi lặp yêu cầu
        header("Location: dashboard.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        // SQLSTATE 23000: Bắt lỗi vi phạm toàn vẹn dữ liệu khi phòng đang chứa thiết bị
        if ($e->getCode() === '23000') {
            header("Location: dashboard.php?msg=fk_error");
            exit;
        } else {
            // Lỗi hệ thống khác (không hiển thị chi tiết lỗi kỹ thuật thô ra màn hình)
            header("Location: dashboard.php?msg=error");
            exit;
        }
    }
}

// ------------------------------------------------------------------------------
// 3. XỬ LÝ THÊM MỚI (CREATE) HOẶC CẬP NHẬT (UPDATE) QUA PHƯƠNG THỨC POST
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu và cắt bỏ khoảng trắng dư thừa
    $classroom_id = isset($_POST['classroom_id']) ? (int)$_POST['classroom_id'] : 0;
    $room_number  = trim($_POST['room_number'] ?? '');
    $building     = trim($_POST['building'] ?? '');
    $floor        = trim($_POST['floor'] ?? '');
    $is_active    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

    // Giữ lại dữ liệu người dùng đã gõ để fill lại form nếu có lỗi
    $form_data = [
        'classroom_id' => $classroom_id,
        'room_number'  => $room_number,
        'building'     => $building,
        'floor'        => $floor,
        'is_active'    => $is_active
    ];

    // --- KIỂM TRA DỮ LIỆU PHÍA MÁY CHỦ (SERVER-SIDE VALIDATION) ---
    // Kiểm tra rỗng và định dạng mã phòng theo Regex [Tòa nhà]-[Số phòng]
    if (empty($room_number)) {
        $errors[] = "Room number is required.";
    } elseif (!preg_match('/^[A-Z0-9]+-[0-9]+$/i', $room_number)) {
        $errors[] = "Room number format must follow [Building]-[Room] (e.g., B2-105, C1-203).";
    }

    // Kiểm tra chọn tòa nhà
    if (empty($building)) {
        $errors[] = "Building selection is required.";
    }

    // Kiểm tra tầng phải là số nguyên dương
    if ($floor === '' || !is_numeric($floor) || (int)$floor < 0) {
        $errors[] = "Floor must be a valid non-negative integer.";
    }

    // Kiểm tra trùng lặp mã phòng duy nhất (Ràng buộc UNIQUE nghiệp vụ)
    if (empty($errors)) {
        if ($classroom_id > 0) {
            // Trường hợp Sửa (UPDATE): kiểm tra trùng mã với các phòng khác ngoại trừ chính nó
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM classrooms WHERE room_number = :room_number AND classroom_id != :id");
            $stmt->execute(['room_number' => $room_number, 'id' => $classroom_id]);
        } else {
            // Trường hợp Thêm mới (INSERT): kiểm tra trùng trong toàn bộ bảng
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM classrooms WHERE room_number = :room_number");
            $stmt->execute(['room_number' => $room_number]);
        }

        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Room number '{$room_number}' already exists in the system.";
        }
    }

    // Nếu không có bất kỳ lỗi nào, tiến hành chèn hoặc cập nhật dữ liệu an toàn
    if (empty($errors)) {
        try {
            if ($classroom_id > 0) {
                // Thao tác UPDATE
                $stmt = $pdo->prepare("
                    UPDATE classrooms 
                    SET room_number = :room_number, 
                        building = :building, 
                        floor = :floor, 
                        is_active = :is_active 
                    WHERE classroom_id = :id
                ");
                $stmt->execute([
                    'room_number' => $room_number,
                    'building'    => $building,
                    'floor'       => (int)$floor,
                    'is_active'   => $is_active,
                    'id'          => $classroom_id
                ]);
                header("Location: dashboard.php?msg=updated");
                exit;
            } else {
                // Thao tác CREATE (INSERT)
                $stmt = $pdo->prepare("
                    INSERT INTO classrooms (room_number, building, floor, is_active) 
                    VALUES (:room_number, :building, :floor, :is_active)
                ");
                $stmt->execute([
                    'room_number' => $room_number,
                    'building'    => $building,
                    'floor'       => (int)$floor,
                    'is_active'   => $is_active
                ]);
                header("Location: dashboard.php?msg=created");
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Database operation failed: " . $e->getMessage();
        }
    }
}

// ------------------------------------------------------------------------------
// 4. TRUY VẤN THỐNG KÊ (KPI METRICS) VÀ LẤY DANH SÁCH HIỂN THỊ
// ------------------------------------------------------------------------------
// KPI 1: Tổng số phòng học
$total_stmt = $pdo->query("SELECT COUNT(*) FROM classrooms");
$total_classrooms = (int)$total_stmt->fetchColumn();

// KPI 2: Số phòng học đang hoạt động
$active_stmt = $pdo->query("SELECT COUNT(*) FROM classrooms WHERE is_active = 1");
$active_classrooms = (int)$active_stmt->fetchColumn();

// KPI 3: Số phòng học bảo trì / tạm dừng
$inactive_classrooms = $total_classrooms - $active_classrooms;
$active_percent = $total_classrooms > 0 ? round(($active_classrooms / $total_classrooms) * 100, 1) : 0;

// Lấy toàn bộ danh sách phòng học để render bảng (sắp xếp ID mới nhất lên đầu)
$stmt = $pdo->query("SELECT * FROM classrooms ORDER BY classroom_id DESC");
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách các tòa nhà không trùng nhau để đổ vào bộ lọc Dropdown
$b_stmt = $pdo->query("SELECT DISTINCT building FROM classrooms WHERE building IS NOT NULL AND building != '' ORDER BY building ASC");
$buildings_list = $b_stmt->fetchAll(PDO::FETCH_COLUMN);

// Xử lý thông điệp Toast Notification (hiển thị tiếng Anh chuẩn)
$toast_msg = '';
$toast_type = 'info';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'created':
            $toast_msg = "Classroom has been created successfully.";
            $toast_type = "success";
            break;
        case 'updated':
            $toast_msg = "Classroom details updated successfully.";
            $toast_type = "success";
            break;
        case 'deleted':
            $toast_msg = "Classroom deleted successfully.";
            $toast_type = "success";
            break;
        case 'fk_error':
            $toast_msg = "Action prohibited: This classroom contains assigned assets (ON DELETE RESTRICT violation).";
            $toast_type = "error";
            break;
        case 'error':
            $toast_msg = "An unexpected database error occurred.";
            $toast_type = "error";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Helpdesk - Classroom Management</title>
    <!-- Nhúng Tailwind CSS CDN để hỗ trợ dựng giao diện chuẩn SaaS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

    <!-- KHỐI THÔNG BÁO TOAST NOTIFICATION -->
    <?php if (!empty($toast_msg)): ?>
    <div id="toast-banner" class="fixed top-5 right-5 z-50 flex items-center p-4 rounded-xl shadow-lg border transition-all duration-300 <?= $toast_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
        <i class="fa-solid <?= $toast_type === 'success' ? 'fa-circle-check text-emerald-500' : 'fa-circle-exclamation text-rose-500' ?> text-xl mr-3"></i>
        <span class="font-medium text-sm"><?= htmlspecialchars($toast_msg) ?></span>
        <button onclick="document.getElementById('toast-banner').remove()" class="ml-4 text-slate-400 hover:text-slate-600">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- HEADER VÀ BREADCRUMB -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <nav class="flex text-xs font-semibold tracking-wider text-indigo-600 uppercase mb-1">
                    <span>CAMPUS HELPDESK</span>
                    <span class="mx-2 text-slate-300">/</span>
                    <span class="text-slate-500">CLASSROOMS</span>
                </nav>
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-50 border border-indigo-100 rounded-xl text-indigo-600">
                        <i class="fa-solid fa-building-columns text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Classroom Management</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Monitor campus facilities and operational status.</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 mr-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Database Connected (PDO)
                </span>
            </div>
        </div>

        <!-- HIỂN THỊ LỖI VALIDATION NẾU CÓ -->
        <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl">
            <div class="flex items-start">
                <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5 mr-3"></i>
                <div>
                    <h3 class="text-sm font-semibold text-rose-800">Submission Error (Please review the fields below):</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-rose-700 space-y-0.5">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3 THẺ KPI METRICS (KÈM THANH PROGRESS BAR) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <!-- Thẻ 1: Tổng số phòng -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">TOTAL CLASSROOMS</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-2"><?= $total_classrooms ?></h3>
                        <p class="text-xs text-slate-500 mt-2">Total registered physical rooms</p>
                    </div>
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                        <i class="fa-regular fa-building text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Thẻ 2: Phòng đang hoạt động -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ACTIVE</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-2"><?= $active_classrooms ?></h3>
                    </div>
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                        <i class="fa-solid fa-circle-check text-lg"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= $active_percent ?>%"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2"><?= $active_classrooms ?> of <?= $total_classrooms ?> rooms ready for use (<?= $active_percent ?>%)</p>
                </div>
            </div>

            <!-- Thẻ 3: Phòng đang bảo trì -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">MAINTENANCE / INACTIVE</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-2"><?= $inactive_classrooms ?></h3>
                        <p class="text-xs text-slate-500 mt-2">Undergoing repairs or inactive</p>
                    </div>
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                        <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- BẢNG ĐIỀU KHIỂN: TÌM KIẾM, LỌC VÀ NÚT THÊM MỚI -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm mb-6 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
                <!-- Live Search Box -->
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                    <input type="text" id="liveSearchInput" placeholder="Search room, building, floor..." 
                           class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                </div>

                <!-- Dropdown lọc Tòa nhà -->
                <select id="buildingFilter" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Buildings</option>
                    <?php foreach ($buildings_list as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Dropdown lọc Trạng thái -->
                <select id="statusFilter" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <!-- Nút mở Modal Thêm mới -->
            <button onclick="openCreateModal()" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl shadow-sm transition-all whitespace-nowrap">
                <i class="fa-solid fa-plus mr-2 text-xs"></i>
                Add Classroom
            </button>
        </div>

        <!-- BẢNG DỮ LIỆU CLASSROOMS -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="classroomsTable">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">ROOM NUMBER</th>
                            <th class="py-3.5 px-6">BUILDING</th>
                            <th class="py-3.5 px-6">FLOOR</th>
                            <th class="py-3.5 px-6">STATUS</th>
                            <th class="py-3.5 px-6 text-center">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($classrooms)): ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">No classroom records found in the database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($classrooms as $row): ?>
                            <tr class="hover:bg-slate-50/60 transition-colors data-row">
                                <td class="py-4 px-6 text-slate-400 font-mono text-xs">#<?= htmlspecialchars($row['classroom_id']) ?></td>
                                <td class="py-4 px-6 font-bold text-slate-900 col-room"><?= htmlspecialchars($row['room_number']) ?></td>
                                <td class="py-4 px-6 text-slate-600 col-building"><?= htmlspecialchars($row['building']) ?></td>
                                <td class="py-4 px-6 text-slate-600 col-floor">Floor <?= htmlspecialchars($row['floor']) ?></td>
                                <td class="py-4 px-6 col-status">
                                    <?php if ($row['is_active']): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-rose-500"></span> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <!-- Nút Sửa: Đổ dữ liệu an toàn vào modal -->
                                        <button onclick='openEditModal(<?= json_encode($row) ?>)' 
                                                class="px-2.5 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                                            <i class="fa-regular fa-pen-to-square mr-1"></i> Edit
                                        </button>

                                        <!-- Nút Xóa: Kích hoạt modal xác nhận -->
                                        <button onclick="confirmDelete(<?= (int)$row['classroom_id'] ?>, '<?= htmlspecialchars($row['room_number'], ENT_QUOTES) ?>')" 
                                                class="px-2.5 py-1.5 text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors">
                                            <i class="fa-regular fa-trash-can mr-1"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================================== -->
    <!-- MODAL CRUD: THÊM MỚI VÀ CHỈNH SỬA PHÒNG HỌC -->
    <!-- ========================================================================== -->
    <div id="crudModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 id="modalTitle" class="text-base font-bold text-slate-800">Add New Classroom</h3>
                <button onclick="closeCrudModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form action="dashboard.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="classroom_id" id="modal_classroom_id" value="">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Room Number *</label>
                    <input type="text" name="room_number" id="modal_room_number" required placeholder="e.g., B2-105, C1-203"
                           class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-[11px] text-slate-400 mt-1">Must follow [Building]-[Room] format.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Building *</label>
                    <input type="text" name="building" id="modal_building" required placeholder="e.g., Building B2"
                           class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Floor *</label>
                    <input type="number" name="floor" id="modal_floor" required min="0" placeholder="e.g., 1"
                           class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Operational Status</label>
                    <select name="is_active" id="modal_is_active" class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="1">Active (Ready for scheduling)</option>
                        <option value="0">Inactive (Under maintenance)</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeCrudModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" id="modalSubmitBtn" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition-colors">Save Classroom</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================== -->
    <!-- MODAL XÁC NHẬN XÓA (CONFIRM DELETE MODAL) -->
    <!-- ========================================================================== -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 text-center shadow-2xl border border-slate-100">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900">Confirm Deletion</h3>
            <p class="text-xs text-slate-500 mt-2">
                Are you sure you want to delete classroom <span id="delRoomName" class="font-bold text-slate-800"></span>?
                <br><span class="text-rose-500">Note: Deletion will be rejected if assets are still assigned.</span>
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <button onclick="closeDeleteModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                <a id="delConfirmLink" href="#" class="px-5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition-colors">Confirm Delete</a>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT ĐIỀU KHIỂN MODAL VÀ LIVE SEARCH -->
    <script>
        // Mở Modal Thêm mới
        function openCreateModal() {
            document.getElementById('modalTitle').innerText = "Add New Classroom";
            document.getElementById('modalSubmitBtn').innerText = "Create Classroom";
            document.getElementById('modal_classroom_id').value = "";
            document.getElementById('modal_room_number').value = "";
            document.getElementById('modal_building').value = "";
            document.getElementById('modal_floor').value = "1";
            document.getElementById('modal_is_active').value = "1";
            document.getElementById('crudModal').classList.remove('hidden');
        }

        // Mở Modal Chỉnh sửa với dữ liệu dòng tương ứng
        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = "Edit Classroom";
            document.getElementById('modalSubmitBtn').innerText = "Update Changes";
            document.getElementById('modal_classroom_id').value = data.classroom_id;
            document.getElementById('modal_room_number').value = data.room_number;
            document.getElementById('modal_building').value = data.building;
            document.getElementById('modal_floor').value = data.floor;
            document.getElementById('modal_is_active').value = data.is_active;
            document.getElementById('crudModal').classList.remove('hidden');
        }

        function closeCrudModal() {
            document.getElementById('crudModal').classList.add('hidden');
        }

        // Mở Modal Xác nhận Xóa
        function confirmDelete(id, roomNumber) {
            document.getElementById('delRoomName').innerText = roomNumber;
            document.getElementById('delConfirmLink').href = "dashboard.php?action=delete&id=" + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Live Search và Filter dữ liệu trực tiếp phía Client
        const searchInput = document.getElementById('liveSearchInput');
        const buildingFilter = document.getElementById('buildingFilter');
        const statusFilter = document.getElementById('statusFilter');

        function filterTable() {
            const query = searchInput.value.toLowerCase().trim();
            const bVal = buildingFilter.value.toLowerCase().trim();
            const sVal = statusFilter.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.data-row');

            rows.forEach(row => {
                const room = row.querySelector('.col-room').innerText.toLowerCase();
                const building = row.querySelector('.col-building').innerText.toLowerCase();
                const floor = row.querySelector('.col-floor').innerText.toLowerCase();
                const status = row.querySelector('.col-status').innerText.toLowerCase();

                const matchQuery = room.includes(query) || building.includes(query) || floor.includes(query);
                const matchBuilding = bVal === "" || building.includes(bVal);
                const matchStatus = sVal === "" || status.includes(sVal);

                if (matchQuery && matchBuilding && matchStatus) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        searchInput.addEventListener('input', filterTable);
        buildingFilter.addEventListener('change', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>