<?php
/**
 * View mẫu: Classroom Management (/master/classrooms)
 * - 3 thẻ thống kê: tổng phòng (active/inactive), thiết bị đang gắn, phiếu đang mở.
 * - Tab "Classroom": phòng nhóm theo tầng, có tìm kiếm, sắp xếp, lọc tầng.
 * - Tab "All Assets": toàn bộ thiết bị trong các phòng.
 * Thêm phòng ở trang riêng /master/classroomCreate, xem chi tiết ở /master/classroomView?id=.
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

$rows = $rows ?? [];
$assets = $assets ?? [];
$stats = $stats ?? ['assets' => 0, 'open_tickets' => 0];
$page_title = $page_title ?? 'Classrooms Management - Campus Helpdesk';
$breadcrumb_current = $breadcrumb_current ?? 'Classrooms Management';
$current_nav = $current_nav ?? 'classrooms';
$flash = $flash ?? null;

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$total = count($rows);
$activeCount = 0;
$byFloor = [];
foreach ($rows as $row) {
    if ((int) $row['is_active'] === 1) {
        $activeCount++;
    }
    $byFloor[(int) $row['floor']][] = $row;
}
ksort($byFloor);
$inactiveCount = $total - $activeCount;

/** Healthy / Need Attention / Inactive cho từng phòng. */
$roomStatus = static function (array $row): array {
    if ((int) $row['is_active'] !== 1) {
        return ['Inactive', 'bg-slate-100 text-slate-500 border-slate-200'];
    }
    if ((int) $row['open_tickets'] > 0 || (int) $row['faulty_count'] > 0) {
        return ['Need Attention', 'bg-amber-50 text-amber-700 border-amber-200'];
    }
    return ['Healthy', 'bg-emerald-50 text-emerald-700 border-emerald-200'];
};

