<?php
/**
 * View: My Profile
 * URL: /auth/profile — dùng chung cho Manager, Technician, Student và Lecturer.
 * Khung layout theo đúng portal của vai trò đang đăng nhập.
 */
if (!defined('APPROOT')) {
    define('APPROOT', dirname(__DIR__, 2));
}
if (!defined('BASE_URL')) {
    $configFile = APPROOT . '/config/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        define('BASE_URL', '');
    }
}

$account = $account ?? [
    'user_id' => (int) ($_SESSION['user_id'] ?? 0),
    'user_code' => $_SESSION['user_code'] ?? '',
    'full_name' => $_SESSION['full_name'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'phone' => '',
    'role' => $_SESSION['role'] ?? 'Student',
    'department_name' => null,
    'department_code' => null,
    'is_active' => 1,
    'last_login_at' => null,
    'created_at' => null,
];
$errors = $errors ?? [];
$flash = $flash ?? null;

$role = (string) ($account['role'] ?? ($_SESSION['role'] ?? 'Student'));
$layout = 'user';
if ($role === 'Manager') {
    $layout = 'manager';
} elseif ($role === 'Technician') {
    $layout = 'technician';
}

$page_title = $page_title ?? 'My Profile - Campus Helpdesk';
$breadcrumb_current = $breadcrumb_current ?? 'My Profile';
$current_nav = $current_nav ?? 'profile';

$nameParts = preg_split('/\s+/', trim((string) ($account['full_name'] ?? ''))) ?: [];
$initials = '';
foreach ($nameParts as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
if (count($nameParts) >= 3) {
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1) . mb_substr((string) end($nameParts), 0, 1));
}
if ($initials === '') {
    $initials = 'CH';
}

