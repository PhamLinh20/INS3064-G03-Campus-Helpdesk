<?php
/**
 * View: User Dashboard
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

$page_title = 'Dashboard - Users Portal';
$breadcrumb_current = 'Dashboard';
$current_nav = 'dashboard';

require_once APPROOT . '/app/Views/layouts/user/header.php';
require_once APPROOT . '/app/Views/layouts/user/sidebar.php';
?>

<div class="pl-64">
  <main class="relative pt-16 min-h-screen bg-surface">
    <div class="max-w-[1200px] mx-auto px-margin py-space-xl">
      <div class="flex flex-col w-full gap-space-lg">

        <!-- Welcome Header -->
        <section class="flex flex-col gap-space-xs">
          <div class="flex items-center justify-between">
            <h1 class="font-headline-h1 text-headline-h1 text-on-surface tracking-tight">
              Welcome back, <?= htmlspecialchars($userName) ?>
            </h1>
          </div>
          <p class="font-body-default text-body-default text-on-surface-variant max-w-2xl">
            Track your reported issues or dispatch an on-duty technician for classroom equipment.
          </p>
        </section>

        <!-- Top Section: Completed Ticket Rating Banner -->
        <section class="relative overflow-hidden rounded-xl bg-surface-container-low shadow-sm transition-all duration-200" id="rating-banner">
          <div class="p-space-md flex flex-col md:flex-row md:items-center justify-between gap-space-md border border-surface-variant/50 rounded-xl bg-[#ECFDF5]/60">
            <div class="flex items-center gap-space-md min-w-0">
              <div class="w-9 h-9 rounded-full bg-[#D1FAE5] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[#059669] text-[22px]">check_circle</span>
              </div>
              <div class="flex items-center flex-wrap gap-2 min-w-0">
                <p class="font-body-medium text-body-medium text-on-surface truncate">
                  <span class="font-semibold">Ticket TIC-7490</span> has been marked <span class="px-2 py-0.5 rounded-full text-[12px] font-semibold bg-[#D1FAE5] text-[#059669]">Completed</span> &middot; Rate Service &amp; Confirm Sign-off
                </p>
                <span class="material-symbols-outlined text-[16px] text-[#059669]">arrow_forward</span>
              </div>
            </div>
            <div class="flex items-center gap-space-sm shrink-0 self-end md:self-auto">
              <button class="h-9 px-space-md rounded-full bg-[#059669] text-white font-body-medium text-caption flex items-center gap-1 shadow-sm hover:bg-[#047857] transition-colors cursor-pointer" type="button">
                <span>Rate Service ⭐</span>
              </button>
              <button class="w-8 h-8 rounded-full flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container-high transition-colors cursor-pointer" onclick="document.getElementById('rating-banner').classList.add('hidden')" title="Dismiss notice" type="button">
                <span class="material-symbols-outlined text-[18px]">close</span>
              </button>
            </div>
          </div>
        </section>

        <!-- Hero CTA Card (Full 12-Column Width) -->
        <section class="relative overflow-hidden rounded-xl bg-primary-container text-on-primary shadow-md">
          <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full bg-secondary/20 blur-3xl pointer-events-none"></div>
          <div class="relative z-10 px-space-lg py-space-lg flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
            <div class="flex flex-col gap-1 max-w-2xl">
              <div class="flex items-center gap-space-xs font-caption text-caption uppercase tracking-wider text-on-primary-container">
                <span class="material-symbols-outlined text-[16px]">bolt</span>
                <span>Immediate On-Campus Response</span>
              </div>
              <h2 class="font-headline-h2 text-headline-h2 text-surface-container-lowest font-semibold leading-tight">Classroom equipment issue?</h2>
              <p class="font-body-default text-body-default text-primary-fixed-dim leading-relaxed">Report technical faults or facility issues for campus technical support.</p>
            </div>
            <div class="shrink-0 flex items-center">
              <a href="<?= BASE_URL ?>/user/tickets/create" class="group h-11 px-space-lg rounded-xl bg-secondary hover:bg-on-secondary-container text-on-secondary font-body-medium text-body-medium font-semibold flex items-center gap-space-sm shadow-md hover:shadow-lg transition-all cursor-pointer">
                <span>Start a Report</span>
                <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
              </a>
            </div>
          </div>
        </section>

        <!-- Active Tickets Section (Full Width) -->
        <section class="flex flex-col gap-space-md">
          <!-- Section Header -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-space-xs">
              <h2 class="font-headline-h2 text-headline-h2 text-on-surface font-semibold">Recent Tickets</h2>
            </div>
            <a class="font-body-medium text-body-medium text-secondary hover:text-on-secondary-container flex items-center gap-space-xs transition-colors group" href="<?= BASE_URL ?>/user/tickets">
              <span>View all tickets (3)</span>
              <span class="material-symbols-outlined text-[18px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
            </a>
          </div>

          <!-- Tickets Card List (Aligned with Database Schema) -->
          <div class="flex flex-col gap-space-sm">
            <!-- Ticket 1: TIC-7606 (Pending) -->
            <article class="p-space-md rounded-xl bg-surface-container-lowest border border-[#E2E8F0] shadow-sm hover:border-secondary/40 transition-all flex flex-col gap-2.5">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-space-sm flex-wrap">
                  <a href="<?= BASE_URL ?>/user/tickets/detail/1" class="font-semibold text-secondary hover:underline text-[14px]">TIC-7606</a>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-default text-[12px] font-medium bg-[#FEF3C7] text-[#B45309]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#B45309]"></span>Pending
                  </span>
                  <span class="px-2 py-0.5 rounded font-caption text-caption font-semibold bg-[#FEF3C7]/60 text-[#B45309]">Medium</span>
                  <span class="font-medium text-on-surface text-[14px]">General Facility (Wall Socket)</span>
                </div>
                <div class="flex items-center gap-1 font-caption text-caption text-on-surface-variant font-medium shrink-0">
                  <span class="material-symbols-outlined text-[15px] text-outline">schedule</span>
                  <span>2026-10-14 08:00</span>
                </div>
              </div>
              <div class="flex items-center justify-between gap-2 pt-2 border-t border-surface-variant/30 text-[12px]">
                <div class="flex items-center gap-1 text-on-surface-variant truncate">
                  <span class="material-symbols-outlined text-[15px] text-outline shrink-0">location_on</span>
                  <span class="truncate">Room A1-202 &middot; Electrical Socket Issue</span>
                </div>
                <a href="<?= BASE_URL ?>/user/tickets/detail/1" class="inline-flex items-center gap-0.5 font-semibold text-secondary hover:underline shrink-0 text-[12px]">
                  <span>View Details</span>
                  <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
              </div>
            </article>

            <!-- Ticket 2: TIC-7490 (Completed) -->
            <article class="p-space-md rounded-xl bg-surface-container-lowest border border-[#E2E8F0] shadow-sm hover:border-secondary/40 transition-all flex flex-col gap-2.5">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-space-sm flex-wrap">
                  <a href="<?= BASE_URL ?>/user/tickets/detail/5" class="font-semibold text-secondary hover:underline text-[14px]">TIC-7490</a>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-default text-[12px] font-medium bg-[#D1FAE5] text-[#059669]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#059669]"></span>Completed
                  </span>
                  <span class="px-2 py-0.5 rounded font-caption text-caption font-semibold bg-[#FEF3C7]/60 text-[#B45309]">Medium</span>
                  <span class="font-medium text-on-surface text-[14px]">HP ProDesk Lab Computers (x30)</span>
                </div>
                <div class="flex items-center gap-1 font-caption text-caption text-on-surface-variant font-medium shrink-0">
                  <span class="material-symbols-outlined text-[15px] text-outline">schedule</span>
                  <span>2026-10-12 10:00</span>
                </div>
              </div>
              <div class="flex items-center justify-between gap-2 pt-2 border-t border-surface-variant/30 text-[12px]">
                <div class="flex items-center gap-1 text-on-surface-variant truncate">
                  <span class="material-symbols-outlined text-[15px] text-outline shrink-0">location_on</span>
                  <span class="truncate">Computer Lab B2-301 &middot; IT Lab Hardware</span>
                </div>
                <a href="<?= BASE_URL ?>/user/tickets/detail/5" class="inline-flex items-center gap-0.5 font-semibold text-secondary hover:underline shrink-0 text-[12px]">
                  <span>View Details</span>
                  <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
              </div>
            </article>

            <!-- Ticket 3: TIC-7450 (Closed) -->
            <article class="p-space-md rounded-xl bg-surface-container-lowest border border-[#E2E8F0] shadow-sm hover:border-secondary/40 transition-all flex flex-col gap-2.5">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-space-sm flex-wrap">
                  <a href="<?= BASE_URL ?>/user/tickets/detail/6" class="font-semibold text-secondary hover:underline text-[14px]">TIC-7450</a>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-default text-[12px] font-medium bg-[#F1F5F9] text-[#475569]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#475569]"></span>Closed
                  </span>
                  <span class="px-2 py-0.5 rounded font-caption text-caption font-semibold bg-[#FFEDD5] text-[#C2410C]">High</span>
                  <span class="font-medium text-on-surface text-[14px]">Cisco 24-Port Gigabit Switch</span>
                </div>
                <div class="flex items-center gap-1 font-caption text-caption text-on-surface-variant font-medium shrink-0">
                  <span class="material-symbols-outlined text-[15px] text-outline">schedule</span>
                  <span>2026-10-10 13:00</span>
                </div>
              </div>
              <div class="flex items-center justify-between gap-2 pt-2 border-t border-surface-variant/30 text-[12px]">
                <div class="flex items-center gap-1 text-on-surface-variant truncate">
                  <span class="material-symbols-outlined text-[15px] text-outline shrink-0">location_on</span>
                  <span class="truncate">Computer Lab B2-301 &middot; Network Infrastructure</span>
                </div>
                <a href="<?= BASE_URL ?>/user/tickets/detail/6" class="inline-flex items-center gap-0.5 font-semibold text-secondary hover:underline shrink-0 text-[12px]">
                  <span>View Details</span>
                  <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
              </div>
            </article>
          </div>
        </section>

<?php
require_once APPROOT . '/app/Views/layouts/user/footer.php';
?>
