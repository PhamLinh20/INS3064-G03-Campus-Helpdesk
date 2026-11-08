<?php
/**
 * Master Header for Technicians Portal
 */
if (!defined('APPROOT')) {
    define('APPROOT', dirname(__DIR__, 4));
}
if (!defined('BASE_URL')) {
    $configFile = APPROOT . '/config/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        define('BASE_URL', '');
    }
}

// Lấy thông tin user từ Session
// Lấy thông tin user từ Session, mặc định fallback là Kỹ thuật viên IT mẫu trong Database (Nguyen Duc Anh - TECH001)
$userName = $_SESSION['full_name'] ?? ($user_name ?? 'Nguyen Duc Anh');
$userCode = $_SESSION['user_code'] ?? ($user_code ?? 'TECH001');
$userRole = $_SESSION['role'] ?? ($role ?? 'Technician');

// Tạo ký tự viết tắt (Initials) cho Avatar (ví dụ: Nguyen Duc Anh -> NDA)
$userInitials = 'NDA';
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
if (empty($userInitials)) $userInitials = 'NDA';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="web_dashboard" name="shell-type">
  <title><?= htmlspecialchars($page_title ?? 'Technician Dashboard - Campus Helpdesk') ?></title>

  <!-- Google Fonts Inter đồng bộ 100% với User Portal & Material Symbols -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

  <style>
    @layer base {
      html, body { margin: 0; padding: 0; }
      body { overscroll-behavior: none; }
      main > :first-child { margin-top: 0 !important; }
      main > :last-child { margin-bottom: 0 !important; }
    }
    ::-webkit-scrollbar { display: none; }
  </style>

  <script src="https://cdn.tailwindcss.com"></script>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "on-primary": "#ffffff",
            "tertiary": "#10181e",
            "on-error-container": "#93000a",
            "surface-bright": "#faf8ff",
            "secondary-container": "#7ca4fe",
            "surface-container-highest": "#d9e2ff",
            "secondary": "#2e5bb0",
            "secondary-fixed": "#d9e2ff",
            "primary-fixed-dim": "#aec6ff",
            "on-surface": "#051a3f",
            "on-primary-fixed": "#001a42",
            "surface-container-lowest": "#ffffff",
            "inverse-primary": "#aec6ff",
            "error-container": "#ffdad6",
            "on-tertiary-fixed-variant": "#40474f",
            "primary-fixed": "#d8e2ff",
            "on-secondary-fixed": "#001944",
            "tertiary-fixed-dim": "#c0c7d1",
            "secondary-fixed-dim": "#afc6ff",
            "outline-variant": "#c4c6d0",
            "primary": "#00163a",
            "on-secondary": "#ffffff",
            "inverse-surface": "#1e2f55",
            "on-surface-variant": "#44474f",
            "on-background": "#051a3f",
            "surface-container": "#e9edff",
            "tertiary-container": "#252c33",
            "on-primary-fixed-variant": "#2c4678",
            "on-tertiary-container": "#8c939c",
            "on-secondary-fixed-variant": "#074397",
            "on-tertiary-fixed": "#151c23",
            "surface-container-high": "#e1e8ff",
            "surface": "#faf8ff",
            "on-tertiary": "#ffffff",
            "primary-container": "#0b2a5b",
            "background": "#faf8ff",
            "outline": "#747780",
            "on-primary-container": "#7a93ca",
            "error": "#ba1a1a",
            "tertiary-fixed": "#dce3ed",
            "surface-tint": "#455e91",
            "surface-container-low": "#f1f3ff",
            "surface-dim": "#cdd9ff",
            "on-secondary-container": "#003883",
            "on-error": "#ffffff",
            "surface-variant": "#d9e2ff",
            "inverse-on-surface": "#edf0ff"
          },
          borderRadius: {
            "DEFAULT": "0.25rem",
            "lg": "0.5rem",
            "xl": "0.75rem",
            "full": "9999px"
          },
          spacing: {
            "space-2xl": "3rem",
            "space-xs": "0.25rem",
            "space-sm": "0.5rem",
            "gutter-sm": "1rem",
            "margin": "2rem",
            "space-xl": "2rem",
            "margin-sm": "1rem",
            "gutter": "1.5rem",
            "space-md": "1rem",
            "space-lg": "1.5rem",
            "gutter-mobile": "1rem",
            "margin-mobile": "1rem"
          },
          fontFamily: {
            "label-default": ["Inter", "sans-serif"],
            "headline-h1": ["Inter", "sans-serif"],
            "small-regular": ["Inter", "sans-serif"],
            "headline-h2": ["Inter", "sans-serif"],
            "caption": ["Inter", "sans-serif"],
            "headline-h3": ["Inter", "sans-serif"],
            "body-medium": ["Inter", "sans-serif"],
            "body-default": ["Inter", "sans-serif"],
            "headline-h1-mobile": ["Inter", "sans-serif"],
            "stat-metric": ["Inter", "sans-serif"],
            "headline-sm": ["Inter", "sans-serif"],
            "label-sm": ["Inter", "sans-serif"],
            "body-sm": ["Inter", "sans-serif"],
            "headline-xl": ["Inter", "sans-serif"],
            "body-md": ["Inter", "sans-serif"],
            "headline-lg": ["Inter", "sans-serif"],
            "label-lg": ["Inter", "sans-serif"],
            "label-md": ["Inter", "sans-serif"],
            "headline-md": ["Inter", "sans-serif"],
            "body-lg": ["Inter", "sans-serif"]
          },
          fontSize: {
            "label-default": ["12px", { lineHeight: "16px", fontWeight: "500" }],
            "headline-h1": ["28px", { lineHeight: "36px", fontWeight: "600" }],
            "small-regular": ["12px", { lineHeight: "16px", fontWeight: "400" }],
            "headline-h2": ["20px", { lineHeight: "28px", fontWeight: "600" }],
            "caption": ["11px", { lineHeight: "14px", letterSpacing: "0.02em", fontWeight: "500" }],
            "headline-h3": ["16px", { lineHeight: "24px", fontWeight: "600" }],
            "body-medium": ["14px", { lineHeight: "22px", fontWeight: "500" }],
            "body-default": ["14px", { lineHeight: "22px", fontWeight: "400" }],
            "headline-h1-mobile": ["24px", { lineHeight: "32px", fontWeight: "600" }],
            "stat-metric": ["32px", { lineHeight: "40px", fontWeight: "700" }],
            "headline-sm": ["18px", { lineHeight: "24px", fontWeight: "600" }],
            "label-sm": ["11px", { lineHeight: "14px", letterSpacing: "0.02em", fontWeight: "600" }],
            "body-sm": ["13px", { lineHeight: "18px", fontWeight: "400" }],
            "headline-xl": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "700" }],
            "body-md": ["14px", { lineHeight: "20px", fontWeight: "400" }],
            "headline-lg": ["24px", { lineHeight: "32px", letterSpacing: "-0.015em", fontWeight: "600" }],
            "label-lg": ["14px", { lineHeight: "20px", fontWeight: "600" }],
            "label-md": ["12px", { lineHeight: "16px", letterSpacing: "0.01em", fontWeight: "600" }],
            "headline-md": ["20px", { lineHeight: "28px", letterSpacing: "-0.01em", fontWeight: "600" }],
            "body-lg": ["16px", { lineHeight: "24px", fontWeight: "400" }]
          }
        }
      }
    };
  </script>
