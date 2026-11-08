<?php
/**
 * Master Sidebar for Technicians Portal
 * Đã bỏ nút logout ở sidebar (đã chuyển vào dropdown avatar theo yêu cầu)
 */
$currentNav = $current_nav ?? 'dashboard';
?>
<!-- Fixed Left Sidebar (w-64 đồng bộ với User) -->
<aside class="fixed inset-y-0 left-0 w-64 bg-surface-container-lowest z-30 flex flex-col justify-between border-r border-surface-variant/40 shadow-sm select-none">
  <div class="flex flex-col">
    <!-- Brand Logo (Tên hệ thống đồng bộ 100% với User: Campus Helpdesk - IT & Facilities) -->
    <div class="h-16 px-space-lg flex items-center gap-space-sm border-b border-surface-variant/40 select-none">
      <div class="w-9 h-9 rounded-lg bg-primary-container text-white flex items-center justify-center shrink-0 shadow-sm">
        <span class="material-symbols-outlined text-white text-[22px]">support_agent</span>
      </div>
      <div class="flex flex-col min-w-0 leading-tight">
        <span class="font-headline-h3 text-headline-h3 text-on-surface font-semibold whitespace-nowrap">Campus Helpdesk</span>
        <span class="font-caption text-caption text-on-surface-variant tracking-wider uppercase whitespace-nowrap">IT &amp; Facilities</span>
      </div>
    </div>

    <!-- Navigation Menu (Đồng bộ font & padding với User Sidebar) -->
    <nav class="flex flex-col gap-space-xs px-space-sm mt-space-md">
      <!-- Dashboard -->
      <a href="<?= BASE_URL ?>/technician/dashboard"
         class="flex items-center justify-between px-space-md py-space-sm rounded-lg transition-colors <?= ($currentNav === 'dashboard') ? 'bg-surface-container-high text-primary font-body-medium font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' ?>">
        <div class="flex items-center gap-space-sm">
          <span class="material-symbols-outlined text-[20px] <?= ($currentNav === 'dashboard') ? 'text-primary' : '' ?>">grid_view</span>
          <span class="font-body-default text-body-default">Dashboard</span>
        </div>
      </a>

      <!-- Assigned Tasks -->
      <a href="<?= BASE_URL ?>/technician/tasks"
         class="flex items-center justify-between px-space-md py-space-sm rounded-lg transition-colors <?= ($currentNav === 'tasks') ? 'bg-surface-container-high text-primary font-body-medium font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' ?>">
        <div class="flex items-center gap-space-sm">
          <span class="material-symbols-outlined text-[20px]">task_alt</span>
          <span class="font-body-default text-body-default">Assigned Tasks</span>
        </div>
        <span class="px-space-xs py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-caption text-caption leading-none font-semibold">3</span>
      </a>

      <!-- Asset Lookup -->
      <a href="<?= BASE_URL ?>/technician/assets"
         class="flex items-center justify-between px-space-md py-space-sm rounded-lg transition-colors <?= ($currentNav === 'assets') ? 'bg-surface-container-high text-primary font-body-medium font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' ?>">
        <div class="flex items-center gap-space-sm">
          <span class="material-symbols-outlined text-[20px]">qr_code_scanner</span>
          <span class="font-body-default text-body-default">Asset Lookup</span>
        </div>
      </a>
    </nav>
  </div>
</aside>
