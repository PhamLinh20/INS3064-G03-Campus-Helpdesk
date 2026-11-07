<?php
/**
 * View: Login - Campus Helpdesk
 * Tương thích cả khi chạy qua MVC router (Apache/XAMPP) hoặc mở trực tiếp qua VS Code PHP Server (port 3000)
 */
if (!defined('BASE_URL')) {
    $configFile = dirname(__DIR__, 3) . '/config/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        define('BASE_URL', '');
    }
}
$loginAction = defined('BASE_URL') && !empty(BASE_URL) ? BASE_URL . '/auth/login' : 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= htmlspecialchars($page_title ?? 'Login - Campus Helpdesk') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            mono: ['JetBrains Mono', 'monospace'],
          },
          colors: {
            brand: {
              navy: '#0B2A5B',
              navyDark: '#071B3D',
              blue: '#1E4FA3',
              pale: '#EAF1FB',
              text: '#0B1F44',
              muted: '#5B6B8C',
              border: '#E3E9F4',
              bg: '#F6F8FC',
            }
          }
        }
      }
    }
  </script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    
    /* Left Panel subtle dotted pattern + rich navy gradient */
    .left-panel-bg {
      background-color: #071B3D;
      background-image: 
        radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px),
        linear-gradient(160deg, #06162E 0%, #092248 50%, #0B2A5B 100%);
      background-size: 24px 24px, 100% 100%;
    }

    /* Custom Form & Input Styles for WCAG Contrast & Premium Feel */
    .custom-input {
      background-color: #ffffff;
      border: 1px solid #D5DCE8;
      height: 50px;
      color: #0F172A;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .custom-input::placeholder {
      color: #8B98AD;
      font-weight: 400;
    }
    .custom-input:focus {
      outline: none;
      border-color: #0B2A5B;
      box-shadow: 0 0 0 3px rgba(11, 42, 91, 0.12);
      background-color: #ffffff;
    }

    /* Entrance Animation */
    @keyframes cardFadeInUp {
      from {
        opacity: 0;
        transform: translateY(10px) scale(0.99);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    .ticket-card {
      animation: cardFadeInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Hole punch inner shadow for ticket stub realism */
    .punch-hole {
      background-color: rgb(5, 5, 5);
      box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.9), inset 0 0 0 1px rgba(255, 255, 255, 0.1);
    }
  </style>
</head>
<body class="text-slate-800 min-h-screen flex items-center justify-center p-4 font-sans antialiased relative selection:bg-blue-600 selection:text-white" style="background-color: rgb(5, 5, 5); background-image: radial-gradient(rgba(255, 255, 255, 0.2) 1.5px, transparent 1.5px); background-size: 24px 24px;">

<!-- Background decorative ambient blur -->
<div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
  <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
  <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>
</div>

<main class="w-full max-w-[1080px] my-auto relative z-10 flex items-center justify-center">
  <!-- Floating Ticket Container with Asymmetrical 40/60 Split -->
  <div class="ticket-card relative bg-white rounded-3xl shadow-[0_30px_70px_-15px_rgba(11,42,91,0.25),0_15px_30px_-10px_rgba(30,79,163,0.18)] flex flex-col md:flex-row overflow-hidden border border-white/10 w-full" style="min-height: 580px;">
    
    <!-- Left Column: 40% Navy Blueprint Ticket Stub -->
    <div class="md:w-[40%] left-panel-bg text-white p-7 lg:p-9 flex flex-col justify-between relative overflow-hidden select-none border-b md:border-b-0">
      <div class="absolute -top-12 -left-12 w-64 h-64 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-16 -right-16 w-60 h-60 bg-[#1E4FA3]/20 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Left Header: Branding + System Status -->
      <div class="relative z-10 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 border border-white/20 shadow-inner text-white shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
          <div>
            <div class="text-sm font-bold text-white tracking-tight leading-tight">Campus Helpdesk</div>
            <div class="text-[11px] text-blue-200/80 font-medium">IT &amp; Facilities Operations</div>
          </div>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 border border-emerald-400/30 text-[10px] font-semibold text-emerald-300 font-mono">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>SYSTEM OPERATIONAL
        </div>
      </div>

      <!-- Left Body: Incident Headline with Cyan Accent -->
      <div class="relative z-10 py-8 md:py-10 flex flex-col items-start">
        <h1 class="text-3xl lg:text-[36px] font-extrabold text-white tracking-tight leading-[1.18]">
          Campus Incident <br><span class="text-sky-400">Dispatch</span>
        </h1>
        <p class="text-sm text-slate-300/80 leading-relaxed mt-4 max-w-[340px]">
          Direct technical resolution for classrooms, laboratories, and lecture halls across all campus facilities.
        </p>
      </div>

      <!-- Left Barcode Footer: Proportionate, clean -->
      <div class="relative z-10">
        <div class="bg-white/95 backdrop-blur-sm rounded-xl p-2.5 flex flex-col items-center justify-center gap-1 shadow-sm border border-white/20">
          <div class="w-full flex items-stretch justify-between h-5 px-1">
            <div class="w-[3px] bg-slate-900 rounded-sm"></div>
            <div class="w-[1px] bg-slate-900"></div>
            <div class="w-[4px] bg-slate-900 rounded-sm"></div>
            <div class="w-[1px] bg-slate-900"></div>
            <div class="w-[2px] bg-slate-900"></div>
            <div class="w-[5px] bg-slate-900 rounded-sm"></div>
            <div class="w-[1px] bg-slate-900"></div>
            <div class="w-[3px] bg-slate-900"></div>
            <div class="w-[2px] bg-slate-900"></div>
            <div class="w-[4px] bg-slate-900 rounded-sm"></div>
            <div class="w-[1px] bg-slate-900"></div>
            <div class="w-[3px] bg-slate-900"></div>
            <div class="w-[2px] bg-slate-900"></div>
            <div class="w-[5px] bg-slate-900 rounded-sm"></div>
            <div class="w-[1px] bg-slate-900"></div>
            <div class="w-[2px] bg-slate-900"></div>
            <div class="w-[4px] bg-slate-900 rounded-sm"></div>
            <div class="w-[1px] bg-slate-900"></div>
            <div class="w-[3px] bg-slate-900"></div>
            <div class="w-[1px] bg-slate-900"></div>
            <div class="w-[4px] bg-slate-900 rounded-sm"></div>
            <div class="w-[2px] bg-slate-900"></div>
            <div class="w-[1px] bg-slate-900"></div>
            <div class="w-[3px] bg-slate-900 rounded-sm"></div>
          </div>
          <div class="text-[9px] font-mono tracking-[0.25em] text-slate-500 font-semibold uppercase">* 2026 - CAMPUS - DESK - SSO *</div>
        </div>
      </div>
    </div>

    <!-- Ticket Perforation Cutouts with depth shadow -->
    <div class="relative hidden md:flex flex-col justify-between items-center w-0 z-20">
      <div class="punch-hole absolute -top-3.5 -left-3.5 w-7 h-7 rounded-full border border-white/10"></div>
      <div class="w-0 h-full border-r-2 border-dashed border-slate-200 my-4"></div>
      <div class="punch-hole absolute -bottom-3.5 -left-3.5 w-7 h-7 rounded-full border border-white/10"></div>
    </div>

    <!-- Right Column: 60% Form Container -->
    <div class="md:w-[60%] bg-white p-7 lg:p-10 flex flex-col justify-between">
      
      <!-- Top Section: Header & Form -->
      <div>
        <!-- Header -->
        <div class="mb-5">
          <h2 class="text-2xl lg:text-[32px] font-extrabold text-[#0B2A5B] tracking-tight leading-tight">Service Access Portal</h2>
          <p class="text-sm font-medium text-slate-500 mt-1.5">Verify university credentials to unlock dispatch queue</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
          <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5">
            <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="font-medium"><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= htmlspecialchars($loginAction) ?>" method="POST" class="space-y-4">
          <!-- Campus Email -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5" for="email">CAMPUS EMAIL</label>
            <div class="relative">
              <input class="custom-input w-full px-4 py-2.5 pr-10 text-sm text-slate-900 rounded-xl font-medium"
                     id="email" name="email" placeholder="username@campus.edu.vn" type="email" required
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400 font-mono text-sm font-semibold">@</div>
            </div>
          </div>

          <!-- Password -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider" for="password">PASSWORD</label>
              <a class="text-xs font-semibold text-[#1E4FA3] hover:text-[#0B2A5B] hover:underline transition-colors" href="#" onclick="alert('Vui lòng liên hệ Quản trị viên phòng IT hoặc Hotline để đặt lại mật khẩu.')">Forgot password?</a>
            </div>
            <div class="relative">
              <input class="custom-input w-full px-4 py-2.5 pr-12 text-sm text-slate-900 rounded-xl font-medium"
                     id="password" name="password" placeholder="Enter security password" type="password" required value="">
              <button aria-label="Show password toggle" id="togglePasswordBtn" type="button"
                      class="absolute inset-y-0 right-0 w-11 h-full flex items-center justify-center text-slate-400 hover:text-slate-700 transition-colors cursor-pointer">
                <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Checkbox: Remember this terminal -->
          <div class="flex items-center justify-between pt-0.5">
            <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-700 font-medium">
              <input checked="" class="w-4 h-4 rounded text-[#0B2A5B] border-slate-300 focus:ring-[#1E4FA3] accent-[#0B2A5B]" type="checkbox">
              <span>Remember this terminal</span>
            </label>
          </div>

          <!-- Submit Button -->
          <div class="pt-1">
            <button class="w-full h-12 rounded-xl bg-[#0B2A5B] hover:bg-[#071B3D] active:scale-[0.99] text-white font-bold text-sm transition-all shadow-md shadow-[#0B2A5B]/20 flex items-center justify-center gap-2 focus:outline-none focus:ring-4 focus:ring-[#0B2A5B]/20 group cursor-pointer" type="submit">
              <span>Sign In</span>
              <span class="transition-transform duration-150 group-hover:translate-x-1">&rarr;</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Bottom Emergency Hotline & Security Badges -->
      <div class="space-y-2 pt-4 mt-4 border-t border-slate-100">
        <!-- Hotline with clickable tel: links -->
        <div class="p-2.5 rounded-xl bg-[#F8FAFD] border border-slate-200/70 flex items-center gap-2.5 text-xs text-slate-600">
          <div class="w-6 h-6 rounded-md bg-[#EAF1FB] flex items-center justify-center text-[#1E4FA3] shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </div>
          <div class="text-[11px] leading-tight text-slate-600 truncate flex items-center gap-3">
            <span class="font-semibold text-slate-800">Hotline:</span>
            <span>IT: <a href="tel:02431234567" class="font-semibold text-slate-900 font-mono hover:text-[#1E4FA3] transition">024 3123 4567</a></span>
            <span class="text-slate-300">|</span>
            <span>Facilities: <a href="tel:02437654321" class="font-semibold text-slate-900 font-mono hover:text-[#1E4FA3] transition">024 3765 4321</a></span>
          </div>
        </div>

        <div class="flex items-center justify-center gap-1.5 text-[11px] text-slate-500 text-center">
          <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path clip-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" fill-rule="evenodd"></path>
          </svg>
          <span>Protected by University SSO</span>
        </div>
      </div>
    </div>

  </div>
</main>

<script>
  const togglePasswordBtn = document.getElementById('togglePasswordBtn');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');

  togglePasswordBtn.addEventListener('click', function() {
    const isPassword = passwordInput.getAttribute('type') === 'password';
    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
    if (isPassword) {
      eyeIcon.innerHTML = `
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
      `;
    } else {
      eyeIcon.innerHTML = `
        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"></path>
        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"></path>
      `;
    }
  });
</script>

</body>
</html>
