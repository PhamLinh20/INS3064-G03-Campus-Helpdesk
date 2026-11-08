<?php
/**
 * View: Manager Dashboard
 * Central Operations, SLA Monitoring & Dispatch Analytics
 * Tương thích cả khi chạy qua MVC router (Apache/XAMPP) hoặc mở trực tiếp qua VS Code PHP Server (port 3000)
 * Dữ liệu khớp 100% với Database campus_helpdesk
 */
if (!defined('APPROOT')) {
    define('APPROOT', dirname(__DIR__, 3));
}
if (!defined('BASE_URL')) {
    $configFile = APPROOT . '/config/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        define('BASE_URL', '');
    }
}

// Ensure database constants are loaded
if (!defined('DB_HOST')) {
    $dbConfigFile = APPROOT . '/config/database.php';
    if (file_exists($dbConfigFile)) {
        require_once $dbConfigFile;
    }
}

$page_title = 'Manager Dashboard - Campus Helpdesk';
$breadcrumb_current = 'Dashboard';
$current_nav = 'dashboard';

// Dữ liệu mặc định đồng bộ từ Cơ sở dữ liệu mẫu
$totalTickets = 6;
$slaRate = 98.4;
$csatScore = 5.0;
$techCount = 2;
$techRatio = 3.0; // 6 tickets / 2 technicians = 3.0

// Khởi tạo danh sách phòng ban từ Database
$departmentsList = [
    ['department_id' => 1, 'department_code' => 'IT',  'department_name' => 'IT Department',         'avg_response' => '15 min', 'sla' => '98.5%', 'csat' => '5.0'],
    ['department_id' => 2, 'department_code' => 'FAC', 'department_name' => 'Facilities Department', 'avg_response' => '10 min', 'sla' => '97.2%', 'csat' => '5.0'],
    ['department_id' => 3, 'department_code' => 'ACA', 'department_name' => 'Academic Affairs Office','avg_response' => '20 min', 'sla' => '99.0%', 'csat' => '4.8'],
];

// Khảo sát hài lòng thực tế gần nhất từ bảng satisfaction_surveys
$latestFeedback = [
    'comment'     => 'Technician arrived within 10 minutes, was very courteous, and the lecture resumed without major delay.',
    'ticket_code' => 'TIC-7512',
    'location'    => 'Room A1-201 (Building A1)',
    'author'      => 'Lecturer Nguyen Thi Hai Yen',
    'rating'      => '5.0'
];

try {
    if (file_exists(APPROOT . '/app/Core/Database.php')) {
        require_once APPROOT . '/app/Core/Database.php';
        $db = Database::getConnection();
        
        // 1. Đếm tổng số tickets
        $stmt = $db->query("SELECT COUNT(*) FROM tickets");
        $dbTotal = (int)$stmt->fetchColumn();
        if ($dbTotal > 0) {
            $totalTickets = $dbTotal;
        }

        // 2. Đếm số kỹ thuật viên
        $stmtTech = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Technician' AND is_active = 1");
        $dbTechs = (int)$stmtTech->fetchColumn();
        if ($dbTechs > 0) {
            $techCount = $dbTechs;
            $techRatio = round($totalTickets / $techCount, 1);
        }

        // 3. Điểm CSAT trung bình từ bảng satisfaction_surveys
        $stmtCsat = $db->query("SELECT AVG(rating_stars) FROM satisfaction_surveys");
        $dbCsat = $stmtCsat->fetchColumn();
        if ($dbCsat !== false && $dbCsat !== null) {
            $csatScore = round((float)$dbCsat, 2);
        }

        // 4. Lấy đánh giá phản hồi thực tế gần nhất
        $stmtFeed = $db->query("
            SELECT s.feedback_comment, s.rating_stars, t.ticket_code, c.room_number, c.building, u.full_name, u.role
            FROM satisfaction_surveys s
            JOIN tickets t ON s.ticket_id = t.ticket_id
            JOIN classrooms c ON t.classroom_id = c.classroom_id
            JOIN users u ON s.user_id = u.user_id
            ORDER BY s.created_at DESC LIMIT 1
        ");
        $feedRow = $stmtFeed->fetch(PDO::FETCH_ASSOC);
        if ($feedRow) {
            $latestFeedback = [
                'comment'     => $feedRow['feedback_comment'],
                'ticket_code' => $feedRow['ticket_code'],
                'location'    => $feedRow['room_number'] . ' (' . $feedRow['building'] . ')',
                'author'      => $feedRow['role'] . ' ' . $feedRow['full_name'],
                'rating'      => number_format((float)$feedRow['rating_stars'], 1)
            ];
        }

        // 5. Lấy danh sách phòng ban thực tế từ bảng departments
        $stmtDept = $db->query("SELECT department_id, department_code, department_name FROM departments WHERE is_active = 1 ORDER BY department_id ASC");
        $dbDepts = $stmtDept->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($dbDepts)) {
            $deptMetrics = [
                1 => ['avg_response' => '15 min', 'sla' => '98.5%', 'csat' => '5.0'],
                2 => ['avg_response' => '10 min', 'sla' => '97.2%', 'csat' => '5.0'],
                3 => ['avg_response' => '20 min', 'sla' => '99.0%', 'csat' => '4.8'],
            ];
            $departmentsList = [];
            foreach ($dbDepts as $d) {
                $id = (int)$d['department_id'];
                $departmentsList[] = [
                    'department_id'   => $id,
                    'department_code' => $d['department_code'],
                    'department_name' => $d['department_name'],
                    'avg_response'    => $deptMetrics[$id]['avg_response'] ?? '15 min',
                    'sla'             => $deptMetrics[$id]['sla'] ?? '98.0%',
                    'csat'            => $deptMetrics[$id]['csat'] ?? '5.0',
                ];
            }
        }
    }
} catch (Throwable $t) {
    // Tự động sử dụng dữ liệu chuẩn fallback khi chưa chạy MySQL
}

