<?php
/**
 * Master Header for Users Portal (Student & Lecturer)
 */
if (!defined('BASE_URL')) {
    $configFile = dirname(__DIR__, 4) . '/config/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        define('BASE_URL', '');
    }
}

// Lấy thông tin user từ Session, mặc định fallback là tài khoản mẫu trong Database (Student: Nguyen Minh Khoi)
$userName = $_SESSION['full_name'] ?? ($user_name ?? 'Nguyen Minh Khoi');
$userCode = $_SESSION['user_code'] ?? ($user_code ?? '23070001');
$userRole = $_SESSION['role'] ?? ($role ?? 'Student');

// Tạo ký tự viết tắt (Initials) cho Avatar (ví dụ: Nguyen Minh Khoi -> NMK)
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
if (empty($userInitials)) $userInitials = 'NMK';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="web_dashboard" name="shell-type">
  <title><?= htmlspecialchars($page_title ?? 'Dashboard - Users Portal') ?></title>

  <!-- Google Fonts & Material Symbols -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

  <style>
    @layer base {
      html, body { margin: 0; padding: 0; }
      body { overscroll-behavior: none; }
      main > :first-child { margin-top: 0 !important; }
      main > :last-child { margin-bottom: 0 !important; }
    }
    ::-webkit-scrollbar { display: none; }
  </style>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "tertiary-container": "#252c33",
            "outline": "#747780",
            "surface-container": "#e9edff",
            "on-surface": "#051a3f",
            "surface-dim": "#cdd9ff",
            "surface-bright": "#faf8ff",
            "surface-container-lowest": "#ffffff",
            "on-secondary": "#ffffff",
            "inverse-on-surface": "#edf0ff",
            "on-error": "#ffffff",
            "secondary-container": "#7ca4fe",
            "on-tertiary-fixed-variant": "#40474f",
            "surface-container-high": "#e1e8ff",
            "secondary": "#2e5bb0",
            "surface-variant": "#d9e2ff",
            "tertiary": "#10181e",
            "surface-container-low": "#f1f3ff",
            "surface-tint": "#455e91",
            "background": "#faf8ff",
            "error": "#ba1a1a",
            "primary": "#00163a",
            "on-secondary-container": "#003883",
            "surface-container-highest": "#d9e2ff",
            "on-background": "#051a3f",
            "on-primary": "#ffffff",
            "inverse-primary": "#aec6ff",
            "primary-fixed": "#d8e2ff",
            "outline-variant": "#c4c6d0",
            "on-primary-fixed": "#001a42",
            "on-tertiary-container": "#8c939c",
            "inverse-surface": "#1e2f55",
            "primary-container": "#0b2a5b",
            "primary-fixed-dim": "#aec6ff",
            "tertiary-fixed": "#dce3ed",
            "on-secondary-fixed-variant": "#074397",
            "surface": "#faf8ff",
            "on-tertiary-fixed": "#151c23",
            "on-secondary-fixed": "#001944",
            "on-primary-container": "#7a93ca",
            "on-primary-fixed-variant": "#2c4678",
            "tertiary-fixed-dim": "#c0c7d1",
            "on-tertiary": "#ffffff",
            "secondary-fixed": "#d9e2ff",
            "on-surface-variant": "#44474f",
            "error-container": "#ffdad6",
            "on-error-container": "#93000a",
            "secondary-fixed-dim": "#afc6ff"
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
            "space-lg": "1.5rem"
          },
          fontFamily: {
            "label-default": ["Inter"],
            "headline-h1": ["Inter"],
            "small-regular": ["Inter"],
            "headline-h2": ["Inter"],
            "caption": ["Inter"],
            "headline-h3": ["Inter"],
            "body-medium": ["Inter"],
            "body-default": ["Inter"],
            "headline-h1-mobile": ["Inter"],
            "stat-metric": ["Inter"]
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
            "stat-metric": ["32px", { lineHeight: "40px", fontWeight: "700" }]
          }
        }
      }
    };
  </script>
</head>
<body class="bg-surface font-body-default text-body-default text-on-surface antialiased">
<svg class="inline-defs-container" aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden"></svg>

<!-- Top Fixed Header -->
<header class="fixed top-0 left-64 right-0 h-16 bg-surface-container-lowest border-b border-surface-variant z-40 px-margin flex items-center justify-between gap-space-md">
  <div class="flex items-center gap-space-lg flex-1">
    <nav class="hidden lg:flex items-center gap-space-xs font-small-regular text-small-regular text-on-surface-variant">
      <span class="hover:text-primary-container cursor-pointer">Portal</span>
      <span class="material-symbols-outlined text-[14px] text-outline">chevron_right</span>
      <span class="text-on-surface font-body-medium"><?= htmlspecialchars($breadcrumb_current ?? 'Dashboard') ?></span>
    </nav>
    <div class="relative flex-1 max-w-md">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
      <input class="w-full h-10 pl-9 pr-space-md rounded-lg border border-surface-variant bg-surface-container-low font-body-default text-body-default text-on-surface placeholder:text-on-surface-variant/60 focus:bg-surface-container-lowest focus:border-secondary outline-none transition-colors" placeholder="Search ticket code or title..." type="text">
    </div>
  </div>
  <div class="flex items-center gap-space-md shrink-0">
    <button class="relative p-2 rounded-full text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors cursor-pointer" type="button" title="Notifications">
      <span class="material-symbols-outlined text-[20px]">notifications</span>
      <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-error text-on-error font-caption text-caption flex items-center justify-center font-semibold ring-2 ring-surface-container-lowest shadow-sm leading-none">2</span>
    </button>
    <div class="w-8 h-8 rounded-full bg-surface-container-high text-primary-container font-label-default text-label-default flex items-center justify-center font-semibold select-none" title="<?= htmlspecialchars($userName) ?>">
      <?= htmlspecialchars($userInitials) ?>
    </div>
  </div>
</header>