$assetBadge = [
    'Active' => ['Working', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'Faulty' => ['Faulty', 'bg-red-50 text-red-700 border-red-200'],
    'Under_Repair' => ['Under Repair', 'bg-amber-50 text-amber-700 border-amber-200'],
    'Decommissioned' => ['Decommissioned', 'bg-slate-100 text-slate-500 border-slate-200'],
];

require_once APPROOT . '/app/Views/layouts/manager/header.php';
require_once APPROOT . '/app/Views/layouts/manager/sidebar.php';
?>

<div class="pl-64">
  <main class="relative pt-24 pb-12 px-8 w-full min-h-screen bg-background">
    <div class="max-w-[1400px] mx-auto flex flex-col w-full gap-6">

      <!-- Tiêu đề + nút thêm phòng -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h1 class="text-[28px] font-bold text-on-surface tracking-tight">Classroom Management</h1>
        <a href="<?= BASE_URL ?>/master/classroomCreate"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl shadow-sm hover:bg-[#00225c] transition-colors font-medium text-[14px] shrink-0">
          <span class="material-symbols-outlined text-[20px]">add_circle</span>
          <span>Add New Classroom</span>
        </a>
      </div>

      <?php if (is_array($flash) && !empty($flash['message'])): ?>
        <?php $flashOk = ($flash['type'] ?? '') === 'success'; ?>
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl border <?= $flashOk ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-error-container border-error/20 text-on-error-container' ?>" role="status">
          <span class="material-symbols-outlined text-[20px]"><?= $flashOk ? 'check_circle' : 'error' ?></span>
          <p class="text-[14px] leading-6"><?= $e($flash['message']) ?></p>
        </div>
      <?php endif; ?>

      <!-- 3 thẻ thống kê -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="relative overflow-hidden bg-surface-container-lowest rounded-2xl p-5 border border-surface-variant/30 shadow-sm">
          <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-primary-fixed/40"></div>
          <div class="relative flex items-start justify-between">
            <p class="text-[14px] text-on-surface-variant font-medium uppercase tracking-wider">Total Classrooms</p>
            <span class="w-9 h-9 rounded-lg bg-primary-fixed text-primary flex items-center justify-center">
              <span class="material-symbols-outlined text-[20px]">door_front</span>
            </span>
          </div>
          <p class="relative mt-2"><span class="text-[24px] font-semibold text-on-surface"><?= (int) $total ?></span> <span class="text-[14px] text-on-surface-variant">Rooms</span></p>
          <div class="relative mt-2 flex items-center gap-3 text-[13px]">
            <span class="px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface font-medium">• <?= (int) $activeCount ?> Active</span>
            <span class="text-on-surface-variant">• <?= (int) $inactiveCount ?> Inactive</span>
          </div>
        </div>

        <div class="relative overflow-hidden bg-surface-container-lowest rounded-2xl p-5 border border-surface-variant/30 shadow-sm">
          <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-primary-fixed/40"></div>
          <div class="relative flex items-start justify-between">
            <p class="text-[14px] text-on-surface-variant font-medium uppercase tracking-wider">Assigned Equipment</p>
            <span class="w-9 h-9 rounded-lg bg-primary-fixed text-primary flex items-center justify-center">
              <span class="material-symbols-outlined text-[20px]">devices</span>
            </span>
          </div>
          <p class="relative mt-4"><span class="text-[24px] font-semibold text-on-surface"><?= (int) $stats['assets'] ?></span> <span class="text-[14px] text-on-surface-variant">Assets</span></p>
        </div>

        <div class="relative overflow-hidden bg-surface-container-lowest rounded-2xl p-5 border border-surface-variant/30 shadow-sm">
          <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-red-50"></div>
          <div class="relative flex items-start justify-between">
            <p class="text-[14px] text-on-surface-variant font-medium uppercase tracking-wider">Open Incidents</p>
            <span class="w-9 h-9 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
              <span class="material-symbols-outlined text-[20px]">warning</span>
            </span>
          </div>
          <p class="relative mt-4"><span class="text-[24px] font-semibold text-red-600"><?= (int) $stats['open_tickets'] ?></span> <span class="text-[14px] text-on-surface-variant">Tickets in progress</span></p>
        </div>
      </div>

      <!-- Tabs + thanh tìm kiếm -->
      <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-variant/30 shadow-sm flex flex-col gap-4">
        <div class="flex items-center gap-2">
          <button type="button" data-tab="rooms" class="js-tab px-5 py-2 rounded-lg text-[13px] font-medium bg-primary text-white">Classroom</button>
          <button type="button" data-tab="assets" class="js-tab px-5 py-2 rounded-lg text-[13px] font-medium bg-surface-container-high text-on-surface-variant">All Assets</button>
        </div>
        <div class="flex flex-col md:flex-row gap-3">
          <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
            <input id="room-search" type="search" placeholder="Search ..." class="w-full h-11 pl-10 pr-3 bg-white rounded-xl text-[14px] text-on-surface placeholder:text-on-surface-variant/70 border border-outline-variant focus:border-primary outline-none">
          </div>
          <select id="room-sort" class="h-11 px-3 pr-8 bg-white rounded-xl text-[14px] text-on-surface border border-outline-variant focus:border-primary outline-none">
            <option value="name-asc">Room Name</option>
            <option value="name-desc">Room Name (Z-A)</option>
            <option value="assets-desc">Most Assets</option>
            <option value="status">Needs Attention First</option>
          </select>
          <select id="room-floor" class="h-11 px-3 pr-8 bg-white rounded-xl text-[14px] text-on-surface border border-outline-variant focus:border-primary outline-none">
            <option value="all">All Floors</option>
            <?php foreach (array_keys($byFloor) as $floorNo): ?>
              <option value="<?= (int) $floorNo ?>">Floor <?= (int) $floorNo ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- TAB 1: phòng theo tầng -->
      <div id="tab-rooms" class="flex flex-col gap-4">
        <?php if ($total === 0): ?>
          <div class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 px-6 py-16 text-center text-on-surface-variant">
            <span class="material-symbols-outlined text-[40px]">meeting_room</span>
            <p class="mt-2 text-[14px]">No classrooms yet. Click <b>Add New Classroom</b> to create the first one.</p>
          </div>
        <?php endif; ?>

        <?php foreach ($byFloor as $floorNo => $floorRows): ?>
          <section class="js-floor bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm overflow-hidden" data-floor="<?= (int) $floorNo ?>">
            <button type="button" class="js-floor-toggle w-full flex items-center justify-between px-5 py-4 bg-[#0b5394] text-white">
              <span class="flex items-center gap-3 text-[16px] font-semibold">
                <span class="material-symbols-outlined text-[22px]">apartment</span>
                Floor <?= (int) $floorNo ?>
              </span>
              <span class="material-symbols-outlined js-chevron transition-transform">expand_more</span>
            </button>
            <div class="js-floor-body">
              <table class="w-full text-left">
                <thead>
                  <tr class="bg-surface-container-low text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
                    <th class="px-5 py-3 w-[25%]">Room</th>
                    <th class="px-5 py-3 w-[25%]">Assets</th>
                    <th class="px-5 py-3 w-[25%]">Status</th>
                    <th class="px-5 py-3 w-[25%]">Actions</th>
                  </tr>
                </thead>
                <tbody class="js-room-body divide-y divide-surface-variant/30">
                  <?php foreach ($floorRows as $row): ?>
                    <?php
                      [$statusLabel, $statusClass] = $roomStatus($row);
                      $isActive = (int) $row['is_active'] === 1;
                      $viewUrl = BASE_URL . '/master/classroomView?id=' . (int) $row['classroom_id'];
                    ?>
                    <tr class="js-room hover:bg-surface-container-low/60 <?= $isActive ? '' : 'opacity-60' ?>"
                        data-name="<?= $e(strtolower($row['room_number'])) ?>"
                        data-search="<?= $e(strtolower($row['room_number'] . ' ' . $row['building'] . ' ' . $statusLabel)) ?>"
                        data-assets="<?= (int) $row['asset_count'] ?>"
                        data-attention="<?= $statusLabel === 'Need Attention' ? 1 : 0 ?>">
                      <td class="px-5 py-4">
                        <a href="<?= $viewUrl ?>" class="text-[13px] font-semibold text-[#1d4ed8] hover:underline">Room <?= $e($row['room_number']) ?></a>
                      </td>
                      <td class="px-5 py-4 text-[13px] font-medium text-on-surface"><?= (int) $row['asset_count'] ?> Assets</td>
                      <td class="px-5 py-4">
                        <span class="inline-flex px-2.5 py-1 rounded-full border text-[11px] font-semibold <?= $statusClass ?>"><?= $statusLabel ?></span>
                      </td>
                      <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                          <a href="<?= $viewUrl ?>" title="View details"
                             class="w-8 h-8 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-high flex items-center justify-center">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                          </a>
                          <form method="post" action="<?= BASE_URL ?>/master/classrooms"
                                data-confirm="<?= $isActive
                                    ? 'Deactivate room ' . $e($row['room_number']) . '? It will no longer appear when users report an issue. Old tickets stay unchanged.'
                                    : 'Reactivate room ' . $e($row['room_number']) . '?' ?>">
                            <input type="hidden" name="form_action" value="toggle">
                            <input type="hidden" name="record_id" value="<?= (int) $row['classroom_id'] ?>">
                            <?php if ($isActive): ?>
                              <button type="submit" title="Deactivate room"
                                      class="w-8 h-8 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                              </button>
                            <?php else: ?>
                              <button type="submit" title="Reactivate room"
                                      class="w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px]">restore_from_trash</span>
                              </button>
                            <?php endif; ?>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endforeach; ?>

        <div id="rooms-empty" class="hidden bg-surface-container-lowest rounded-2xl border border-surface-variant/30 px-6 py-12 text-center text-[14px] text-on-surface-variant">
          No rooms match your search.
        </div>
      </div>

      <!-- TAB 2: tất cả thiết bị -->
      <div id="tab-assets" class="hidden bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm overflow-hidden">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-surface-container-low text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
              <th class="px-5 py-3">Asset Code</th>
              <th class="px-5 py-3">Asset Name</th>
              <th class="px-5 py-3">Category</th>
              <th class="px-5 py-3">Room</th>
              <th class="px-5 py-3">Faults</th>
              <th class="px-5 py-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-variant/30">
            <?php foreach ($assets as $asset): ?>
              <?php [$aLabel, $aClass] = $assetBadge[$asset['status']] ?? [$asset['status'], 'bg-slate-100 text-slate-600 border-slate-200']; ?>
              <tr class="js-asset hover:bg-surface-container-low/60"
                  data-floor="<?= (int) $asset['floor'] ?>"
                  data-search="<?= $e(strtolower($asset['asset_code'] . ' ' . $asset['asset_name'] . ' ' . $asset['room_number'] . ' ' . ($asset['category_name'] ?? ''))) ?>">
                <td class="px-5 py-3 text-[13px] font-mono text-on-surface"><?= $e($asset['asset_code']) ?></td>
                <td class="px-5 py-3 text-[13px] font-medium text-on-surface"><?= $e($asset['asset_name']) ?></td>
                <td class="px-5 py-3 text-[13px] text-on-surface-variant"><?= $e($asset['category_name'] ?? '—') ?></td>
                <td class="px-5 py-3 text-[13px]">
                  <a href="<?= BASE_URL ?>/master/classroomView?id=<?= (int) $asset['classroom_id'] ?>" class="font-semibold text-[#1d4ed8] hover:underline">Room <?= $e($asset['room_number']) ?></a>
                </td>
                <td class="px-5 py-3 text-[13px] text-on-surface"><?= (int) $asset['fault_count'] ?></td>
                <td class="px-5 py-3"><span class="inline-flex px-2.5 py-1 rounded-full border text-[11px] font-semibold <?= $aClass ?>"><?= $e($aLabel) ?></span></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$assets): ?>
              <tr><td colspan="6" class="px-5 py-12 text-center text-[14px] text-on-surface-variant">No assets recorded yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

<script>
  (function () {
    const search = document.getElementById('room-search');
    const sort = document.getElementById('room-sort');
    const floor = document.getElementById('room-floor');
    const empty = document.getElementById('rooms-empty');
    const tabs = { rooms: document.getElementById('tab-rooms'), assets: document.getElementById('tab-assets') };
    let current = 'rooms';

    // Đổi tab Classroom / All Assets
    document.querySelectorAll('.js-tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        current = btn.dataset.tab;
        document.querySelectorAll('.js-tab').forEach(function (b) {
          const on = b === btn;
          b.classList.toggle('bg-primary', on);
          b.classList.toggle('text-white', on);
          b.classList.toggle('bg-surface-container-high', !on);
          b.classList.toggle('text-on-surface-variant', !on);
        });
        tabs.rooms.classList.toggle('hidden', current !== 'rooms');
        tabs.assets.classList.toggle('hidden', current !== 'assets');
        sort.classList.toggle('hidden', current !== 'rooms');
        apply();
      });
    });

    // Thu gọn / mở rộng từng tầng
    document.querySelectorAll('.js-floor-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const body = btn.parentElement.querySelector('.js-floor-body');
        const hidden = body.classList.toggle('hidden');
        btn.querySelector('.js-chevron').style.transform = hidden ? 'rotate(-90deg)' : '';
      });
    });

    // Xác nhận trước khi ngừng / kích hoạt lại phòng
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
      });
    });

    function sortRooms() {
      const mode = sort.value;
      document.querySelectorAll('.js-room-body').forEach(function (body) {
        const rows = Array.from(body.querySelectorAll('.js-room'));
        rows.sort(function (a, b) {
          if (mode === 'assets-desc') return Number(b.dataset.assets) - Number(a.dataset.assets);
          if (mode === 'status' && a.dataset.attention !== b.dataset.attention) return Number(b.dataset.attention) - Number(a.dataset.attention);
          const cmp = a.dataset.name.localeCompare(b.dataset.name, undefined, { numeric: true });
          return mode === 'name-desc' ? -cmp : cmp;
        });
        rows.forEach(function (row) { body.appendChild(row); });
      });
    }

    function apply() {
      const q = (search.value || '').trim().toLowerCase();
      const f = floor.value;
      if (current === 'rooms') {
        let visible = 0;
        document.querySelectorAll('.js-floor').forEach(function (section) {
          let inFloor = 0;
          const floorOk = f === 'all' || section.dataset.floor === f;
          section.querySelectorAll('.js-room').forEach(function (row) {
            const show = floorOk && (!q || row.dataset.search.indexOf(q) !== -1);
            row.classList.toggle('hidden', !show);
            if (show) inFloor++;
          });
          section.classList.toggle('hidden', inFloor === 0);
          visible += inFloor;
        });
        if (empty) empty.classList.toggle('hidden', visible !== 0 || document.querySelectorAll('.js-floor').length === 0);
      } else {
        document.querySelectorAll('.js-asset').forEach(function (row) {
          const show = (f === 'all' || row.dataset.floor === f) && (!q || row.dataset.search.indexOf(q) !== -1);
          row.classList.toggle('hidden', !show);
        });
      }
    }

    search.addEventListener('input', apply);
    floor.addEventListener('change', apply);
    sort.addEventListener('change', sortRooms);
    sortRooms();
  })();
</script>

<?php require_once APPROOT . '/app/Views/layouts/manager/footer.php'; ?>