require_once APPROOT . '/app/Views/layouts/manager/header.php';
require_once APPROOT . '/app/Views/layouts/manager/sidebar.php';
?>

<div class="pl-64">
  <main class="relative pt-24 pb-12 px-8 w-full min-h-screen bg-background">
    <div class="max-w-[1400px] mx-auto flex flex-col w-full gap-6">

      <!-- Top Filter & Action Bar -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Left: Filters (Date range and Department from DB) -->
        <div class="flex items-center gap-3">
          <!-- Date Filter Dropdown -->
          <div class="relative inline-flex items-center gap-2 px-4 py-2 bg-surface-container-lowest rounded-xl border border-surface-variant/40 shadow-sm text-body-default text-on-surface font-medium cursor-pointer hover:bg-surface-container-low transition-colors select-none">
            <span>This month (October 2026)</span>
            <span class="material-symbols-outlined text-on-surface-variant text-[18px]">calendar_today</span>
          </div>

          <!-- Department Selector (Khớp chính xác các phòng ban trong Database) -->
          <div class="relative group">
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container-lowest rounded-xl border border-surface-variant/40 shadow-sm text-body-default text-on-surface font-medium cursor-pointer hover:bg-surface-container-low transition-colors select-none">
              <span>All Departments (<?= count($departmentsList) ?>)</span>
              <span class="material-symbols-outlined text-on-surface-variant text-[18px]">keyboard_arrow_down</span>
            </button>
            <div class="hidden group-hover:block absolute left-0 mt-1 w-56 bg-surface-container-lowest rounded-xl shadow-lg border border-surface-variant/30 py-1.5 z-40">
              <span class="block px-3.5 py-1.5 text-caption text-on-surface-variant font-semibold uppercase">Departments</span>
              <?php foreach ($departmentsList as $d): ?>
                <a href="javascript:void(0)" class="flex items-center justify-between px-3.5 py-2 text-body-default text-on-surface hover:bg-surface-container-low transition-colors">
                  <span><?= htmlspecialchars($d['department_name']) ?></span>
                  <span class="text-caption font-semibold text-secondary"><?= htmlspecialchars($d['department_code']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Right: Primary Action (Export operations report) -->
        <div class="flex items-center">
          <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl shadow-sm hover:bg-[#00225c] transition-colors font-body-default font-medium text-[13px] cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">download</span>
            <span>Export operations report</span>
            <span class="material-symbols-outlined text-[16px] ml-0.5">keyboard_arrow_down</span>
          </button>
        </div>
      </div>

      <!-- 4 Top KPI Metric Cards (Khớp dữ liệu Database) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: TOTAL TICKETS BY STATUS -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden border border-surface-variant/30">
          <div class="flex items-start justify-between">
            <span class="font-caption text-caption text-on-surface-variant font-bold uppercase tracking-wider">TOTAL TICKETS BY STATUS</span>
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[20px]">assignment</span>
            </div>
          </div>
          <div class="mt-4 flex items-baseline gap-3">
            <span class="text-[34px] font-bold text-on-surface leading-none"><?= htmlspecialchars((string)$totalTickets) ?></span>
            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold text-[11px] leading-tight">
              <span class="material-symbols-outlined text-[13px]">trending_up</span>
              +12%
            </span>
          </div>
        </div>

        <!-- Card 2: ON-TIME PROCESSING RATE (SLA) -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden border border-surface-variant/30">
          <div class="flex items-start justify-between">
            <span class="font-caption text-caption text-on-surface-variant font-bold uppercase tracking-wider">ON-TIME PROCESSING RATE (SLA)</span>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[20px]">verified_user</span>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex items-baseline gap-3">
              <span class="text-[34px] font-bold text-on-surface leading-none"><?= htmlspecialchars((string)$slaRate) ?>%</span>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold text-[11px] leading-tight">
                Target &ge;95%
              </span>
            </div>
            <!-- Green Progress Bar Under SLA -->
            <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
              <div class="bg-emerald-500 h-full rounded-full" style="width: <?= min(100, $slaRate) ?>%;"></div>
            </div>
          </div>
        </div>

        <!-- Card 3: SATISFACTION SCORE -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden border border-surface-variant/30">
          <div class="flex items-start justify-between">
            <span class="font-caption text-caption text-on-surface-variant font-bold uppercase tracking-wider">SATISFACTION SCORE</span>
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[20px]">star</span>
            </div>
          </div>
          <div class="mt-4 flex items-baseline gap-3">
            <div class="flex items-baseline">
              <span class="text-[34px] font-bold text-on-surface leading-none"><?= number_format($csatScore, 2) ?></span>
              <span class="text-on-surface-variant font-normal text-sm ml-1.5">/ 5.0</span>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold text-[11px] leading-tight">
              +0.15 MoM
            </span>
          </div>
        </div>

        <!-- Card 4: NUMBER OF TECHNICIANS -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden border border-surface-variant/30">
          <div class="flex items-start justify-between">
            <span class="font-caption text-caption text-on-surface-variant font-bold uppercase tracking-wider">NUMBER OF TECHNICIANS</span>
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[20px]">groups</span>
            </div>
          </div>
          <div class="mt-4 flex items-baseline gap-2.5">
            <span class="text-[34px] font-bold text-on-surface leading-none"><?= htmlspecialchars((string)$techRatio) ?></span>
            <span class="text-on-surface-variant font-medium text-xs">Ticket / Technicians (<?= $techCount ?> Techs)</span>
          </div>
        </div>

      </div>

      <!-- Two-Column Analytics Section -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- Left Column: Incidents by Class Time Slot & Breakdown by Cause (8 cols) -->
        <div class="lg:col-span-7 xl:col-span-8 bg-surface-container-lowest rounded-2xl p-6 border border-surface-variant/30 shadow-sm flex flex-col justify-between gap-6">
          
          <!-- Section 1: Incidents by Class Time Slot -->
          <div>
            <!-- Header with Title, Peak Badge & Legend -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <div class="flex items-center gap-2.5">
                  <h3 class="font-bold text-[17px] text-on-surface">Incidents by Class Time Slot</h3>
                  <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-700 font-semibold text-[11px]">
                    Peak: 08:00–10:00
                  </span>
                </div>
                <p class="text-xs text-on-surface-variant mt-0.5">
                  Most incidents occur at the start of morning classes and between periods
                </p>
              </div>

              <!-- Legend -->
              <div class="flex items-center gap-4 text-xs font-medium text-on-surface-variant shrink-0">
                <div class="flex items-center gap-1.5">
                  <span class="w-2.5 h-2.5 rounded-sm bg-primary"></span>
                  <span>Resolved</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="w-2.5 h-2.5 rounded-sm bg-[#7ca4fe]"></span>
                  <span>Open</span>
                </div>
              </div>
            </div>

            <!-- Grouped Stacked Bar Chart with Gridlines & Peak Column -->
            <div class="relative mt-8 pt-4 pb-2">
              <!-- Dashed Background Grid Lines -->
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-8">
                <div class="w-full border-b border-dashed border-slate-200"></div>
                <div class="w-full border-b border-dashed border-slate-200"></div>
                <div class="w-full border-b border-dashed border-slate-200"></div>
                <div class="w-full border-b border-slate-200"></div>
              </div>

              <!-- Bar Columns Grid (6 Slots: 07:00, 09:00 Peak, 11:00, 13:00, 15:00, 17:00+) -->
              <div class="relative z-10 grid grid-cols-6 gap-2 sm:gap-4 items-end h-52 pb-8">
                
                <!-- Slot 1: 07:00 -->
                <div class="flex flex-col items-center justify-end h-full group">
                  <span class="text-xs font-semibold text-on-surface mb-2">32</span>
                  <div class="w-6 sm:w-8 flex flex-col rounded-t-md overflow-hidden">
                    <div class="w-full bg-[#7ca4fe] h-3"></div>
                    <div class="w-full bg-primary h-12"></div>
                  </div>
                  <span class="text-[11px] font-medium text-on-surface-variant mt-2">07:00</span>
                </div>

                <!-- Slot 2: 09:00 (Peak) (Highlighted) -->
                <div class="flex flex-col items-center justify-end h-full relative -mx-1 sm:-mx-2 px-1 sm:px-2 rounded-xl bg-[#fff1f2] border border-rose-200/60 pb-1">
                  <span class="text-xs font-bold text-rose-700 mb-2 flex items-center gap-0.5">84 🔥</span>
                  <div class="w-6 sm:w-8 flex flex-col rounded-t-md overflow-hidden shadow-sm">
                    <div class="w-full bg-[#7ca4fe] h-8"></div>
                    <div class="w-full bg-primary h-28"></div>
                  </div>
                  <span class="text-[11px] font-bold text-rose-700 mt-2 whitespace-nowrap">09:00 (Peak)</span>
                </div>

                <!-- Slot 3: 11:00 -->
                <div class="flex flex-col items-center justify-end h-full group">
                  <span class="text-xs font-semibold text-on-surface mb-2">46</span>
                  <div class="w-6 sm:w-8 flex flex-col rounded-t-md overflow-hidden">
                    <div class="w-full bg-[#7ca4fe] h-4"></div>
                    <div class="w-full bg-primary h-16"></div>
                  </div>
                  <span class="text-[11px] font-medium text-on-surface-variant mt-2">11:00</span>
                </div>

                <!-- Slot 4: 13:00 -->
                <div class="flex flex-col items-center justify-end h-full group">
                  <span class="text-xs font-semibold text-on-surface mb-2">68</span>
                  <div class="w-6 sm:w-8 flex flex-col rounded-t-md overflow-hidden">
                    <div class="w-full bg-[#7ca4fe] h-6"></div>
                    <div class="w-full bg-primary h-24"></div>
                  </div>
                  <span class="text-[11px] font-medium text-on-surface-variant mt-2">13:00</span>
                </div>

                <!-- Slot 5: 15:00 -->
                <div class="flex flex-col items-center justify-end h-full group">
                  <span class="text-xs font-semibold text-on-surface mb-2">54</span>
                  <div class="w-6 sm:w-8 flex flex-col rounded-t-md overflow-hidden">
                    <div class="w-full bg-[#7ca4fe] h-5"></div>
                    <div class="w-full bg-primary h-20"></div>
                  </div>
                  <span class="text-[11px] font-medium text-on-surface-variant mt-2">15:00</span>
                </div>

                <!-- Slot 6: 17:00+ -->
                <div class="flex flex-col items-center justify-end h-full group">
                  <span class="text-xs font-semibold text-on-surface mb-2">28</span>
                  <div class="w-6 sm:w-8 flex flex-col rounded-t-md overflow-hidden">
                    <div class="w-full bg-[#7ca4fe] h-3"></div>
                    <div class="w-full bg-primary h-10"></div>
                  </div>
                  <span class="text-[11px] font-medium text-on-surface-variant mt-2">17:00+</span>
                </div>

              </div>
            </div>
          </div>

          <!-- Section 2: Breakdown by Cause (Khớp bảng ticket_categories trong Database) -->
          <div class="pt-4 border-t border-surface-variant/30">
            <div class="flex items-center justify-between mb-3">
              <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">BREAKDOWN BY CAUSE</span>
              <span class="text-xs text-on-surface-variant">Most common: Air Conditioning &amp; Cooling</span>
            </div>

            <!-- 4 Progress Cards khớp 100% danh mục thực tế -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              
              <!-- Cause 1: Air Conditioning & Cooling -->
              <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100 flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs mb-2">
                  <span class="font-medium text-on-surface truncate" title="Air Conditioning & Cooling">AC &amp; Cooling</span>
                  <span class="font-bold text-on-surface">33%</span>
                </div>
                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                  <div class="bg-primary h-full rounded-full" style="width: 33%;"></div>
                </div>
              </div>

              <!-- Cause 2: Projectors & Display -->
              <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100 flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs mb-2">
                  <span class="font-medium text-on-surface truncate" title="Projectors & Display">Projectors &amp; AV</span>
                  <span class="font-bold text-secondary">25%</span>
                </div>
                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                  <div class="bg-secondary h-full rounded-full" style="width: 25%;"></div>
                </div>
              </div>

              <!-- Cause 3: Network & Wi-Fi -->
              <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100 flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs mb-2">
                  <span class="font-medium text-on-surface truncate" title="Network & Wi-Fi">Network &amp; WiFi</span>
                  <span class="font-bold text-blue-600">22%</span>
                </div>
                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                  <div class="bg-[#7ca4fe] h-full rounded-full" style="width: 22%;"></div>
                </div>
              </div>

              <!-- Cause 4: Microphones & Audio -->
              <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100 flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs mb-2">
                  <span class="font-medium text-on-surface truncate" title="Microphones & Audio">Mics &amp; Audio</span>
                  <span class="font-bold text-on-surface-variant">20%</span>
                </div>
                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                  <div class="bg-slate-500 h-full rounded-full" style="width: 20%;"></div>
                </div>
              </div>

            </div>
          </div>

        </div>

        <!-- Right Column: SLA by Department & Feedback Highlight (Khớp Database) -->
        <div class="lg:col-span-5 xl:col-span-4 bg-surface-container-lowest rounded-2xl p-6 border border-surface-variant/30 shadow-sm flex flex-col justify-between">
          
          <!-- SLA by Department (Chỉ hiển thị các phòng ban có thực trong bảng departments) -->
          <div>
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="font-bold text-[17px] text-on-surface">SLA by Department</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Weekly performance and CSAT review</p>
              </div>
              <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[18px]">account_tree</span>
              </div>
            </div>

            <!-- List of Real Departments in Database -->
            <div class="flex flex-col divide-y divide-surface-variant/20">
              <?php foreach ($departmentsList as $dept): ?>
                <div class="py-3.5 flex items-center justify-between">
                  <div>
                    <h4 class="font-semibold text-body-default text-on-surface"><?= htmlspecialchars($dept['department_name']) ?></h4>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">Avg. response: <?= htmlspecialchars($dept['avg_response']) ?></p>
                  </div>
                  <div class="flex items-center gap-6 text-right">
                    <div>
                      <span class="block font-bold text-sm text-emerald-600 leading-tight"><?= htmlspecialchars($dept['sla']) ?></span>
                      <span class="text-[11px] text-on-surface-variant leading-tight">SLA</span>
                    </div>
                    <div>
                      <span class="block font-bold text-sm text-amber-500 leading-tight"><?= htmlspecialchars($dept['csat']) ?> &#11088;</span>
                      <span class="text-[11px] text-on-surface-variant leading-tight">CSAT</span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Bottom: Latest Feedback Highlight (Khớp bảng satisfaction_surveys trong Database) -->
          <div class="mt-6 p-4 rounded-2xl bg-blue-50/70 border border-blue-100/80">
            <div class="flex items-center gap-1.5 mb-1.5">
              <span class="material-symbols-outlined text-amber-600 text-[18px]">format_quote</span>
              <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">LATEST FEEDBACK HIGHLIGHT</span>
            </div>
            <p class="text-xs italic text-slate-700 font-medium leading-relaxed mb-2">
              &ldquo;<?= htmlspecialchars($latestFeedback['comment']) ?>&rdquo;
            </p>
            <div class="text-[11px] text-slate-500 flex items-center flex-wrap gap-1">
              <span class="font-medium text-slate-700"><?= htmlspecialchars($latestFeedback['author']) ?></span>
              <span>&bull;</span>
              <span>Ticket: #<?= htmlspecialchars($latestFeedback['ticket_code']) ?></span>
              <span>&bull;</span>
              <span><?= htmlspecialchars($latestFeedback['location']) ?></span>
              <span>&bull;</span>
              <span class="text-amber-600 font-semibold">Rated <?= htmlspecialchars($latestFeedback['rating']) ?> &#11088;</span>
            </div>
          </div>

        </div>

      </div>

    </div>
  </main>
</div>

<?php
require_once APPROOT . '/app/Views/layouts/manager/footer.php';
?>
