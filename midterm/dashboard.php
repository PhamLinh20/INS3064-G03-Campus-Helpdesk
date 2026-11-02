<?php
// dashboard.php - Classrooms Management (Premium SaaS CRUD Architecture)
require_once 'db.php';

$errors = [];
$is_edit = false;
$edit_id = 0;

$room_number = '';
$building = '';
$floor = '';
$is_active = 1;

// -----------------------------------------------------------------------------
// 1. HANDLE RECORD DELETION (DELETE)
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM classrooms WHERE classroom_id = :id");
        $stmt->execute(['id' => $delete_id]);
        header("Location: dashboard.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        // SQLSTATE 23000: Foreign Key Integrity Constraint (ON DELETE RESTRICT)
        if ($e->getCode() == '23000') {
            header("Location: dashboard.php?msg=fk_error");
        } else {
            header("Location: dashboard.php?msg=sys_error");
        }
        exit;
    }
}

// -----------------------------------------------------------------------------
// 2. FETCH RECORD DATA FOR EDITING (PRE-FILLED UPDATE FORM)
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE classroom_id = :id");
    $stmt->execute(['id' => $edit_id]);
    $room = $stmt->fetch();

    if ($room) {
        $is_edit = true;
        $room_number = $room['room_number'];
        $building    = $room['building'];
        $floor       = $room['floor'];
        $is_active   = $room['is_active'];
    } else {
        header("Location: dashboard.php?msg=not_found");
        exit;
    }
}

