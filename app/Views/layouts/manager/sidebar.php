<?php
/**
 * Master Sidebar for Manager Portal
 * Khớp 100% danh sách điều hướng theo yêu cầu và cơ sở dữ liệu
 */
$currentNav = $current_nav ?? 'dashboard';
?>
<!-- Fixed Left Sidebar (w-64 đồng bộ với Technician & User) -->
<aside class="fixed inset-y-0 left-0 w-64 bg-[#0b2a5b] z-30 flex flex-col justify-between border-r border-[#0b2a5b] shadow-sm select-none">
  <div class="flex flex-col">
    <!-- Brand Logo (Campus Helpdesk - IT & Facilities) -->
    <div class="h-16 px-space-lg flex items-center gap-space-sm border-b border-white/10 select-none">
      <div class="w-9 h-9 rounded-lg bg-white/15 text-white flex items-center justify-center shrink-0 shadow-sm">
        <span class="material-symbols-outlined text-white text-[22px]">support_agent</span>
      </div>
      <div class="flex flex-col min-w-0 leading-tight">
        <span class="font-headline-h3 text-headline-h3 text-white font-semibold whitespace-nowrap">Campus Helpdesk</span>
        <span class="font-caption text-caption text-white/60 tracking-wider uppercase whitespace-nowrap">IT &amp; Facilities</span>
      </div>
    </div>

    <!-- Navigation Menu (Chuẩn theo yêu cầu người dùng & cấu trúc Database) -->
    <nav class="flex flex-col gap-space-xs px-space-sm mt-space-md">
      
      <!-- 1. Dashboard -->
      <a href="<?= BASE_URL ?>/manager/dashboard"
         class="flex items-center gap-space-sm px-space-md py-space-sm rounded-xl transition-colors <?= ($currentNav === 'dashboard') ? 'bg-white/15 text-white font-semibold' : 'text-white/70 hover:bg-white/10 hover:text-white font-normal' ?>">
        <span class="material-symbols-outlined text-[20px] <?= ($currentNav === 'dashboard') ? 'text-white' : 'text-white/70' ?>">grid_view</span>
        <span class="font-body-default text-body-default">Dashboard</span>
      </a>

      <!-- 2. Tickets & Assignment -->
      <a href="<?= BASE_URL ?>/manager/tickets"
         class="flex items-center gap-space-sm px-space-md py-space-sm rounded-xl transition-colors <?= ($currentNav === 'tickets' || $currentNav === 'assignment') ? 'bg-white/15 text-white font-semibold' : 'text-white/70 hover:bg-white/10 hover:text-white font-normal' ?>">
        <span class="material-symbols-outlined text-[20px] <?= ($currentNav === 'tickets' || $currentNav === 'assignment') ? 'text-white' : 'text-white/70' ?>">confirmation_number</span>
        <span class="font-body-default text-body-default">Tickets &amp; Assignment</span>
      </a>

      <!-- 3. Classrooms Management -->
      <a href="<?= BASE_URL ?>/master/classrooms"
         class="flex items-center gap-space-sm px-space-md py-space-sm rounded-xl transition-colors <?= ($currentNav === 'classrooms') ? 'bg-white/15 text-white font-semibold' : 'text-white/70 hover:bg-white/10 hover:text-white font-normal' ?>">
        <span class="material-symbols-outlined text-[20px] <?= ($currentNav === 'classrooms') ? 'text-white' : 'text-white/70' ?>">meeting_room</span>
        <span class="font-body-default text-body-default">Classrooms Management</span>
      </a>

      <!-- 4. Users Management -->
      <a href="<?= BASE_URL ?>/master/users"
         class="flex items-center gap-space-sm px-space-md py-space-sm rounded-xl transition-colors <?= ($currentNav === 'users') ? 'bg-white/15 text-white font-semibold' : 'text-white/70 hover:bg-white/10 hover:text-white font-normal' ?>">
        <span class="material-symbols-outlined text-[20px] <?= ($currentNav === 'users') ? 'text-white' : 'text-white/70' ?>">group</span>
        <span class="font-body-default text-body-default">Users Management</span>
      </a>

      <!-- 5. Services Management -->
      <a href="<?= BASE_URL ?>/master/services"
         class="flex items-center gap-space-sm px-space-md py-space-sm rounded-xl transition-colors <?= ($currentNav === 'services') ? 'bg-white/15 text-white font-semibold' : 'text-white/70 hover:bg-white/10 hover:text-white font-normal' ?>">
        <span class="material-symbols-outlined text-[20px] <?= ($currentNav === 'services') ? 'text-white' : 'text-white/70' ?>">dns</span>
        <span class="font-body-default text-body-default">Services Management</span>
      </a>

      <!-- 6. Reports & Analytics -->
      <a href="<?= BASE_URL ?>/manager/reports"
         class="flex items-center gap-space-sm px-space-md py-space-sm rounded-xl transition-colors <?= ($currentNav === 'reports') ? 'bg-white/15 text-white font-semibold' : 'text-white/70 hover:bg-white/10 hover:text-white font-normal' ?>">
        <span class="material-symbols-outlined text-[20px] <?= ($currentNav === 'reports') ? 'text-white' : 'text-white/70' ?>">query_stats</span>
        <span class="font-body-default text-body-default">Reports &amp; Analytics</span>
      </a>

    </nav>
  </div>
</aside>
