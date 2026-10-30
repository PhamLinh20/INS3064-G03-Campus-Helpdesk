<?php
// dashboard.php - Classrooms Management (Vertical Slice CRUD)
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
        // SQLSTATE 23000: Integrity constraint violation (Foreign Key RESTRICT)
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

// Sort newest records first for immediate visual feedback during viva demonstration
$stmt = $pdo->query("SELECT * FROM classrooms ORDER BY classroom_id DESC");
$classrooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Management - Campus Helpdesk</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { background-color: #f7f9fa; color: #333; padding: 25px; }
        .container { max-width: 1100px; margin: 0 auto; }
        header { margin-bottom: 20px; }
        header h1 { font-size: 22px; color: #1e3a8a; }
        header p { font-size: 13px; color: #666; margin-top: 4px; }

        /* KPI Summary Cards */
        .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
        .kpi-card { background: #fff; border-radius: 6px; padding: 15px; border-left: 4px solid #1e3a8a; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .kpi-card.active { border-left-color: #16a34a; }
        .kpi-card.inactive { border-left-color: #dc2626; }
        .kpi-title { font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold; }
        .kpi-value { font-size: 24px; font-weight: bold; color: #111; margin-top: 5px; }

        /* Two-column layout */
        .grid { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; }
        .card { background: #fff; border-radius: 6px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card h2 { font-size: 16px; margin-bottom: 15px; color: #1e3a8a; border-bottom: 1px solid #eee; padding-bottom: 8px; }

        /* Form styling */
        .form-group { margin-bottom: 14px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #444; }
        input[type="text"], input[type="number"], select {
            width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;
        }
        input:focus, select:focus { outline: none; border-color: #1e3a8a; }
        .btn-group { display: flex; gap: 8px; margin-top: 18px; }
        .btn { padding: 9px 14px; border: none; border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; }
        .btn-primary { background: #1e3a8a; color: #fff; flex: 1; }
        .btn-primary:hover { background: #172554; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }

        /* Flash notifications */
        .alert { padding: 10px 14px; border-radius: 4px; margin-bottom: 18px; font-size: 13px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert ul { margin-left: 18px; margin-top: 5px; }

        /* Table styling */
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; color: #475569; font-weight: bold; }
        tr:hover { background: #f8fafc; }

        /* Highlight selected row during editing */
        .row-editing {
    background-color: #e0f2fe !important; /* Nền xanh dương phấn dịu mắt */
    border-left: 4px solid #0284c7;        /* Viền xanh nhấn ở mép trái */
}

        .badge { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .action-link { text-decoration: none; font-weight: bold; font-size: 12px; margin-right: 10px; }
        .link-edit { color: #1e3a8a; }
        .link-delete { color: #dc2626; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Campus Helpdesk – Classrooms Management</h1>
        <p>Vertical Slice: Main Table <code>classrooms</code> &amp; Related Table <code>assets</code></p>
    </header>

    <!-- KPI Summary Section -->
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-title">Total Classrooms</div>
            <div class="kpi-value"><?= $total_rooms ?></div>
        </div>
        <div class="kpi-card active">
            <div class="kpi-title">Active Classrooms</div>
            <div class="kpi-value"><?= $active_rooms ?></div>
        </div>
        <div class="kpi-card inactive">
            <div class="kpi-title">Maintenance / Inactive</div>
            <div class="kpi-value"><?= $inactive_rooms ?></div>
        </div>
    </div>

    <!-- Alert notifications -->
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'created'): ?>
            <div class="alert alert-success">Classroom added successfully.</div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">Classroom updated successfully.</div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success">Classroom deleted successfully.</div>
        <?php elseif ($_GET['msg'] === 'fk_error'): ?>
            <div class="alert alert-error"><strong>Constraint Error:</strong> Cannot delete this classroom because active assets are assigned to it (ON DELETE RESTRICT).</div>
        <?php elseif ($_GET['msg'] === 'not_found'): ?>
            <div class="alert alert-error">Requested classroom was not found.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Validation errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please resolve the following input errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="grid">
        <!-- Form Component (Create & Edit) -->
        <div class="card">
            <h2><?= $is_edit ? 'Edit Classroom #' . $edit_id : 'Add New Classroom' ?></h2>
            <form action="dashboard.php" method="POST">
                <input type="hidden" name="edit_id" value="<?= $is_edit ? $edit_id : 0 ?>">

                <div class="form-group">
                    <label for="room_number">Room Number (Unique Code):</label>
                    <input type="text" id="room_number" name="room_number" 
                           value="<?= htmlspecialchars($room_number) ?>" 
                           placeholder="e.g., A1-201, B2-302" required>
                </div>

                <div class="form-group">
                    <label for="building">Building:</label>
                    <input type="text" id="building" name="building" 
                           value="<?= htmlspecialchars($building) ?>" 
                           placeholder="e.g., Building A1, Building B2" required>
                </div>

                <div class="form-group">
                    <label for="floor">Floor (Positive Integer):</label>
                    <input type="number" id="floor" name="floor" min="1" 
                           value="<?= htmlspecialchars($floor) ?>" 
                           placeholder="e.g., 1, 2, 3" required>
                </div>

                <div class="form-group">
                    <label for="is_active">Operational Status:</label>
                    <select id="is_active" name="is_active">
                        <option value="1" <?= $is_active == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $is_active == 0 ? 'selected' : '' ?>>Inactive / Under Maintenance</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <?= $is_edit ? 'Update Classroom' : 'Save Classroom' ?>
                    </button>
                    <?php if ($is_edit): ?>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Data Table Component -->
        <div class="card">
            <h2>Classrooms List (<?= count($classrooms) ?> records)</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Room Number</th>
                        <th>Building</th>
                        <th style="width: 70px;">Floor</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 110px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classrooms)): ?>
                        <tr><td colspan="6" style="text-align: center; color: #888;">No classrooms available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($classrooms as $row): ?>
                            <tr class="<?= ($is_edit && $edit_id === (int)$row['classroom_id']) ? 'row-editing' : '' ?>">
                                <td><?= htmlspecialchars($row['classroom_id']) ?></td>
                                <td><strong><?= htmlspecialchars($row['room_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['building']) ?></td>
                                <td>Floor <?= htmlspecialchars($row['floor']) ?></td>
                                <td>
                                    <?php if ($row['is_active'] == 1): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <a class="action-link link-edit" href="dashboard.php?action=edit&id=<?= $row['classroom_id'] ?>">Edit</a>
                                    <a class="action-link link-delete" 
                                       href="dashboard.php?action=delete&id=<?= $row['classroom_id'] ?>" 
                                       onclick="return confirm('Are you sure you want to delete classroom <?= htmlspecialchars($row['room_number']) ?>?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>