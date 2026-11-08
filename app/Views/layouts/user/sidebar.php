<?php
/**
 * Master Sidebar for Users Portal (Student & Lecturer)
 */
$currentNav = $current_nav ?? 'dashboard';

// Đồng bộ thông tin người dùng từ session hoặc fallback từ database
$userName = $_SESSION['full_name'] ?? ($userName ?? 'Nguyen Minh Khoi');
$userCode = $_SESSION['user_code'] ?? ($userCode ?? '23070001');
$userRole = $_SESSION['role'] ?? ($userRole ?? 'Student');

if (empty($userInitials)) {
    $userInitials = 'NMK';
    if (!empty($userName)) {
        $nameParts = preg_split('/\s+/', trim($userName));
        if (count($nameParts) >= 3) {
            $userInitials = mb_strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1) . mb_substr(end($nameParts), 0, 1));
        } else {
            $userInitials = '';
            foreach ($nameParts as $part) {
                if (!empty($part)) {
                    $userInitials .= mb_strtoupper(mb_substr($part, 0, 1));
                }
            }
        }
    }
}
?>
<!-- Fixed Left Sidebar -->
<aside class="fixed left-0 top-0 h-screen w-64 bg-primary-container z-50 flex flex-col justify-between py-margin-sm shadow-xl select-none">
  <div class="flex flex-col gap-space-md">
    <!-- Logo & Title -->
    <div class="px-space-lg flex items-center gap-space-sm">
      <div class="w-9 h-9 rounded-lg bg-surface-container-lowest flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-primary-container text-[22px]">support_agent</span>
      </div>
      <div class="flex flex-col min-w-0 leading-tight">
        <span class="font-headline-h3 text-headline-h3 text-surface-container-lowest whitespace-nowrap">Campus Helpdesk</span>
        <span class="font-caption text-caption text-on-primary-container tracking-wider uppercase whitespace-nowrap">IT &amp; Facilities</span>
      </div>
    </div>

    <!-- CTA Button: Report an Issue -->
    <div class="px-space-md">
      <a href="<?= BASE_URL ?>/user/tickets/create" class="w-full h-10 px-space-md rounded-[10px] bg-surface-container-lowest/10 hover:bg-surface-container-lowest/15 text-surface-container-lowest border border-surface-container-highest/20 font-body-medium text-body-medium flex items-center justify-center gap-space-sm transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">add</span>
        <span>Report an Issue</span>
      </a>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex flex-col gap-space-xs px-space-sm">
      <!-- Dashboard -->
      <a href="<?= BASE_URL ?>/user/dashboard"
         class="flex items-center justify-between px-space-md py-space-sm rounded-lg transition-colors <?= ($currentNav === 'dashboard') ? 'bg-surface-container-lowest/15 text-surface-container-lowest font-body-medium' : 'text-surface-container-highest/80 hover:bg-surface-container-lowest/10 hover:text-surface-container-lowest' ?>">
        <div class="flex items-center gap-space-sm">
          <span class="material-symbols-outlined text-[20px]">grid_view</span>
          <span class="font-body-default text-body-default">Dashboard</span>
        </div>
      </a>

      <!-- My Tickets -->
      <a href="<?= BASE_URL ?>/user/tickets"
         class="flex items-center justify-between px-space-md py-space-sm rounded-lg transition-colors <?= ($currentNav === 'tickets') ? 'bg-surface-container-lowest/15 text-surface-container-lowest font-body-medium' : 'text-surface-container-highest/80 hover:bg-surface-container-lowest/10 hover:text-surface-container-lowest' ?>">
        <div class="flex items-center gap-space-sm">
          <span class="material-symbols-outlined text-[20px]">confirmation_number</span>
          <span class="font-body-default text-body-default">My Tickets</span>
        </div>
        <span class="px-space-xs py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-caption text-caption leading-none"><?= htmlspecialchars($myTicketsCount ?? 3) ?></span>
      </a>

      <!-- Profile -->
      <a href="<?= BASE_URL ?>/user/profile"
         class="flex items-center justify-between px-space-md py-space-sm rounded-lg transition-colors <?= ($currentNav === 'profile') ? 'bg-surface-container-lowest/15 text-surface-container-lowest font-body-medium' : 'text-surface-container-highest/80 hover:bg-surface-container-lowest/10 hover:text-surface-container-lowest' ?>">
        <div class="flex items-center gap-space-sm">
          <span class="material-symbols-outlined text-[20px]">account_circle</span>
          <span class="font-body-default text-body-default">Profile</span>
        </div>
      </a>
    </nav>
  </div>

  <!-- Bottom User Info & Logout Button -->
  <div class="px-space-sm pt-space-md border-t border-surface-container-highest/10">
    <div class="px-3 py-2.5 rounded-xl bg-surface-container-lowest/10 flex items-center justify-between gap-2.5">
      <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-8 h-8 rounded-full bg-surface-container-high text-primary-container font-semibold text-[12px] flex items-center justify-center shrink-0 select-none">
          <?= htmlspecialchars($userInitials) ?>
        </div>
        <div class="flex flex-col min-w-0 leading-tight">
          <span class="text-white text-[13px] font-semibold truncate" title="<?= htmlspecialchars($userName) ?>">
            <?= htmlspecialchars($userName) ?>
          </span>
          <span class="text-[11px] text-[#93a8cf] truncate">
            <?= htmlspecialchars($userCode) ?> - <?= htmlspecialchars($userRole) ?>
          </span>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/auth/logout" class="p-1 rounded-lg text-[#93a8cf] hover:text-white hover:bg-surface-container-lowest/10 transition-colors shrink-0" title="Sign out">
        <span class="material-symbols-outlined text-[20px]">logout</span>
      </a>
    </div>
  </div>
</aside>