$formatWhen = static function (?string $value, string $empty): string {
    if ($value === null || $value === '') {
        return $empty;
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y, H:i', $timestamp) : $empty;
};

$isActive = (int) ($account['is_active'] ?? 1) === 1;
$fieldClass = 'h-11 px-3 rounded-xl bg-surface-container-low border border-transparent focus:border-secondary focus:bg-white outline-none text-on-surface';

require_once APPROOT . '/app/Views/layouts/' . $layout . '/header.php';
require_once APPROOT . '/app/Views/layouts/' . $layout . '/sidebar.php';

$isUserPortal = $layout === 'user';
?>

<?php
// Mở lại hộp thoại đổi mật khẩu nếu lần gửi trước là đổi mật khẩu và bị lỗi.
$passwordAttempt = !empty($errors) && (($_POST['form_kind'] ?? '') === 'password');
$roleLabel = [
    'Manager' => 'Manager (Operations)',
    'Technician' => 'Technician (Field Support)',
    'Lecturer' => 'Lecturer (Faculty)',
    'Student' => 'Student',
][$role] ?? $role;
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$box = 'flex items-center gap-3 h-12 px-4 rounded-lg';
$updatedAgo = '';
if (!empty($account['updated_at']) && ($ts = strtotime((string) $account['updated_at']))) {
    $days = (int) floor((time() - $ts) / 86400);
    $updatedAgo = $days < 1 ? 'today' : ($days < 30 ? $days . ' day' . ($days === 1 ? '' : 's') . ' ago'
        : (int) floor($days / 30) . ' month' . ((int) floor($days / 30) === 1 ? '' : 's') . ' ago');
}
?>

<div class="pl-64">
  <main class="<?= $isUserPortal ? 'relative pt-16 min-h-screen bg-surface' : 'relative pt-24 pb-12 px-8 w-full min-h-screen bg-background' ?>">
    <div class="<?= $isUserPortal ? 'max-w-[1200px] mx-auto px-margin py-space-xl' : 'max-w-[1100px] mx-auto' ?>">
      <div class="flex flex-col w-full gap-6">

        <h1 class="text-[28px] font-bold text-on-surface tracking-tight">My Profile</h1>

        <?php if (is_array($flash) && !empty($flash['message'])): ?>
          <?php $flashOk = ($flash['type'] ?? '') === 'success'; ?>
          <div class="flex items-start gap-3 px-4 py-3 rounded-xl border <?= $flashOk ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-error-container border-error/20 text-on-error-container' ?>" role="status">
            <span class="material-symbols-outlined text-[20px]"><?= $flashOk ? 'check_circle' : 'error' ?></span>
            <p class="text-[14px] leading-6"><?= $h($flash['message']) ?></p>
          </div>
        <?php endif; ?>

        <?php if (!$isActive): ?>
          <div class="flex items-start gap-3 px-4 py-3 rounded-xl border bg-amber-50 border-amber-200 text-amber-900" role="status">
            <span class="material-symbols-outlined text-[20px]">info</span>
            <p class="text-[14px] leading-6">This account is inactive. A manager can turn it back on from Users Management.</p>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors) && !$passwordAttempt): ?>
          <div class="px-4 py-3 rounded-xl border bg-error-container border-error/20 text-on-error-container" role="alert">
            <ul class="list-disc list-inside text-[14px] leading-6">
              <?php foreach ($errors as $error): ?><li><?= $h($error) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Personal Information -->
        <form id="profile-form" method="post" action="<?= BASE_URL ?>/auth/profile" class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm p-6 flex flex-col gap-5">
          <input type="hidden" name="form_kind" value="profile">
          <div class="flex items-start gap-3">
            <span class="w-10 h-10 rounded-lg bg-primary-fixed text-primary flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[22px]">badge</span>
            </span>
            <div>
              <h2 class="text-[18px] font-semibold text-on-surface leading-tight">Personal Information</h2>
              <p class="text-[12px] text-on-surface-variant">Update your identity details and direct contact number</p>
            </div>
          </div>

          <div class="flex items-center gap-4 p-4 rounded-xl bg-surface-container-low">
            <div class="relative w-[60px] h-[60px] shrink-0">
              <div class="w-full h-full rounded-full bg-primary text-white text-[20px] font-bold flex items-center justify-center"><?= $h($initials) ?></div>
              <span class="absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-white <?= $isActive ? 'bg-[#1d4ed8]' : 'bg-slate-400' ?>" title="<?= $isActive ? 'Active' : 'Inactive' ?>"></span>
            </div>
            <div>
              <p class="text-[18px] font-semibold text-on-surface"><?= $h($account['full_name']) ?></p>
              <div class="mt-1 flex flex-wrap gap-2">
                <span class="px-2.5 py-0.5 rounded-md bg-primary text-white text-[11px] font-semibold"><?= $h($role) ?></span>
                <?php if (!empty($account['department_name'])): ?>
                  <span class="px-2.5 py-0.5 rounded-md bg-primary-fixed text-primary text-[11px] font-medium"><?= $h($account['department_name']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
            <div class="flex flex-col gap-1.5">
              <span class="text-[12px] font-semibold text-on-surface">User Code</span>
              <div class="<?= $box ?> bg-surface-container-low text-on-surface-variant text-[13px]">
                <span class="material-symbols-outlined text-[18px]">lock</span><?= $h($account['user_code'] ?: '—') ?>
              </div>
              <span class="text-[11px] text-on-surface-variant">System identifier (Cannot be changed)</span>
            </div>
            <label class="flex flex-col gap-1.5">
              <span class="text-[12px] font-semibold text-on-surface">Full Name <span class="text-red-600">*</span></span>
              <div class="<?= $box ?> bg-white border border-surface-variant focus-within:border-primary">
                <span class="material-symbols-outlined text-[18px] text-[#1d4ed8]">person</span>
                <input name="full_name" required maxlength="100" value="<?= $h($account['full_name']) ?>" class="flex-1 bg-transparent outline-none text-[14px] text-on-surface">
              </div>
              <span class="text-[11px] text-on-surface-variant">Shown in ticket assignment logs</span>
            </label>
            <div class="flex flex-col gap-1.5">
              <span class="text-[12px] font-semibold text-on-surface">System Role</span>
              <div class="<?= $box ?> bg-surface-container-low text-on-surface-variant text-[13px]">
                <span class="material-symbols-outlined text-[18px]">verified_user</span><?= $h($roleLabel) ?>
              </div>
              <span class="text-[11px] text-on-surface-variant">Permissions assigned by role hierarchy policy</span>
            </div>
            <label class="flex flex-col gap-1.5">
              <span class="text-[12px] font-semibold text-on-surface">Phone Number <span class="text-red-600">*</span></span>
              <div class="<?= $box ?> bg-white border border-surface-variant focus-within:border-primary">
                <span class="material-symbols-outlined text-[18px] text-[#1d4ed8]">call</span>
                <input name="phone" maxlength="20" value="<?= $h($account['phone'] ?? '') ?>" placeholder="0901000001" class="flex-1 bg-transparent outline-none text-[14px] text-on-surface">
              </div>
              <span class="text-[11px] text-on-surface-variant">Used for emergency alert calls from the on-duty team</span>
            </label>
            <div class="flex flex-col gap-1.5">
              <span class="text-[12px] font-semibold text-on-surface">Institutional Email</span>
              <div class="<?= $box ?> bg-surface-container-low text-on-surface-variant text-[13px]">
                <?= $h($account['email']) ?>
              </div>
              <input type="hidden" name="email" value="<?= $h($account['email']) ?>">
              <span class="text-[11px] text-on-surface-variant">SSO-authorized campus email</span>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="reset" class="px-4 h-10 rounded-lg bg-surface-container-high text-on-surface text-[13px] font-medium hover:bg-surface-dim">Cancel</button>
            <button type="submit" class="inline-flex items-center gap-2 px-4 h-10 rounded-lg bg-primary text-white text-[13px] font-medium hover:bg-[#00225c]">
              <span class="material-symbols-outlined text-[18px]">save</span>Save Changes
            </button>
          </div>
        </form>

        <!-- Security -->
        <h2 class="text-[20px] font-semibold text-on-surface">Security</h2>
        <div class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm p-5 flex items-center gap-4">
          <span class="material-symbols-outlined text-[28px] text-primary" style="font-variation-settings:'FILL' 1">lock</span>
          <div class="flex-1">
            <p class="text-[14px] font-semibold text-on-surface">Password</p>
            <p class="text-[12px] text-on-surface-variant"><?= $updatedAgo !== '' ? 'Account last updated ' . $h($updatedAgo) : 'Use a strong password you do not use elsewhere' ?></p>
          </div>
          <button type="button" id="open-password" class="px-4 h-9 rounded-lg border border-outline text-on-surface text-[13px] font-medium hover:bg-surface-container-low">Change password</button>
        </div>

<!-- Hộp thoại Change Password -->
<div id="password-modal" class="fixed inset-0 z-50 <?= $passwordAttempt ? 'flex' : 'hidden' ?> items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="absolute inset-0 bg-black/40" data-close-password></div>
  <form id="password-form" method="post" action="<?= BASE_URL ?>/auth/profile" class="relative w-full max-w-[420px] bg-white rounded-2xl shadow-xl px-8 py-7 flex flex-col gap-4">
    <input type="hidden" name="form_kind" value="password">
    <input type="hidden" name="full_name" value="<?= $h($account['full_name']) ?>">
    <input type="hidden" name="email" value="<?= $h($account['email']) ?>">
    <input type="hidden" name="phone" value="<?= $h($account['phone'] ?? '') ?>">
    <div>
      <h2 class="text-[26px] font-bold text-on-surface">Change Password</h2>
      <p class="text-[13px] text-on-surface-variant mt-0.5">Choose a strong password to ensure your account security.</p>
    </div>
    <?php if ($passwordAttempt): ?>
      <ul class="js-pw-errors px-4 py-3 rounded-lg bg-error-container text-on-error-container text-[13px] list-disc list-inside">
        <?php foreach ($errors as $error): ?><li><?= $h($error) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <label class="flex flex-col gap-1.5">
      <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Old Password *</span>
      <input name="current_password" type="password" required autocomplete="current-password" placeholder="Enter old Password" class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] focus:border-primary outline-none">
    </label>
    <label class="flex flex-col gap-1.5">
      <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">New Password *</span>
      <input name="new_password" type="password" required minlength="6" maxlength="72" autocomplete="new-password" placeholder="Enter new Password" class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] focus:border-primary outline-none">
      <span class="text-[12px] text-on-surface-variant">Password must be at least 6 characters long</span>
    </label>
    <label class="flex flex-col gap-1.5">
      <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Confirm New Password *</span>
      <input name="confirm_password" type="password" required minlength="6" maxlength="72" autocomplete="new-password" placeholder="Confirm New Password" class="h-10 px-3 rounded-lg border border-outline-variant text-[14px] focus:border-primary outline-none">
    </label>
    <p id="pw-client-error" class="hidden text-[13px] text-red-600"></p>
    <div class="grid grid-cols-2 gap-4 pt-2">
      <button type="button" data-close-password class="h-10 text-[14px] font-medium text-on-surface hover:text-primary">Cancel</button>
      <button type="submit" class="h-10 rounded-lg bg-primary text-white text-[14px] font-medium hover:bg-[#00225c]">Change Password</button>
    </div>
  </form>