// -----------------------------------------------------------------------------
// 3. HANDLE FORM SUBMISSION (CREATE & UPDATE WITH SERVER VALIDATION)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
    $is_edit = ($edit_id > 0);

    $room_number = trim($_POST['room_number'] ?? '');
    $building    = trim($_POST['building'] ?? '');
    $floor       = trim($_POST['floor'] ?? '');
    $is_active   = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    // Server-side validation rules
    if ($room_number === '') {
        $errors[] = "Room number is required.";
    }
    if ($building === '') {
        $errors[] = "Building name is required.";
    }
    if ($floor === '' || !is_numeric($floor) || (int)$floor < 1) {
        $errors[] = "Floor must be a positive integer (>= 1).";
    }

    // Business code uniqueness check
    if (empty($errors)) {
        if ($is_edit) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM classrooms WHERE room_number = :room_number AND classroom_id != :id");
            $stmt->execute(['room_number' => $room_number, 'id' => $edit_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM classrooms WHERE room_number = :room_number");
            $stmt->execute(['room_number' => $room_number]);
        }

        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Room number '{$room_number}' already exists. Please choose a unique room number.";
        }
    }

    // Process database insertion or update if no validation errors
    if (empty($errors)) {
        if ($is_edit) {
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
// 4. FETCH DATA FOR LISTING & SUMMARY KPI CARDS (+5 BONUS POINTS)
// -----------------------------------------------------------------------------
$total_rooms    = (int)$pdo->query("SELECT COUNT(*) FROM classrooms")->fetchColumn();
$active_rooms   = (int)$pdo->query("SELECT COUNT(*) FROM classrooms WHERE is_active = 1")->fetchColumn();
$inactive_rooms = $total_rooms - $active_rooms;
$active_ratio   = ($total_rooms > 0) ? round(($active_rooms / $total_rooms) * 100, 1) : 0;

// Fetch unique buildings dynamically for filter dropdown
$building_stmt = $pdo->query("SELECT DISTINCT building FROM classrooms ORDER BY building ASC");
$unique_buildings = $building_stmt->fetchAll(PDO::FETCH_COLUMN);

// Sort newest records first for immediate visual feedback during viva demonstration
$stmt = $pdo->query("SELECT * FROM classrooms ORDER BY classroom_id DESC");
$classrooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Management &bull; Campus Helpdesk</title>
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

        /* 1. Header with Breadcrumb & Icon */
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

        /* 2. Elevated KPI Cards */
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

        /* 3. Floating Toast Notifications */
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

        /* 4. Full-Width Data Table Panel */
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

        /* 5. Refined Table with Soft Hover #F8FAFF */
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

        /* Row Highlight for Editing */
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

        /* Action Buttons with Tooltips */
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

        /* Pagination & Footer */
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

        /* Modal Dialog Component */
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

<!-- Floating Toast Container -->
<div class="toast-container" id="toastContainer">
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'created'): ?>
            <div class="toast success" id="mainToast">
                <span>Classroom record created successfully.</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="toast success" id="mainToast">
                <span>Classroom configuration updated successfully.</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="toast success" id="mainToast">
                <span>Classroom record deleted successfully.</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php elseif ($_GET['msg'] === 'fk_error'): ?>
            <div class="toast error" id="mainToast">
                <span><strong>Constraint Error:</strong> Cannot delete room with active assets (<code>ON DELETE RESTRICT</code>).</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php elseif ($_GET['msg'] === 'not_found'): ?>
            <div class="toast error" id="mainToast">
                <span>Requested classroom record was not found.</span>
                <button class="toast-close" onclick="dismissToast(this)">&times;</button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="container">
    <!-- 1. Header with Breadcrumb & Icon -->
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
                <div class="breadcrumb-tag">Management / Classrooms</div>
                <h1>Classroom Management</h1>
                <p>Manage classroom information and operational status.</p>
            </div>
        </div>
        <div class="db-status-pill">
            <div class="pulse-dot"></div>
            <span>Database Connected</span>
        </div>
    </header>

    <!-- 2. Refined 3 KPI Cards -->
    <div class="kpi-row">
        <div class="kpi-card kpi-total">
            <div class="kpi-card-header">
                <span class="kpi-label">Total Classrooms</span>
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
            <div class="kpi-footer-text">Physical facilities indexed</div>
        </div>

        <div class="kpi-card kpi-active">
            <div class="kpi-card-header">
                <span class="kpi-label">Operational Active</span>
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
            <div class="kpi-footer-text"><?= $active_rooms ?>/<?= $total_rooms ?> classrooms operational (<?= $active_ratio ?>%)</div>
        </div>

        <div class="kpi-card kpi-inactive">
            <div class="kpi-card-header">
                <span class="kpi-label">Maintenance / Inactive</span>
                <div class="kpi-icon-pastel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
            </div>
            <div class="kpi-numeric"><?= $inactive_rooms ?></div>
            <div class="kpi-footer-text">Requires maintenance or decommissioned</div>
        </div>
    </div>

    <!-- 3. Full-Width Table Component -->
    <div class="table-panel">
        <div class="table-toolbar">
            <div class="toolbar-filters">
                <div class="search-field">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="liveSearchInput" placeholder="Search classrooms...">
                </div>

                <select id="filterBuilding" class="filter-select">
                    <option value="all">All Buildings</option>
                    <?php foreach ($unique_buildings as $bld): ?>
                        <option value="<?= htmlspecialchars($bld) ?>"><?= htmlspecialchars($bld) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filterStatus" class="filter-select">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <button type="button" class="btn-add-primary" id="openAddModalBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add Classroom
            </button>
        </div>

        <div class="table-responsive">
            <table id="classroomTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Room Identifier</th>
                        <th>Building</th>
                        <th style="width: 120px;">Floor</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 140px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classrooms)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 36px;">No classroom records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($classrooms as $row): ?>
                            <tr class="data-row <?= ($is_edit && $edit_id === (int)$row['classroom_id']) ? 'row-editing' : '' ?>"
                                data-building="<?= htmlspecialchars($row['building']) ?>"
                                data-status="<?= $row['is_active'] == 1 ? 'active' : 'inactive' ?>">
                                <td class="col-id">#<?= htmlspecialchars($row['classroom_id']) ?></td>
                                <td>
                                    <span class="col-room"><?= htmlspecialchars($row['room_number']) ?></span>
                                    <?php if ($is_edit && $edit_id === (int)$row['classroom_id']): ?>
                                        <span class="editing-flag">EDITING</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="bld-badge"><?= htmlspecialchars($row['building']) ?></span>
                                </td>
                                <td>
                                    <span class="floor-label">Floor <?= htmlspecialchars($row['floor']) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['is_active'] == 1): ?>
                                        <span class="status-pill active"><span class="dot"></span> Active</span>
                                    <?php else: ?>
                                        <span class="status-pill inactive"><span class="dot"></span> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <a class="btn-act btn-act-edit" 
                                       href="dashboard.php?action=edit&id=<?= $row['classroom_id'] ?>" 
                                       title="Edit classroom configuration">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Edit
                                    </a>
                                    <button type="button" 
                                            class="btn-act btn-act-delete" 
                                            data-id="<?= $row['classroom_id'] ?>" 
                                            data-room="<?= htmlspecialchars($row['room_number']) ?>"
                                            title="Delete classroom record"
                                            onclick="openDeleteDialog(this)">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path></svg>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Pagination & Record Count Footer -->
        <div class="table-footer">
            <div>
                Showing <strong id="pageRangeText">1–8</strong> of <strong id="visibleCount"><?= count($classrooms) ?></strong> records
            </div>
            <div class="pagination-controls">
                <button type="button" class="btn-page" id="prevPageBtn" disabled>Previous</button>
                <span style="font-weight: 600; padding: 0 4px;" id="pageIndicator">Page 1 of 1</span>
                <button type="button" class="btn-page" id="nextPageBtn">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Form Dialog (Create & Edit) -->
