<?php
/**
 * View: User Accounts & Access Control (/master/users)
 * - 4 thẻ thống kê: tổng tài khoản, kỹ thuật viên, quản lý, người dùng (giảng viên + sinh viên).
 * - Bảng tài khoản: tìm kiếm, lọc vai trò, lọc trạng thái; sửa bằng nút bút chì, bật/tắt bằng công tắc.
 * - Hộp thoại Add New User / Edit User: Full name, Email, Password, Role, Status
 *   (Department chỉ hiện khi chọn Technician vì CSDL bắt buộc).
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

$page_title = $page_title ?? 'User Management - Campus Helpdesk';
$breadcrumb_current = $breadcrumb_current ?? 'User Management';

$rows = $rows ?? [];
$departments = $departments ?? [];
$current_user_id = (int) ($current_user_id ?? 0);
$flash = $flash ?? null;
$form_error = $form_error ?? null;
$current_nav = $current_nav ?? 'users';

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

// ---- Số liệu cho 4 thẻ
$count = ['total' => 0, 'active' => 0, 'Manager' => 0, 'Manager_active' => 0, 'Technician' => 0,
          'Technician_active' => 0, 'Lecturer' => 0, 'Student' => 0];
foreach ($rows as $row) {
    $count['total']++;
    $count[$row['role']] = ($count[$row['role']] ?? 0) + 1;
    if ((int) $row['is_active'] === 1) {
        $count['active']++;
        $count[$row['role'] . '_active'] = ($count[$row['role'] . '_active'] ?? 0) + 1;
    }
}
$userCount = $count['Lecturer'] + $count['Student'];

// ---- Chữ viết tắt cho avatar: "Nguyễn Văn A" -> "NA"
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/u', trim(preg_replace('/^(dr|ths|ts|pgs|gs|mr|ms|mrs)\.?\s+/iu', '', $name))) ?: [];
    $first = $parts[0] ?? '';
    $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';
    return mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
};

$roleBadge = [
    'Manager' => 'bg-primary text-white border-primary',
    'Technician' => 'bg-primary-fixed text-primary border-primary-fixed',
    'Lecturer' => 'bg-surface-container-high text-on-surface-variant border-surface-container-high',
    'Student' => 'bg-surface-container-high text-on-surface-variant border-surface-container-high',
];

require_once APPROOT . '/app/Views/layouts/manager/header.php';
require_once APPROOT . '/app/Views/layouts/manager/sidebar.php';
?>

<div class="pl-64">
  <main class="relative pt-24 pb-12 px-8 w-full min-h-screen bg-background">
    <div class="max-w-[1400px] mx-auto flex flex-col w-full gap-6">

      <!-- Tiêu đề -->
      <div>
        <div class="flex flex-wrap items-center gap-3">
          <h1 class="text-[28px] font-bold text-on-surface tracking-tight">User Accounts &amp; Access Control</h1>
          <span class="px-2.5 py-1 rounded-md bg-primary-fixed text-primary text-[11px] font-bold tracking-wider">FR-M05 ENFORCED</span>
        </div>
        <p class="mt-1 text-[14px] text-on-surface-variant max-w-2xl">
          Manage staff, technician and student accounts; assign system permissions and monitor incident workloads.
        </p>
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
              ['Total Accounts', number_format($count['total']), '', 'group', '<span class="text-primary">●</span> ' . number_format($count['active']) . ' Active'],
              ['Technicians', number_format($count['Technician_active']), ' / ' . number_format($count['Technician']) . ' staff', 'engineering', '<span class="text-primary font-medium">● ' . number_format($count['Technician_active']) . ' techs active</span>'],
              ['Managers', number_format($count['Manager']), '', 'admin_panel_settings', '<span class="text-primary font-medium flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">verified_user</span>' . number_format($count['Manager_active']) . ' active manager accounts</span>'],
              ['Users', number_format($userCount), '', 'school', number_format($count['Lecturer']) . ' Faculty <span class="mx-2">·</span> ' . number_format($count['Student']) . ' Students'],
          ];
        ?>
        <?php foreach ($cards as [$label, $value, $suffix, $icon, $note]): ?>
          <div class="relative overflow-hidden bg-surface-container-lowest rounded-2xl p-5 border border-surface-variant/30 shadow-sm">
            <div class="absolute -bottom-10 -right-6 w-28 h-28 rounded-full bg-surface-container-high/70"></div>
            <div class="relative flex items-start justify-between">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant"><?= $label ?></p>
              <span class="w-9 h-9 rounded-lg bg-primary-fixed text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px]"><?= $icon ?></span>
              </span>
            </div>
            <p class="relative -mt-2"><span class="text-[28px] font-bold text-on-surface"><?= $value ?></span><span class="text-[13px] text-on-surface-variant"><?= $suffix ?></span></p>
            <p class="relative mt-2 text-[12px] text-on-surface-variant flex items-center"><?= $note ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Bảng tài khoản -->
      <section class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-4">
          <div class="relative flex-1 lg:max-w-[360px]">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
            <input id="user-search" type="search" placeholder="Search ..."
                   class="w-full h-10 pl-10 pr-3 bg-surface-container-low rounded-lg text-[14px] text-on-surface placeholder:text-on-surface-variant/70 border border-transparent focus:border-primary focus:bg-white outline-none">
          </div>
          <div class="flex gap-2 lg:ml-2">
            <select id="user-role" class="h-10 px-3 pr-8 bg-surface-container-low rounded-lg text-[14px] text-on-surface border border-transparent focus:border-primary outline-none">
              <option value="all">All Roles</option>
              <option value="Manager">Manager</option>
              <option value="Technician">Technician</option>
              <option value="Lecturer">Lecturer</option>
              <option value="Student">Student</option>
            </select>
            <select id="user-status" class="h-10 px-3 pr-8 bg-surface-container-low rounded-lg text-[14px] text-on-surface border border-transparent focus:border-primary outline-none">
              <option value="all">All Status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
          <button type="button" class="js-open-create lg:ml-auto inline-flex items-center justify-center gap-2 px-5 h-10 bg-primary text-white rounded-lg shadow-sm hover:bg-[#00225c] text-[14px] font-medium">
            <span class="material-symbols-outlined text-[20px]">person_add</span>
            Add New User
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-surface-container-low text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
                <th class="px-5 py-3 w-12">#</th>
                <th class="px-5 py-3">User Identity</th>
                <th class="px-5 py-3 text-center">Role</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-center">Created Day</th>
                <th class="px-5 py-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="user-body" class="divide-y divide-surface-variant/30">
              <?php foreach ($rows as $i => $row): ?>
                <?php
                  $isActive = (int) $row['is_active'] === 1;
                  $isSelf = (int) $row['user_id'] === $current_user_id;
                  $record = [
                      'record_id' => (string) $row['user_id'],
                      'full_name' => $row['full_name'],
                      'email' => $row['email'],
                      'role' => $row['role'],
                      'department_id' => $row['department_id'] === null ? '' : (string) $row['department_id'],
                      'is_active' => (string) (int) $row['is_active'],
                      'user_code' => (string) ($row['user_code'] ?? ''),
                      'phone' => (string) ($row['phone'] ?? ''),
                      'is_self' => $isSelf,
                  ];
                ?>
                <tr class="hover:bg-surface-container-low/60"
                    data-role="<?= $e($row['role']) ?>" data-status="<?= $isActive ? 1 : 0 ?>"
                    data-search="<?= $e(mb_strtolower($row['full_name'] . ' ' . $row['email'] . ' ' . ($row['user_code'] ?? '') . ' ' . $row['role'])) ?>">
                  <td class="px-5 py-3 text-[13px] font-semibold text-[#1d4ed8] js-index"><?= $i + 1 ?></td>
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                      <span class="w-9 h-9 rounded-full bg-primary-fixed text-primary text-[12px] font-bold flex items-center justify-center shrink-0"><?= $e($initials($row['full_name'])) ?></span>
                      <div class="min-w-0">
                        <p class="text-[13px] font-semibold text-on-surface truncate">
                          <?= $e($row['full_name']) ?>
                          <?php if ($isSelf): ?><span class="ml-1 text-[11px] font-medium text-on-surface-variant">(you)</span><?php endif; ?>
                        </p>
                        <p class="text-[12px] text-on-surface-variant truncate"><?= $e($row['email']) ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-center">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border text-[11px] font-medium <?= $roleBadge[$row['role']] ?? $roleBadge['Student'] ?>">● <?= $e($row['role']) ?></span>
                  </td>
                  <td class="px-5 py-3 text-center">
                    <?php if ($isActive): ?>
                      <span class="inline-flex px-3 py-0.5 rounded-full border text-[11px] font-semibold bg-emerald-50 text-emerald-700 border-emerald-200">Active</span>
                    <?php else: ?>
                      <span class="inline-flex px-3 py-0.5 rounded-full border text-[11px] font-semibold bg-slate-100 text-slate-500 border-slate-300">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-5 py-3 text-center text-[13px] font-semibold text-on-surface">
                    <?= $row['created_at'] ? $e(date('M d, Y', strtotime($row['created_at']))) : '—' ?>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex items-center justify-center gap-3">
                      <button type="button" class="js-open-edit text-on-surface hover:text-primary" title="Edit account"
                              data-record="<?= $e(json_encode($record, $jsonFlags)) ?>">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                      </button>
                      <form method="post" action="<?= BASE_URL ?>/master/users" class="js-toggle"
                            data-confirm="<?= $e(($isActive ? 'Deactivate ' : 'Activate ') . $row['full_name'] . '?') ?>">
                        <input type="hidden" name="form_action" value="toggle">
                        <input type="hidden" name="record_id" value="<?= (int) $row['user_id'] ?>">
                        <button type="submit" <?= $isSelf ? 'disabled title="You cannot deactivate your own account"' : 'title="' . ($isActive ? 'Deactivate' : 'Activate') . '"' ?>
                                class="relative inline-flex w-11 h-6 rounded-full transition-colors <?= $isActive ? 'bg-[#1d4ed8]' : 'bg-slate-300' ?> <?= $isSelf ? 'opacity-50 cursor-not-allowed' : '' ?>">
                          <span class="absolute top-0.5 <?= $isActive ? 'left-[22px]' : 'left-0.5' ?> w-5 h-5 rounded-full bg-white shadow transition-all"></span>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr id="user-empty" class="<?= $rows ? 'hidden' : '' ?>">
                <td colspan="6" class="px-5 py-12 text-center text-[14px] text-on-surface-variant">No accounts match your filters.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
</div>

<!-- Hộp thoại Add New User / Edit User -->
<div id="user-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="absolute inset-0 bg-black/40" data-close-modal></div>
  <form id="user-form" method="post" action="<?= BASE_URL ?>/master/users" novalidate
        class="relative w-full max-w-[460px] bg-white rounded-2xl shadow-xl px-8 py-7 flex flex-col gap-4">
    <input type="hidden" name="form_action" value="create">
    <input type="hidden" name="record_id" value="">
    <input type="hidden" name="user_code" value="">
    <input type="hidden" name="phone" value="">

    <div>
      <h2 id="modal-title" class="text-[24px] font-bold text-on-surface">Add New User</h2>
      <p id="modal-subtitle" class="text-[13px] text-on-surface-variant mt-0.5">Create a new account for the system</p>
    </div>

    <?php if (is_array($form_error) && !empty($form_error['errors'])): ?>
      <ul id="modal-errors" class="px-4 py-3 rounded-lg bg-error-container text-on-error-container text-[13px] list-disc list-inside">
        <?php foreach ($form_error['errors'] as $error): ?>
          <li><?= $e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <label class="flex flex-col gap-1.5">
      <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Full Name *</span>
      <input name="full_name" type="text" required maxlength="100" placeholder="Enter full name"
             class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] focus:border-primary outline-none">
    </label>
    <label class="flex flex-col gap-1.5">
      <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Email *</span>
      <input name="email" type="email" required maxlength="100" placeholder="Enter email"
             class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] focus:border-primary outline-none">
    </label>
    <label class="flex flex-col gap-1.5">
      <span id="password-label" class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Password *</span>
      <input name="password" type="password" minlength="6" maxlength="72" placeholder="Enter password" autocomplete="new-password"
             class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] focus:border-primary outline-none">
      <span id="password-hint" class="text-[12px] text-on-surface-variant">Password must be at least 6 characters long</span>
    </label>
    <div class="grid grid-cols-2 gap-4">
      <label class="flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Role *</span>
        <select name="role" class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] bg-white focus:border-primary outline-none">
          <option value="Student">Student</option>
          <option value="Lecturer">Lecturer</option>
          <option value="Technician">Technician</option>
          <option value="Manager">Manager</option>
        </select>
      </label>
      <label class="flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Status *</span>
        <select name="is_active" class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] bg-white focus:border-primary outline-none">
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </label>
    </div>
    <label id="dept-row" class="hidden flex-col gap-1.5">
      <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Department *</span>
      <select name="department_id" class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] bg-white focus:border-primary outline-none">
        <option value="">Choose department</option>
        <?php foreach ($departments as $dept): ?>
          <option value="<?= (int) $dept['department_id'] ?>"><?= $e($dept['department_name'] ?? $dept['department_code'] ?? ('#' . $dept['department_id'])) ?></option>
        <?php endforeach; ?>
      </select>
      <span class="text-[12px] text-on-surface-variant">Technicians must belong to a department.</span>
    </label>

    <p id="client-error" class="hidden text-[13px] text-red-600"></p>

    <div class="grid grid-cols-2 gap-4 pt-2">
      <button type="button" data-close-modal class="h-10 text-[14px] font-medium text-on-surface hover:text-primary">Cancel</button>
      <button id="submit-btn" type="submit" class="h-10 rounded-lg bg-primary text-white text-[14px] font-medium shadow-sm hover:bg-[#00225c]">Create User</button>
    </div>
  </form>
</div>

<script>
  (function () {
    const modal = document.getElementById('user-modal');
    const form = document.getElementById('user-form');
    const deptRow = document.getElementById('dept-row');
    const clientError = document.getElementById('client-error');
    const serverErrors = document.getElementById('modal-errors');
    const reopen = <?= json_encode($form_error, $jsonFlags) ?>;
    let mode = 'create';

    function syncDept() {
      const tech = form.elements.role.value === 'Technician';
      deptRow.classList.toggle('hidden', !tech);
      deptRow.classList.toggle('flex', tech);
    }
    form.elements.role.addEventListener('change', syncDept);

    function setMode(next, isSelf) {
      mode = next;
      const edit = next === 'update';
      form.elements.form_action.value = edit ? 'update' : 'create';
      document.getElementById('modal-title').textContent = edit ? 'Edit User' : 'Add New User';
      document.getElementById('modal-subtitle').textContent = edit ? 'Update this account' : 'Create a new account for the system';
      document.getElementById('submit-btn').textContent = edit ? 'Save Changes' : 'Create User';
      document.getElementById('password-label').textContent = edit ? 'New Password' : 'Password *';
      document.getElementById('password-hint').textContent = edit
        ? 'Leave blank to keep the current password (min 6 characters)'
        : 'Password must be at least 6 characters long';
      form.elements.password.placeholder = edit ? 'Leave blank to keep current' : 'Enter password';
      form.elements.role.disabled = !!isSelf;
      form.elements.is_active.disabled = !!isSelf;
    }

    function fill(values) {
      ['record_id', 'full_name', 'email', 'user_code', 'phone'].forEach(function (k) {
        form.elements[k].value = values[k] || '';
      });
      form.elements.role.value = values.role || 'Student';
      form.elements.is_active.value = values.is_active === '0' ? '0' : '1';
      form.elements.department_id.value = values.department_id || '';
      form.elements.password.value = '';
      syncDept();
    }

    function show() { modal.classList.remove('hidden'); modal.classList.add('flex'); form.elements.full_name.focus(); }
    function hide() {
      modal.classList.add('hidden'); modal.classList.remove('flex');
      clientError.classList.add('hidden');
      if (serverErrors) serverErrors.remove();
    }

    document.querySelectorAll('.js-open-create').forEach(function (b) {
      b.addEventListener('click', function () { fill({}); setMode('create'); show(); });
    });
    document.querySelectorAll('.js-open-edit').forEach(function (b) {
      b.addEventListener('click', function () {
        const record = JSON.parse(b.dataset.record);
        fill(record); setMode('update', record.is_self); show();
      });
    });
    modal.querySelectorAll('[data-close-modal]').forEach(function (el) { el.addEventListener('click', hide); });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') hide(); });

    // Kiểm tra nhanh phía trình duyệt; máy chủ vẫn kiểm tra lại.
    form.addEventListener('submit', function (ev) {
      const problems = [];
      if (form.elements.full_name.value.trim().length < 2) problems.push('Enter the full name.');
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.elements.email.value.trim())) problems.push('Enter a valid email.');
      const pw = form.elements.password.value;
      if ((mode === 'create' || pw !== '') && pw.length < 6) problems.push('Password must be at least 6 characters long.');
      if (form.elements.role.value === 'Technician' && !form.elements.department_id.value) problems.push('Choose a department for the technician.');
      if (problems.length) {
        ev.preventDefault();
        clientError.textContent = problems.join(' ');
        clientError.classList.remove('hidden');
        return;
      }
      // Ô bị khóa (sửa chính mình) không được gửi đi, nên bỏ khóa trước khi gửi.
      form.elements.role.disabled = false;
      form.elements.is_active.disabled = false;
    });

    document.querySelectorAll('form.js-toggle').forEach(function (f) {
      f.addEventListener('submit', function (ev) { if (!window.confirm(f.dataset.confirm)) ev.preventDefault(); });
    });

    // Tìm kiếm + lọc
    const search = document.getElementById('user-search');
    const role = document.getElementById('user-role');
    const status = document.getElementById('user-status');
    function apply() {
      const q = search.value.trim().toLowerCase();
      let n = 0;
      document.querySelectorAll('#user-body tr[data-search]').forEach(function (row) {
        const ok = (!q || row.dataset.search.indexOf(q) !== -1)
          && (role.value === 'all' || row.dataset.role === role.value)
          && (status.value === 'all' || row.dataset.status === status.value);
        row.classList.toggle('hidden', !ok);
        if (ok) { n++; row.querySelector('.js-index').textContent = n; }
      });
      document.getElementById('user-empty').classList.toggle('hidden', n !== 0);
    }
    [search, role, status].forEach(function (el) { el.addEventListener(el === search ? 'input' : 'change', apply); });

    if (reopen && reopen.values) {
      fill(reopen.values);
      const selfId = '<?= (int) $current_user_id ?>';
      setMode(reopen.action === 'update' ? 'update' : 'create', reopen.values.record_id === selfId);
      show();
    }
  })();
</script>

<?php require_once APPROOT . '/app/Views/layouts/manager/footer.php'; ?>