</head>
<body class="bg-background font-body-md text-body-md text-on-surface antialiased">

<!-- Fixed Top Header -->
<header class="fixed top-0 left-64 right-0 h-16 bg-surface-container-lowest/90 backdrop-blur-xl z-20 px-margin flex items-center justify-between shadow-[0_1px_8px_rgba(0,0,0,0.04)]">
  <!-- Breadcrumb -->
  <div class="flex items-center gap-space-sm">
    <span class="font-label-md text-label-md text-on-surface-variant">Technician Portal</span>
    <span class="text-on-surface-variant">/</span>
    <span class="font-label-md text-label-md text-primary font-semibold"><?= htmlspecialchars($breadcrumb_current ?? 'Console') ?></span>
  </div>

  <!-- Search Input -->
  <div class="flex items-center w-96 relative">
    <span class="material-symbols-outlined absolute left-3 text-on-surface-variant text-[18px]">search</span>
    <input class="w-full pl-9 pr-12 py-1.5 bg-surface-container-low rounded-xl text-body-sm font-body-sm text-on-surface placeholder-on-surface-variant focus:outline-none focus:bg-surface-container-lowest transition-colors border border-transparent focus:border-surface-variant" placeholder="Search ticket #, serial, room, or staff name..." type="text">
  </div>

  <!-- Right Actions: Notifications & Avatar with Dropdown -->
  <div class="flex items-center gap-space-md">
    <!-- Notification Bell (Truy cập trang Notifications theo yêu cầu) -->
    <a href="<?= BASE_URL ?>/technician/notifications" class="relative p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors cursor-pointer" title="Notifications">
      <span class="material-symbols-outlined text-[20px]">notifications</span>
      <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-error"></span>
    </a>

    <!-- Avatar with Dropdown Trigger & Chevron Indicator -->
    <div class="relative pl-space-sm" id="tech-user-menu-container">
      <button id="tech-user-menu-btn" type="button" class="flex items-center gap-2 py-1 px-1.5 rounded-xl hover:bg-surface-container-low transition-colors cursor-pointer focus:outline-none select-none">
        <div class="w-8 h-8 rounded-full bg-secondary-fixed text-primary font-semibold text-[12px] flex items-center justify-center shrink-0">
          <?= htmlspecialchars($userInitials) ?>
        </div>
        <div class="hidden xl:flex flex-col text-left leading-tight">
          <span class="font-label-md text-label-md text-on-surface font-semibold leading-tight"><?= htmlspecialchars($userName) ?></span>
          <span class="font-label-sm text-label-sm text-on-surface-variant leading-tight"><?= htmlspecialchars($userCode) ?> &middot; <?= htmlspecialchars($userRole) ?></span>
        </div>
        <!-- Dấu click dropdown cạnh avatar -->
        <span id="tech-user-chevron" class="material-symbols-outlined text-[18px] text-on-surface-variant transition-transform duration-200">expand_more</span>
      </button>

      <!-- Dropdown Menu (Theo yêu cầu: Chỉ để My Profile và Logout) -->
      <div id="tech-user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-surface-container-lowest rounded-xl shadow-lg border border-surface-variant/40 py-1.5 z-50">
        <a href="<?= BASE_URL ?>/technician/profile" class="flex items-center gap-2.5 px-3.5 py-2.5 text-body-sm text-on-surface hover:bg-surface-container-low transition-colors font-medium">
          <span class="material-symbols-outlined text-[18px] text-on-surface-variant">person</span>
          <span>My Profile</span>
        </a>
        <div class="my-1 border-t border-surface-variant/30"></div>
        <a href="<?= BASE_URL ?>/auth/logout" class="flex items-center gap-2.5 px-3.5 py-2.5 text-body-sm text-error hover:bg-error-container/40 transition-colors font-medium">
          <span class="material-symbols-outlined text-[18px]">logout</span>
          <span>Logout</span>
        </a>
      </div>
    </div>
  </div>
</header>
