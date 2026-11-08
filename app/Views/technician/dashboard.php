<?php
/**
 * View: Technician Dashboard
 * Tương thích cả khi chạy qua MVC router (Apache/XAMPP) hoặc mở trực tiếp qua VS Code PHP Server (port 3000)
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

$page_title = 'Technician Dashboard - Campus Helpdesk';
$breadcrumb_current = 'Console';
$current_nav = 'dashboard';

require_once APPROOT . '/app/Views/layouts/technician/header.php';
require_once APPROOT . '/app/Views/layouts/technician/sidebar.php';
?>

<div class="pl-64">
  <main class="relative pt-24 pb-12 px-8 w-full min-h-screen bg-background">
    <div class="max-w-[1400px] mx-auto flex flex-col w-full gap-6">

      <!-- Top Section: Header & Welcome -->
      <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
          <h1 class="font-headline-h1 text-headline-h1 text-primary font-bold tracking-tight">👋 Welcome back, <?= htmlspecialchars($userName) ?>!</h1>
          <p class="font-body-default text-body-default text-on-surface-variant max-w-2xl">
            Here is your task overview and repair schedule for today.
          </p>
        </div>
      </div>

      <!-- KPI Overview 4-Card Grid (Khớp dữ liệu phân công nhiệm vụ trong Database) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Assigned Tasks -->
        <div class="bg-surface-container-lowest rounded-xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden border border-surface-variant/30">
          <div class="flex items-center justify-between mb-3">
            <span class="font-caption text-caption text-on-surface-variant font-semibold uppercase tracking-wider">Assigned Tasks</span>
            <div class="w-10 h-10 rounded-full bg-secondary-fixed flex items-center justify-center text-primary">
              <span class="material-symbols-outlined text-[20px]">assignment</span>
            </div>
          </div>
          <div>
            <div class="font-headline-h1 text-headline-h1 text-primary font-bold tracking-tight">3</div>
          </div>
        </div>

        <!-- Card 2: In Progress -->
        <div class="bg-surface-container-lowest rounded-xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden border border-surface-variant/30">
          <div class="flex items-center justify-between mb-3">
            <span class="font-caption text-caption text-on-surface-variant font-semibold uppercase tracking-wider">In Progress</span>
            <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-secondary">
              <span class="material-symbols-outlined text-[20px]">build</span>
            </div>
          </div>
          <div>
            <div class="font-headline-h1 text-headline-h1 text-primary font-bold tracking-tight">1</div>
          </div>
        </div>

        <!-- Card 3: Completed Today -->
        <div class="bg-surface-container-lowest rounded-xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden border border-surface-variant/30">
          <div class="flex items-center justify-between mb-3">
            <span class="font-caption text-caption text-on-surface-variant font-semibold uppercase tracking-wider">Completed</span>
            <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center text-primary-container">
              <span class="material-symbols-outlined text-[20px]">check_circle</span>
            </div>
          </div>
          <div>
            <div class="font-headline-h1 text-headline-h1 text-primary font-bold tracking-tight">2</div>
          </div>
        </div>

        <!-- Card 4: SLA At Risk -->
        <div class="bg-surface-container-lowest rounded-xl p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden border border-surface-variant/30">
          <div class="flex items-center justify-between mb-3">
            <span class="font-caption text-caption text-error font-semibold uppercase tracking-wider">SLA Overdue</span>
            <div class="w-10 h-10 rounded-full bg-error-container flex items-center justify-center text-error">
              <span class="material-symbols-outlined text-[20px]">alarm</span>
            </div>
          </div>
          <div>
            <div class="font-headline-h1 text-headline-h1 text-error font-bold tracking-tight">1</div>
          </div>
        </div>
      </div>

      <!-- Main Content: Recent Assigned Work Orders Table Card -->
      <div class="bg-surface-container-lowest rounded-xl p-5 shadow-sm flex flex-col border border-surface-variant/30">
        <!-- Card Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 mb-4 border-b border-surface-variant/30">
          <div class="flex items-center gap-3 flex-wrap">
            <h2 class="font-headline-h2 text-headline-h2 text-primary font-bold">Recent Work Orders</h2>
            <span class="bg-secondary-fixed text-primary font-caption text-caption px-2.5 py-1 rounded-full font-semibold">
              3 Tasks Assigned
            </span>
          </div>
          <div class="flex items-center gap-3">
            <div class="relative w-64 md:w-72">
              <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-[18px]">search</span>
              <input class="w-full pl-9 pr-3 py-1.5 bg-surface-container-low text-on-surface placeholder-on-surface-variant rounded-xl font-body-default text-body-default focus:outline-none focus:bg-surface-container-lowest shadow-sm border border-transparent focus:border-surface-variant" id="table-search-input" placeholder="Filter by room, ID or keyword..." type="text">
            </div>
            <a class="font-body-medium text-body-medium text-secondary hover:underline flex items-center gap-0.5 whitespace-nowrap font-semibold" href="<?= BASE_URL ?>/technician/tasks">
              <span>View All Tasks</span>
              <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
          </div>
        </div>

        <!-- Table (Khớp 100% dữ liệu bảng tickets và classrooms trong database) -->
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-surface-container-low text-on-surface-variant uppercase font-caption text-caption tracking-wider">
                <th class="py-3 px-4 rounded-l-xl">Ticket Code</th>
                <th class="py-3 px-4">Issue</th>
                <th class="py-3 px-4">Location</th>
                <th class="py-3 px-4">Priority</th>
                <th class="py-3 px-4">SLA Deadline</th>
                <th class="py-3 px-4 rounded-r-xl">Status</th>
              </tr>
            </thead>
            <tbody class="text-body-default font-body-default divide-y divide-surface-variant/20" id="work-orders-tbody">
              <!-- Row 1: TIC-7580 (High Priority / In Progress) -->
              <tr class="hover:bg-surface-container-low transition-colors group">
                <td class="py-3.5 px-4 font-semibold whitespace-nowrap">
                  <span class="font-mono bg-surface-container px-2 py-1 rounded text-primary">TIC-7580</span>
                </td>
                <td class="py-3.5 px-4 max-w-xs">
                  <div class="font-semibold text-primary group-hover:text-secondary transition-colors">
                    Epson EB-X06 Projector flickering HDMI signal
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-on-surface text-[12px]">
                    <span class="material-symbols-outlined text-[16px] text-secondary">location_on</span>
                    Room A1-201
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-error-container text-error text-[12px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                    High
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-error-container text-error text-[12px] font-bold animate-pulse">
                    <span>⏱️</span> 12:15 PM
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-surface-container-high text-primary text-[12px] font-medium">
                    In Progress
                  </span>
                </td>
              </tr>

              <!-- Row 2: TIC-7542 (Low Priority / Assigned) -->
              <tr class="hover:bg-surface-container-low transition-colors group">
                <td class="py-3.5 px-4 font-semibold whitespace-nowrap">
                  <span class="font-mono bg-surface-container px-2 py-1 rounded text-primary">TIC-7542</span>
                </td>
                <td class="py-3.5 px-4 max-w-xs">
                  <div class="font-semibold text-primary group-hover:text-secondary transition-colors">
                    Daikin Inverter AC blowing warm air
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-on-surface text-[12px]">
                    <span class="material-symbols-outlined text-[16px] text-secondary">location_on</span>
                    Room A1-201
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-low text-on-surface-variant text-[12px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-outline"></span>
                    Low
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-high text-primary text-[12px] font-bold">
                    <span>⏱️</span> 08:00 PM
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-secondary-fixed text-primary text-[12px] font-medium">
                    Assigned
                  </span>
                </td>
              </tr>

              <!-- Row 3: TIC-7606 (Medium Priority / Pending) -->
              <tr class="hover:bg-surface-container-low transition-colors group">
                <td class="py-3.5 px-4 font-semibold whitespace-nowrap">
                  <span class="font-mono bg-surface-container px-2 py-1 rounded text-primary">TIC-7606</span>
                </td>
                <td class="py-3.5 px-4 max-w-xs">
                  <div class="font-semibold text-primary group-hover:text-secondary transition-colors">
                    General Facility (Wall Socket sparking)
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-on-surface text-[12px]">
                    <span class="material-symbols-outlined text-[16px] text-secondary">location_on</span>
                    Room A1-202
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-high text-primary text-[12px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                    Medium
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-high text-primary text-[12px] font-bold">
                    <span>⏱️</span> Within 4h
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-[#FEF3C7] text-[#B45309] text-[12px] font-medium">
                    Pending
                  </span>
                </td>
              </tr>

              <!-- Row 4: TIC-7490 (Medium Priority / Completed) -->
              <tr class="hover:bg-surface-container-low transition-colors group">
                <td class="py-3.5 px-4 font-semibold whitespace-nowrap">
                  <span class="font-mono bg-surface-container px-2 py-1 rounded text-primary">TIC-7490</span>
                </td>
                <td class="py-3.5 px-4 max-w-xs">
                  <div class="font-semibold text-primary group-hover:text-secondary transition-colors">
                    HP ProDesk Lab Computers (x30) boot failure
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-on-surface text-[12px]">
                    <span class="material-symbols-outlined text-[16px] text-secondary">computer</span>
                    Lab B2-301
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-high text-primary text-[12px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                    Medium
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-low text-on-surface-variant text-[12px] font-medium">
                    <span>✔️</span> 16:30
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-[#D1FAE5] text-[#059669] text-[12px] font-medium">
                    Completed
                  </span>
                </td>
              </tr>

              <!-- Row 5: TIC-7450 (High Priority / Closed) -->
              <tr class="hover:bg-surface-container-low transition-colors group">
                <td class="py-3.5 px-4 font-semibold whitespace-nowrap">
                  <span class="font-mono bg-surface-container px-2 py-1 rounded text-primary">TIC-7450</span>
                </td>
                <td class="py-3.5 px-4 max-w-xs">
                  <div class="font-semibold text-primary group-hover:text-secondary transition-colors">
                    Cisco 24-Port Gigabit Switch lost LAN
                  </div>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-on-surface text-[12px]">
                    <span class="material-symbols-outlined text-[16px] text-secondary">dns</span>
                    Lab B2-301
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-error-container text-error text-[12px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                    High
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-low text-on-surface-variant text-[12px] font-medium">
                    <span>🔒</span> 16:00
                  </span>
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-[#F1F5F9] text-[#475569] text-[12px] font-medium">
                    Closed
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Interactive Search for Table -->
    <script>
      (function () {
        const searchInput = document.getElementById('table-search-input');
        const tableBody = document.getElementById('work-orders-tbody');

        if (searchInput && tableBody) {
          searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(function (row) {
              const rowText = row.innerText.toLowerCase();
              row.style.display = rowText.includes(query) ? '' : 'none';
            });
          });
        }
      })();
    </script>
  </main>
</div>

<?php
require_once APPROOT . '/app/Views/layouts/technician/footer.php';
?>

<?php
require_once APPROOT . '/app/Views/layouts/technician/footer.php';
?>