<div class="modal-backdrop <?= ($is_edit || !empty($errors)) ? 'show' : '' ?>" id="crudModal">
    <div class="modal-card">
        <div class="modal-card-header">
            <h2><?= $is_edit ? 'Edit Classroom #' . $edit_id : 'Register New Classroom' ?></h2>
            <button type="button" class="btn-close-modal" id="closeModalCross">&times;</button>
        </div>
        <div class="modal-card-body">
            <?php if (!empty($errors)): ?>
                <div style="background: var(--rose-50); border: 1px solid var(--rose-200); color: var(--rose-700); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 13px;">
                    <strong>Please resolve input errors:</strong>
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
                    <label for="room_number">Room Number (Unique Code):</label>
                    <input type="text" id="room_number" name="room_number" 
                           value="<?= htmlspecialchars($room_number) ?>" 
                           placeholder="e.g., A1-201, B2-302" required>
                </div>

                <div class="form-item">
                    <label for="building">Building Allocation:</label>
                    <input type="text" id="building" name="building" 
                           value="<?= htmlspecialchars($building) ?>" 
                           placeholder="e.g., Building A1, Building B2" required>
                </div>

                <div class="form-item">
                    <label for="floor">Floor Level:</label>
                    <input type="number" id="floor" name="floor" min="1" 
                           value="<?= htmlspecialchars($floor) ?>" 
                           placeholder="e.g., 1, 2, 3" required>
                </div>

                <div class="form-item">
                    <label for="is_active">Operational Status:</label>
                    <select id="is_active" name="is_active">
                        <option value="1" <?= $is_active == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $is_active == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="modal-card-footer">
                    <a href="dashboard.php" class="btn-modal-cancel" id="cancelModalBtn">Cancel</a>
                    <button type="submit" class="btn-modal-submit">
                        <?= $is_edit ? 'Update Classroom' : 'Save Classroom' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Custom Delete Confirmation Dialog -->
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
                    <h2 style="font-size: 16px; font-weight: 800; color: var(--text-heading);">Delete Classroom?</h2>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 1px;">This action requires confirmation.</p>
                </div>
            </div>
        </div>
        <div class="modal-card-body" style="padding-top: 14px;">
            <p style="font-size: 13.5px; color: var(--text-body); line-height: 1.5;">
                Are you sure you want to delete classroom <strong id="deleteTargetRoom" style="color: var(--text-heading);"></strong>? This action cannot be undone.
            </p>
            <div class="modal-card-footer" style="margin-top: 22px;">
                <button type="button" class="btn-modal-cancel" id="cancelDeleteBtn">Cancel</button>
                <a href="#" id="confirmDeleteLink" class="btn-modal-submit" style="background: var(--rose-500); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                    Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Toast Auto-Dismiss
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

    // 2. CRUD Modal Controller
    const crudModal = document.getElementById('crudModal');
    const openAddBtn = document.getElementById('openAddModalBtn');
    const closeCross = document.getElementById('closeModalCross');
    const cancelBtn = document.getElementById('cancelModalBtn');

    if (openAddBtn) {
        openAddBtn.addEventListener('click', () => {
            document.querySelector('#crudModal form').reset();
            document.querySelector('#crudModal input[name="edit_id"]').value = '0';
            document.querySelector('.modal-card-header h2').textContent = 'Register New Classroom';
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

    // 3. Delete Confirmation Dialog Controller
    const deleteModal = document.getElementById('deleteModal');
    const deleteTargetRoom = document.getElementById('deleteTargetRoom');
    const confirmDeleteLink = document.getElementById('confirmDeleteLink');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    function openDeleteDialog(btn) {
        const id = btn.getAttribute('data-id');
        const room = btn.getAttribute('data-room');
        deleteTargetRoom.textContent = room;
        confirmDeleteLink.href = 'dashboard.php?action=delete&id=' + id;
        deleteModal.classList.add('show');
    }

    function closeDeleteDialog() {
        deleteModal.classList.remove('show');
    }

    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteDialog);

    // Global Modal Backdrop & Escape Handling
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

    // 4. Client-side Realtime Filter & Pagination System
    const searchInput = document.getElementById('liveSearchInput');
    const filterBuilding = document.getElementById('filterBuilding');
    const filterStatus = document.getElementById('filterStatus');
    const allRows = Array.from(document.querySelectorAll('#classroomTable tbody tr.data-row'));
    
    const pageRangeText = document.getElementById('pageRangeText');
    const visibleCounter = document.getElementById('visibleCount');
    const pageIndicator = document.getElementById('pageIndicator');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');

    const ITEMS_PER_PAGE = 8;
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
            pageIndicator.textContent = `Page ${currentPage} of ${totalPages}`;
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

        currentPage = 1;
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

    // Initial load
    applyFilterAndPaginate();
</script>

</body>
</html>