<?php
/**
 * View: Services & SLA Catalog (/master/services)
 * - 4 thẻ thống kê: phòng ban, danh mục sự cố, quy tắc định tuyến theo từ khóa, mức ưu tiên.
 * - Tab Ticket Categories: bảng ticket_categories (tìm kiếm, lọc ưu tiên, lọc SLA phản hồi < 30 phút).
 * - Tab Departments: bảng departments (mã, tên + mô tả, email liên hệ, số danh mục liên kết).
 * Thêm/sửa bằng hộp thoại; xóa chỉ thành công khi không còn dữ liệu nào tham chiếu.
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
$departments = $departments ?? [];
$page_title = $page_title ?? 'Services & SLA Catalog - Campus Helpdesk';
$breadcrumb_current = $breadcrumb_current ?? 'Services Management';
$current_nav = $current_nav ?? 'services';
$flash = $flash ?? null;
$form_error = $form_error ?? null;

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$base = BASE_URL . '/master/services';

// SLA: 10 -> "10m", 240 -> "4h", 2880 -> "2d"
$sla = static function ($minutes): string {
    $m = (int) $minutes;
    if ($m >= 1440 && $m % 1440 === 0) return ($m / 1440) . 'd';
    if ($m > 120 && $m % 60 === 0) return ($m / 60) . 'h';
    return $m . 'm';
};

$priorityBadge = [
    'Critical' => 'bg-red-100 text-red-700 border-red-200',
    'High' => 'bg-amber-100 text-amber-800 border-amber-200',
    'Medium' => 'bg-blue-100 text-blue-700 border-blue-200',
    'Low' => 'bg-slate-100 text-slate-600 border-slate-200',
];

// Biểu tượng cho từng phòng ban theo mã
$deptIcon = static function (?string $code): string {
    $code = strtoupper((string) $code);
    return match (true) {
        str_starts_with($code, 'IT') => 'lan',
        str_starts_with($code, 'FAC') => 'handyman',
        str_starts_with($code, 'ACA') => 'school',
        str_starts_with($code, 'SEC') => 'shield',
        default => 'domain',
    };
};

// ---- Số liệu thẻ
$deptTotal = count($departments);
$deptActive = count(array_filter($departments, static fn ($d) => (int) $d['is_active'] === 1));
$catTotal = count($rows);
$catActive = count(array_filter($rows, static fn ($r) => (int) $r['is_active'] === 1));
$routingRules = count(array_filter($rows, static fn ($r) => (int) $r['is_active'] === 1 && trim((string) ($r['routing_keywords'] ?? '')) !== ''));
$deptPercent = $deptTotal > 0 ? (int) round($deptActive * 100 / $deptTotal) : 0;

// Mở lại hộp thoại đúng tab khi máy chủ báo lỗi
$errorTab = (is_array($form_error) && str_starts_with((string) ($form_error['action'] ?? ''), 'dept_')) ? 'departments' : 'categories';

require_once APPROOT . '/app/Views/layouts/manager/header.php';
require_once APPROOT . '/app/Views/layouts/manager/sidebar.php';
?>

<div class="pl-64">
  <main class="relative pt-24 pb-12 px-8 w-full min-h-screen bg-background">
    <div class="max-w-[1400px] mx-auto flex flex-col w-full gap-6">

      <div>
        <h1 class="text-[28px] font-bold text-on-surface tracking-tight">Services &amp; SLA Catalog</h1>
        <p class="mt-1 text-[14px] text-on-surface-variant">Manage receiving departments, configure automatic incident routing categories and set SLA resolution targets.</p>
      </div>

      <?php if (is_array($flash) && !empty($flash['message'])): ?>
        <?php $flashOk = ($flash['type'] ?? '') === 'success'; ?>
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl border <?= $flashOk ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-error-container border-error/20 text-on-error-container' ?>" role="status">
          <span class="material-symbols-outlined text-[20px]"><?= $flashOk ? 'check_circle' : 'error' ?></span>
          <p class="text-[14px] leading-6"><?= $e($flash['message']) ?></p>
        </div>
      <?php endif; ?>

      <!-- 4 thẻ thống kê -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <?php
          $cards = [
              ['Total Departments', $deptTotal, 'apartment',
               '<span class="px-2.5 py-0.5 rounded-full bg-primary-fixed text-primary font-medium">' . $deptPercent . '% Active</span><span class="text-on-surface-variant">• ' . $deptActive . ' receiving</span>'],
              ['Incident Categories', $catTotal, 'category',
               '<span class="px-2.5 py-0.5 rounded-full bg-primary-fixed text-primary font-medium">' . $catActive . ' Active</span><span class="text-on-surface-variant">' . ($catTotal - $catActive) . ' Inactive</span>'],
              ['Active Routing Rules', $routingRules, 'alt_route',
               '<span class="px-2.5 py-0.5 rounded-full bg-primary-fixed text-primary font-medium">Keyword-based auto routing</span>'],
              ['Priority Levels', 4, 'priority_high',
               '<span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700">Critical</span><span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">High</span><span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Medium</span><span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Low</span>'],
          ];
        ?>
        <?php foreach ($cards as [$label, $value, $icon, $note]): ?>
          <div class="bg-surface-container-lowest rounded-2xl p-5 border border-surface-variant/30 border-b-4 border-b-primary shadow-sm">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant"><?= $label ?></p>
                <p class="text-[28px] font-bold text-on-surface leading-tight mt-1"><?= (int) $value ?></p>
              </div>
              <span class="w-10 h-10 rounded-lg bg-primary-fixed text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]"><?= $icon ?></span>
              </span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px]"><?= $note ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Tabs -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex p-1 rounded-xl bg-surface-container-low gap-1">
          <button type="button" data-tab="categories" class="js-tab inline-flex items-center gap-2 px-4 h-10 rounded-lg text-[14px] font-semibold">
            <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>Ticket Categories (<?= $catTotal ?>)
          </button>
          <button type="button" data-tab="departments" class="js-tab inline-flex items-center gap-2 px-4 h-10 rounded-lg text-[14px] font-semibold">
            <span class="material-symbols-outlined text-[20px]">apartment</span>Departments (<?= $deptTotal ?>)
          </button>
        </div>
      </div>

      <!-- ===== Tab: Ticket Categories ===== -->
      <section id="tab-categories" class="flex flex-col gap-4">
        <div class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm p-4 flex flex-col gap-3">
          <div class="flex flex-col md:flex-row md:items-center gap-3 justify-between">
            <div class="relative w-full md:max-w-[420px]">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
              <input id="cat-search" type="search" placeholder="Search category, code, routing keywords..."
                     class="w-full h-10 pl-10 pr-3 bg-surface-container-low rounded-lg text-[14px] border border-transparent focus:border-primary focus:bg-white outline-none">
            </div>
            <label class="inline-flex items-center gap-2 text-[13px] text-on-surface-variant cursor-pointer">
              <input id="cat-fast" type="checkbox" class="w-4 h-4 rounded border-outline-variant">
              Only response SLA &lt; 30m
            </label>
          </div>
          <div class="inline-flex self-start p-1 rounded-lg bg-surface-container-low gap-1 text-[13px]">
            <?php foreach (['all' => 'All', 'Critical' => 'Critical', 'High' => 'High Priority', 'Medium' => 'Medium', 'Low' => 'Low'] as $val => $lbl): ?>
              <button type="button" data-priority="<?= $val ?>" class="js-priority px-3 h-8 rounded-md"><?= $lbl ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-surface-container-low text-[12px] font-semibold text-on-surface-variant">
                <th class="px-5 py-3 text-center">Code</th>
                <th class="px-5 py-3">Category &amp; Scope</th>
                <th class="px-5 py-3 text-center">Responsible<br>Department</th>
                <th class="px-5 py-3 text-center">Priority</th>
                <th class="px-5 py-3 text-center">Response<br>SLA</th>
                <th class="px-5 py-3 text-center">Resolution<br>SLA</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="cat-body" class="divide-y divide-surface-variant/30">
              <?php foreach ($rows as $row): ?>
                <?php
                  $active = (int) $row['is_active'] === 1;
                  $record = [
                      'record_id' => (string) $row['category_id'],
                      'department_id' => (string) $row['department_id'],
                      'category_code' => $row['category_code'],
                      'category_name' => $row['category_name'],
                      'description' => (string) ($row['description'] ?? ''),
                      'routing_keywords' => (string) ($row['routing_keywords'] ?? ''),
                      'default_priority' => $row['default_priority'],
                      'sla_response_minutes' => (string) $row['sla_response_minutes'],
                      'sla_resolution_minutes' => (string) $row['sla_resolution_minutes'],
                  ];
                ?>
                <tr class="hover:bg-surface-container-low/60 <?= $active ? '' : 'opacity-60' ?>"
                    data-priority="<?= $e($row['default_priority']) ?>" data-response="<?= (int) $row['sla_response_minutes'] ?>"
                    data-search="<?= $e(mb_strtolower($row['category_code'] . ' ' . $row['category_name'] . ' ' . ($row['description'] ?? '') . ' ' . ($row['routing_keywords'] ?? '') . ' ' . $row['department_name'])) ?>">
                  <td class="px-5 py-4 text-center font-mono text-[12px] font-semibold text-[#1d4ed8]"><?= $e($row['category_code']) ?></td>
                  <td class="px-5 py-4 max-w-[220px]">
                    <p class="text-[14px] font-medium text-on-surface"><?= $e($row['category_name']) ?></p>
                    <?php if (!empty($row['description'])): ?>
                      <p class="text-[12px] text-on-surface-variant truncate" title="<?= $e($row['description']) ?>"><?= $e($row['description']) ?></p>
                    <?php endif; ?>
                  </td>
                  <td class="px-5 py-4 text-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed text-primary text-[12px] font-medium">
                      <span class="material-symbols-outlined text-[16px]"><?= $deptIcon($row['department_code']) ?></span><?= $e($row['department_name']) ?>
                    </span>
                  </td>
                  <td class="px-5 py-4 text-center">
                    <span class="inline-flex px-3 py-0.5 rounded-full border text-[11px] font-semibold <?= $priorityBadge[$row['default_priority']] ?? $priorityBadge['Low'] ?>"><?= $e($row['default_priority']) ?></span>
                  </td>
                  <td class="px-5 py-4 text-center font-mono text-[13px]"><?= $sla($row['sla_response_minutes']) ?></td>
                  <td class="px-5 py-4 text-center font-mono text-[13px]"><?= $sla($row['sla_resolution_minutes']) ?></td>
                  <td class="px-5 py-4 text-center">
                    <form method="post" action="<?= $base ?>" class="js-confirm inline" data-confirm="<?= $e(($active ? 'Turn off ' : 'Turn on ') . $row['category_name'] . '?') ?>">
                      <input type="hidden" name="form_action" value="toggle">
                      <input type="hidden" name="record_id" value="<?= (int) $row['category_id'] ?>">
                      <button type="submit" title="<?= $active ? 'Active' : 'Inactive' ?>" class="relative inline-flex w-11 h-6 rounded-full transition-colors <?= $active ? 'bg-[#1d4ed8]' : 'bg-slate-300' ?>">
                        <span class="absolute top-0.5 <?= $active ? 'left-[22px]' : 'left-0.5' ?> w-5 h-5 rounded-full bg-white shadow"></span>
                      </button>
                    </form>
                  </td>
                  <td class="px-5 py-4">
                    <div class="flex items-center justify-center gap-3">
                      <button type="button" class="js-edit-cat text-[#1d4ed8] hover:text-primary" title="Edit" data-record="<?= $e(json_encode($record, $jsonFlags)) ?>">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                      </button>
                      <form method="post" action="<?= $base ?>" class="js-confirm inline" data-confirm="<?= $e('Delete category ' . $row['category_name'] . '? This cannot be undone.') ?>">
                        <input type="hidden" name="form_action" value="delete">
                        <input type="hidden" name="record_id" value="<?= (int) $row['category_id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800" title="Delete"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr id="cat-empty" class="<?= $rows ? 'hidden' : '' ?>"><td colspan="8" class="px-5 py-12 text-center text-[14px] text-on-surface-variant">No categories match your filters.</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ===== Tab: Departments ===== -->
      <section id="tab-departments" class="hidden flex-col gap-4">
        <div class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm p-4">
          <div class="relative w-full md:max-w-[420px]">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
            <input id="dept-search" type="search" placeholder="Search department, code, contact..."
                   class="w-full h-10 pl-10 pr-3 bg-surface-container-low rounded-lg text-[14px] border border-transparent focus:border-primary focus:bg-white outline-none">
          </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-surface-container-low text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
                <th class="px-5 py-3">Dept Code</th>
                <th class="px-5 py-3">Department Name &amp; Scope</th>
                <th class="px-5 py-3">Contact Information</th>
                <th class="px-5 py-3 text-center">Linked Categories</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="dept-body" class="divide-y divide-surface-variant/30">
              <?php foreach ($departments as $dept): ?>
                <?php
                  $active = (int) $dept['is_active'] === 1;
                  $linked = (int) ($dept['category_count'] ?? 0);
                  $record = [
                      'record_id' => (string) $dept['department_id'],
                      'department_code' => $dept['department_code'],
                      'department_name' => $dept['department_name'],
                      'description' => (string) ($dept['description'] ?? ''),
                      'contact_email' => (string) ($dept['contact_email'] ?? ''),
                      'contact_phone' => (string) ($dept['contact_phone'] ?? ''),
                      'is_active' => (string) (int) $dept['is_active'],
                  ];
                ?>
                <tr class="hover:bg-surface-container-low/60"
                    data-search="<?= $e(mb_strtolower($dept['department_code'] . ' ' . $dept['department_name'] . ' ' . ($dept['description'] ?? '') . ' ' . ($dept['contact_email'] ?? '') . ' ' . ($dept['contact_phone'] ?? ''))) ?>">
                  <td class="px-5 py-4 text-[13px] font-bold text-[#1d4ed8]"><?= $e($dept['department_code']) ?></td>
                  <td class="px-5 py-4 max-w-[300px]">
                    <p class="text-[13px] font-semibold text-on-surface"><?= $e($dept['department_name']) ?></p>
                    <?php if (!empty($dept['description'])): ?>
                      <p class="text-[12px] text-on-surface-variant truncate" title="<?= $e($dept['description']) ?>"><?= $e($dept['description']) ?></p>
                    <?php endif; ?>
                  </td>
                  <td class="px-5 py-4">
                    <p class="text-[13px] font-semibold text-on-surface"><?= $e($dept['contact_email'] ?: '—') ?></p>
                    <?php if (!empty($dept['contact_phone'])): ?><p class="text-[12px] text-on-surface-variant"><?= $e($dept['contact_phone']) ?></p><?php endif; ?>
                  </td>
                  <td class="px-5 py-4 text-center">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[12px] font-medium <?= $linked > 0 ? 'bg-primary-fixed text-primary' : 'bg-slate-100 text-slate-400' ?>">
                      ● <?= $linked ?> <?= $linked === 1 ? 'Category' : 'Categories' ?>
                    </span>
                  </td>
                  <td class="px-5 py-4 text-center">
                    <form method="post" action="<?= $base ?>" class="js-confirm inline" data-confirm="<?= $e(($active ? 'Turn off ' : 'Turn on ') . $dept['department_name'] . '?') ?>">
                      <input type="hidden" name="form_action" value="dept_toggle">
                      <input type="hidden" name="record_id" value="<?= (int) $dept['department_id'] ?>">
                      <button type="submit" title="<?= $active ? 'Active' : 'Inactive' ?>" class="relative inline-flex w-11 h-6 rounded-full transition-colors <?= $active ? 'bg-[#1d4ed8]' : 'bg-slate-300' ?>">
                        <span class="absolute top-0.5 <?= $active ? 'left-[22px]' : 'left-0.5' ?> w-5 h-5 rounded-full bg-white shadow"></span>
                      </button>
                    </form>
                  </td>
                  <td class="px-5 py-4">
                    <div class="flex items-center justify-center gap-3">
                      <button type="button" class="js-edit-dept text-[#1d4ed8] hover:text-primary" title="Edit" data-record="<?= $e(json_encode($record, $jsonFlags)) ?>">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                      </button>
                      <form method="post" action="<?= $base ?>" class="js-confirm inline" data-confirm="<?= $e('Delete department ' . $dept['department_name'] . '? This cannot be undone.') ?>">
                        <input type="hidden" name="form_action" value="dept_delete">
                        <input type="hidden" name="record_id" value="<?= (int) $dept['department_id'] ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800" title="Delete"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr id="dept-empty" class="<?= $departments ? 'hidden' : '' ?>"><td colspan="6" class="px-5 py-12 text-center text-[14px] text-on-surface-variant">No departments match your search.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
</div>

<?php
$errorList = (is_array($form_error) && !empty($form_error['errors']))
    ? '<ul class="js-server-errors px-4 py-3 rounded-lg bg-error-container text-on-error-container text-[13px] list-disc list-inside">'
      . implode('', array_map(static fn ($m) => '<li>' . htmlspecialchars((string) $m, ENT_QUOTES, 'UTF-8') . '</li>', $form_error['errors'])) . '</ul>'
    : '';
$input = 'h-10 px-3 rounded-lg border border-outline-variant text-[14px] bg-white focus:border-primary outline-none';
$lbl = 'text-[11px] font-semibold uppercase tracking-wider text-on-surface';
?>

<!-- Hộp thoại danh mục -->
<div id="cat-modal" class="js-modal fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="absolute inset-0 bg-black/40" data-close-modal></div>
  <form method="post" action="<?= $base ?>" class="relative w-full max-w-[560px] max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-xl px-8 py-7 flex flex-col gap-4">
    <input type="hidden" name="form_action" value="create">
    <input type="hidden" name="record_id" value="">
    <div>
      <h2 class="js-title text-[22px] font-bold text-on-surface">Add Ticket Category</h2>
      <p class="text-[13px] text-on-surface-variant">Routing keywords send matching incidents to the responsible department automatically.</p>
    </div>
    <?= $errorTab === 'categories' ? $errorList : '' ?>
    <div class="grid grid-cols-2 gap-4">
      <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Code *</span>
        <input name="category_code" required maxlength="30" placeholder="IT_NETWORK" class="<?= $input ?> font-mono uppercase"></label>
      <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Department *</span>
        <select name="department_id" required class="<?= $input ?>">
          <option value="">Choose department</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= (int) $dept['department_id'] ?>"><?= $e($dept['department_name']) ?><?= (int) $dept['is_active'] === 1 ? '' : ' (inactive)' ?></option>
          <?php endforeach; ?>
        </select></label>
    </div>
    <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Category Name *</span>
      <input name="category_name" required maxlength="100" placeholder="LAN, Wi-Fi & Internet" class="<?= $input ?>"></label>
    <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Scope / Description</span>
      <input name="description" maxlength="255" placeholder="Classroom Wi-Fi disconnected..." class="<?= $input ?>"></label>
    <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Routing Keywords</span>
      <textarea name="routing_keywords" maxlength="500" rows="2" placeholder="wifi, internet, lan, network" class="px-3 py-2 rounded-lg border border-outline-variant text-[14px] focus:border-primary outline-none"></textarea></label>
    <div class="grid grid-cols-3 gap-4">
      <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Priority *</span>
        <select name="default_priority" class="<?= $input ?>">
          <option value="Critical">Critical</option><option value="High">High</option>
          <option value="Medium" selected>Medium</option><option value="Low">Low</option>
        </select></label>
      <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Response (min) *</span>
        <input name="sla_response_minutes" type="number" min="1" required class="<?= $input ?>"></label>
      <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Resolution (min) *</span>
        <input name="sla_resolution_minutes" type="number" min="1" required class="<?= $input ?>"></label>
    </div>
    <p class="js-client-error hidden text-[13px] text-red-600"></p>
    <div class="grid grid-cols-2 gap-4 pt-2">
      <button type="button" data-close-modal class="h-10 text-[14px] font-medium text-on-surface hover:text-primary">Cancel</button>
      <button type="submit" class="js-submit h-10 rounded-lg bg-primary text-white text-[14px] font-medium hover:bg-[#00225c]">Create Category</button>
    </div>
  </form>
</div>

<!-- Hộp thoại phòng ban -->
<div id="dept-modal" class="js-modal fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="absolute inset-0 bg-black/40" data-close-modal></div>
  <form method="post" action="<?= $base ?>" class="relative w-full max-w-[500px] bg-white rounded-2xl shadow-xl px-8 py-7 flex flex-col gap-4">
    <input type="hidden" name="form_action" value="dept_create">
    <input type="hidden" name="record_id" value="">
    <div>
      <h2 class="js-title text-[22px] font-bold text-on-surface">Add Department</h2>
      <p class="text-[13px] text-on-surface-variant">A receiving unit that handles routed incidents.</p>
    </div>
    <?= $errorTab === 'departments' ? $errorList : '' ?>
    <div class="grid grid-cols-3 gap-4">
      <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Code *</span>
        <input name="department_code" required maxlength="20" placeholder="IT" class="<?= $input ?> uppercase"></label>
      <label class="col-span-2 flex flex-col gap-1.5"><span class="<?= $lbl ?>">Department Name *</span>
        <input name="department_name" required maxlength="100" placeholder="IT Center" class="<?= $input ?>"></label>
    </div>
    <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Scope / Description</span>
      <input name="description" maxlength="255" placeholder="Technical infrastructure, software" class="<?= $input ?>"></label>
    <div class="grid grid-cols-2 gap-4">
      <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Contact Email</span>
        <input name="contact_email" type="email" maxlength="100" placeholder="it-support@campus.edu" class="<?= $input ?>"></label>
      <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Contact Phone</span>
        <input name="contact_phone" maxlength="20" placeholder="024..." class="<?= $input ?>"></label>
    </div>
    <label class="flex flex-col gap-1.5"><span class="<?= $lbl ?>">Status *</span>
      <select name="is_active" class="<?= $input ?>"><option value="1">Active</option><option value="0">Inactive</option></select></label>
    <p class="js-client-error hidden text-[13px] text-red-600"></p>
    <div class="grid grid-cols-2 gap-4 pt-2">
      <button type="button" data-close-modal class="h-10 text-[14px] font-medium text-on-surface hover:text-primary">Cancel</button>
      <button type="submit" class="js-submit h-10 rounded-lg bg-primary text-white text-[14px] font-medium hover:bg-[#00225c]">Create Department</button>
    </div>
  </form>
</div>

<script>
  (function () {
    const reopen = <?= json_encode($form_error, $jsonFlags) ?>;
    const errorTab = <?= json_encode($errorTab) ?>;
    const TAB_KEY = 'services_tab';
    let currentTab = 'categories';

    // ---- Tabs (nhớ tab đang mở qua các lần tải lại trang)
    function showTab(name) {
      currentTab = name;
      document.querySelectorAll('.js-tab').forEach(function (b) {
        const on = b.dataset.tab === name;
        b.classList.toggle('bg-primary', on); b.classList.toggle('text-white', on);
        b.classList.toggle('text-on-surface-variant', !on);
      });
      ['categories', 'departments'].forEach(function (t) {
        const el = document.getElementById('tab-' + t);
        el.classList.toggle('hidden', t !== name); el.classList.toggle('flex', t === name);
      });
      try { sessionStorage.setItem(TAB_KEY, name); } catch (e) {}
    }
    document.querySelectorAll('.js-tab').forEach(function (b) { b.addEventListener('click', function () { showTab(b.dataset.tab); }); });
    let startTab = 'categories';
    try { startTab = sessionStorage.getItem(TAB_KEY) || 'categories'; } catch (e) {}
    if (location.hash === '#departments') startTab = 'departments';
    if (reopen && reopen.values) startTab = errorTab;
    showTab(startTab);

    // ---- Modals
    function openModal(id, mode, values) {
      const modal = document.getElementById(id);
      const form = modal.querySelector('form');
      const isDept = id === 'dept-modal';
      const edit = mode === 'update';
      form.reset();
      Object.keys(values || {}).forEach(function (k) { if (form.elements[k]) form.elements[k].value = values[k]; });
      if (!edit) form.elements.record_id.value = '';
      form.elements.form_action.value = (isDept ? 'dept_' : '') + (edit ? 'update' : 'create');
      modal.querySelector('.js-title').textContent = (edit ? 'Edit ' : 'Add ') + (isDept ? 'Department' : 'Ticket Category');
      modal.querySelector('.js-submit').textContent = edit ? 'Save Changes' : (isDept ? 'Create Department' : 'Create Category');
      modal.querySelector('.js-client-error').classList.add('hidden');
      modal.classList.remove('hidden'); modal.classList.add('flex');
      const first = form.querySelector('input:not([type=hidden])'); if (first) first.focus();
    }
    function closeModals() {
      document.querySelectorAll('.js-modal').forEach(function (m) { m.classList.add('hidden'); m.classList.remove('flex'); });
      document.querySelectorAll('.js-server-errors').forEach(function (el) { el.remove(); });
    }
    document.querySelectorAll('[data-close-modal]').forEach(function (el) { el.addEventListener('click', closeModals); });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') closeModals(); });

    document.querySelectorAll('.js-edit-cat').forEach(function (b) {
      b.addEventListener('click', function () { openModal('cat-modal', 'update', JSON.parse(b.dataset.record)); });
    });
    document.querySelectorAll('.js-edit-dept').forEach(function (b) {
      b.addEventListener('click', function () { openModal('dept-modal', 'update', JSON.parse(b.dataset.record)); });
    });

    // Kiểm tra nhanh phía trình duyệt (máy chủ vẫn kiểm tra lại)
    document.querySelector('#cat-modal form').addEventListener('submit', function (ev) {
      const f = ev.target, p = [];
      if (!/^[A-Za-z0-9_-]{2,30}$/.test(f.elements.category_code.value.trim())) p.push('Code must be 2–30 letters, numbers, - or _.');
      if (!f.elements.department_id.value) p.push('Choose a department.');
      if (!f.elements.category_name.value.trim()) p.push('Enter the category name.');
      const r = parseInt(f.elements.sla_response_minutes.value, 10), s = parseInt(f.elements.sla_resolution_minutes.value, 10);
      if (!(r > 0) || !(s > 0)) p.push('SLA minutes must be greater than 0.');
      else if (s < r) p.push('Resolution SLA must be at least the response SLA.');
      if (p.length) { ev.preventDefault(); const el = f.querySelector('.js-client-error'); el.textContent = p.join(' '); el.classList.remove('hidden'); }
    });
    document.querySelector('#dept-modal form').addEventListener('submit', function (ev) {
      const f = ev.target, p = [];
      if (!/^[A-Za-z0-9_-]{2,20}$/.test(f.elements.department_code.value.trim())) p.push('Code must be 2–20 letters, numbers, - or _.');
      if (!f.elements.department_name.value.trim()) p.push('Enter the department name.');
      const mail = f.elements.contact_email.value.trim();
      if (mail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail)) p.push('Enter a valid contact email.');
      if (p.length) { ev.preventDefault(); const el = f.querySelector('.js-client-error'); el.textContent = p.join(' '); el.classList.remove('hidden'); }
    });

    document.querySelectorAll('form.js-confirm').forEach(function (f) {
      f.addEventListener('submit', function (ev) { if (!window.confirm(f.dataset.confirm)) ev.preventDefault(); });
    });

    // ---- Lọc danh mục
    let priority = 'all';
    const catSearch = document.getElementById('cat-search');
    const fast = document.getElementById('cat-fast');
    function paintPriority() {
      document.querySelectorAll('.js-priority').forEach(function (b) {
        const on = b.dataset.priority === priority;
        b.classList.toggle('bg-white', on); b.classList.toggle('shadow-sm', on); b.classList.toggle('font-semibold', on);
        b.classList.toggle('text-on-surface-variant', !on);
      });
    }
    function filterCats() {
      const q = catSearch.value.trim().toLowerCase(); let n = 0;
      document.querySelectorAll('#cat-body tr[data-search]').forEach(function (row) {
        const ok = (!q || row.dataset.search.indexOf(q) !== -1)
          && (priority === 'all' || row.dataset.priority === priority)
          && (!fast.checked || parseInt(row.dataset.response, 10) < 30);
        row.classList.toggle('hidden', !ok); if (ok) n++;
      });
      document.getElementById('cat-empty').classList.toggle('hidden', n !== 0);
    }
    document.querySelectorAll('.js-priority').forEach(function (b) {
      b.addEventListener('click', function () { priority = b.dataset.priority; paintPriority(); filterCats(); });
    });
    catSearch.addEventListener('input', filterCats);
    fast.addEventListener('change', filterCats);
    paintPriority();

    // ---- Lọc phòng ban
    const deptSearch = document.getElementById('dept-search');
    deptSearch.addEventListener('input', function () {
      const q = deptSearch.value.trim().toLowerCase(); let n = 0;
      document.querySelectorAll('#dept-body tr[data-search]').forEach(function (row) {
        const ok = !q || row.dataset.search.indexOf(q) !== -1;
        row.classList.toggle('hidden', !ok); if (ok) n++;
      });
      document.getElementById('dept-empty').classList.toggle('hidden', n !== 0);
    });

    // ---- Mở lại hộp thoại khi máy chủ báo lỗi
    if (reopen && reopen.values) {
      const isDept = errorTab === 'departments';
      openModal(isDept ? 'dept-modal' : 'cat-modal', String(reopen.action).indexOf('update') !== -1 ? 'update' : 'create', reopen.values);
    }
  })();
</script>

<?php require_once APPROOT . '/app/Views/layouts/manager/footer.php'; ?>