</div>

<script>
  (function () {
    const modal = document.getElementById('password-modal');
    const form = document.getElementById('password-form');
    const err = document.getElementById('pw-client-error');
    function open() { form.reset(); err.classList.add('hidden'); modal.classList.remove('hidden'); modal.classList.add('flex'); form.elements.current_password.focus(); }
    function close() {
      modal.classList.add('hidden'); modal.classList.remove('flex');
      document.querySelectorAll('.js-pw-errors').forEach(function (el) { el.remove(); });
    }
    document.getElementById('open-password').addEventListener('click', open);
    modal.querySelectorAll('[data-close-password]').forEach(function (el) { el.addEventListener('click', close); });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') close(); });
    form.addEventListener('submit', function (ev) {
      const p = [];
      if (!form.elements.current_password.value) p.push('Enter your old password.');
      if (form.elements.new_password.value.length < 6) p.push('Password must be at least 6 characters long.');
      if (form.elements.new_password.value !== form.elements.confirm_password.value) p.push('New password and confirmation do not match.');
      if (p.length) { ev.preventDefault(); err.textContent = p.join(' '); err.classList.remove('hidden'); }
    });
  })();
</script>

<?php if ($isUserPortal): ?>
<?php require_once APPROOT . '/app/Views/layouts/user/footer.php'; ?>
<?php else: ?>
      </div>
    </div>
  </main>
</div>
<?php require_once APPROOT . '/app/Views/layouts/' . $layout . '/footer.php'; ?>
<?php endif; ?>
