<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= htmlspecialchars($page_title ?? 'Login - Campus Helpdesk') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
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
    .arch-grid-pattern {
      background-image: 
        radial-gradient(rgba(255, 255, 255, 0.12) 1.5px, transparent 1.5px),
        linear-gradient(to right, rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
      background-size: 32px 32px, 64px 64px, 64px 64px;
    }
  </style>
</head>
<body class="blueprint-canvas-bg text-slate-800 min-h-screen flex items-center justify-center px-4 py-6 font-sans antialiased relative selection:bg-blue-600 selection:text-white" style="background-color: rgb(5, 5, 5); background-image: radial-gradient(rgba(255, 255, 255, 0.25) 1.5px, transparent 1.5px); background-size: 24px 24px;">

<!-- Ambient background container -->
<div class="fixed inset-0 pointer-events-none z-0 overflow-hidden"><div class="absolute inset-0" style="display: none;"></div></div>

<main class="w-full max-w-[94vw] lg:w-[1180px] my-auto relative z-10 flex items-center justify-center">
  <!-- Floating Ticket Container with Asymmetrical 40/60 Split -->
  <div class="relative bg-white rounded-3xl shadow-[0_30px_70px_-15px_rgba(11,42,91,0.22),0_15px_30px_-10px_rgba(30,79,163,0.18),0_0_1px_rgba(0,0,0,0.1)] flex flex-col lg:flex-row overflow-hidden border border-white/10 w-full lg:w-[1180px] min-h-[640px]">
    
    <!-- Left Column: 40% Navy Blueprint Ticket Stub -->
    <div class="lg:w-[40%] bg-gradient-to-br from-[#06162e] via-[#092248] to-[#0B2A5B] text-white p-7 lg:p-8 flex flex-col justify-between relative overflow-hidden select-none border-b lg:border-b-0">
      <div class="absolute inset-0 arch-grid-pattern opacity-15 pointer-events-none"></div>
      <div class="absolute -top-12 -left-12 w-64 h-64 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-16 -right-16 w-60 h-60 bg-[#1E4FA3]/25 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.08]">
        <svg class="w-96 h-96 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
          <path d="M12 2L4 5v6.5C4 16.8 7.4 21.2 12 22c4.6-.8 8-5.2 8-10.5V5L12 2z" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M12 6.5v11" stroke-linecap="round"></path>
          <path d="M8 9h8" stroke-linecap="round"></path>
          <path d="M8 12h8" stroke-linecap="round"></path>
          <path d="M9 15h6" stroke-linecap="round"></path>
          <circle cx="12" cy="4.5" r="0.75" fill="currentColor"></circle>
        </svg>
      </div>

      <!-- Left Header -->
      <div class="relative z-10 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-white/10 border border-white/20 shadow-inner text-white shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
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

      <!-- Left Content -->
      <div class="relative z-10 my-auto py-6 flex flex-col items-start gap-3">
        <div class="w-full px-1 py-2 flex flex-col">
          <h1 class="text-2xl font-bold text-white tracking-tight leading-snug">Campus Incident Dispatch</h1>
          <p class="text-sm text-slate-300/80 leading-relaxed mt-2.5 max-w-sm">Direct technical resolution for classrooms, laboratories, and lecture halls.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 pt-1">
          <div class="backdrop-blur-md bg-white/10 border border-white/20 rounded-xl px-3 py-1.5 shadow-sm inline-flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
            <span class="text-[11px] font-semibold tracking-wide text-slate-100">AV &amp; Computer Labs</span>
          </div>
          <div class="backdrop-blur-md bg-white/10 border border-white/20 rounded-xl px-3 py-1.5 shadow-sm inline-flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
            <span class="text-[11px] font-semibold tracking-wide text-slate-100">High-SLA Priority</span>
          </div>
          <div class="backdrop-blur-md bg-white/10 border border-white/20 rounded-xl px-3 py-1.5 shadow-sm inline-flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-[11px] font-semibold tracking-wide text-slate-100">Active Dispatch</span>
          </div>
        </div>
      </div>

      <!-- Left Barcode Footer -->
      <div class="relative z-10 pt-4 border-t border-white/10">
        <div class="bg-white/95 backdrop-blur-sm rounded-xl p-3 flex flex-col items-center justify-center gap-1.5 shadow-sm border border-white/20">
          <div class="w-full flex items-stretch justify-between h-8 px-1">
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
          <div class="text-[10px] font-mono tracking-[0.25em] text-slate-800 font-bold uppercase">* 2026 - CAMPUS - DESK - SSO *</div>
        </div>
      </div>
    </div>

    <!-- Ticket Perforation Cutouts (hidden on small screens) -->
    <div class="hidden lg:flex relative flex-col justify-between items-center w-0 z-20">
      <div class="absolute -top-3.5 -left-3.5 w-7 h-7 rounded-full border border-white/10 shadow-inner" style="background-color: rgb(5, 5, 5);"></div>
      <div class="w-0 h-full border-r-2 border-dashed border-slate-200 my-4"></div>
      <div class="absolute -bottom-3.5 -left-3.5 w-7 h-7 rounded-full border border-white/10 shadow-inner" style="background-color: rgb(5, 5, 5);"></div>
    </div>

    <!-- Right Column: 60% Form Container -->
    <div class="lg:w-[60%] bg-white p-7 lg:p-11 flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-2">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#EAF1FB] text-[#1E4FA3] text-[11px] font-mono font-semibold tracking-wider">
            <svg class="w-3 h-3 text-[#1E4FA3]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            SECURE DISPATCH NODE // PORT 443
          </span>
          <span class="text-xs text-slate-400 font-mono">v2.4-PROD</span>
        </div>
        <h2 class="text-2xl font-bold text-[#0B2A5B] tracking-tight">Service Access Portal</h2>
        <p class="text-xs text-slate-500 mt-1">Verify university credentials to unlock dispatch queue</p>
      </div>

      <!-- Error Alert -->
      <?php if (!empty($error)): ?>
        <div class="mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
          <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <span class="font-medium"><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form action="<?= BASE_URL ?>/auth/login" method="POST" class="space-y-4 my-5">
        <div>
          <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5" for="email">Campus Email</label>
          <div class="relative">
            <input class="w-full h-11 px-4 py-2.5 pr-10 text-sm text-slate-800 bg-[#F9FAFD] rounded-xl border border-slate-200 focus:border-[#1E4FA3] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1E4FA3]/20 transition-all font-medium placeholder:text-slate-400"
                   id="email" name="email" placeholder="username@campus.edu.vn" type="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? 'student1@campus.edu.vn') ?>">
            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 font-mono text-sm">@</div>
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider" for="password">Password</label>
            <a class="text-xs font-semibold text-[#1E4FA3] hover:text-[#0B2A5B] hover:underline transition-colors" href="#" onclick="alert('Vui lòng liên hệ Quản trị viên để đặt lại mật khẩu.')">Forgot password?</a>
          </div>
          <div class="relative">
            <input class="w-full h-11 px-4 py-2.5 pr-10 text-sm text-slate-800 bg-[#F9FAFD] rounded-xl border border-slate-200 focus:border-[#1E4FA3] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1E4FA3]/20 transition-all font-medium placeholder:text-slate-400"
                   id="password" name="password" placeholder="Enter security password" type="password" required
                   value="Helpdesk@123">
            <button aria-label="Show password toggle" id="togglePasswordBtn" type="button"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-700 transition-colors">
              <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"></path>
                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between pt-0.5">
          <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-600">
            <input checked="" class="w-4 h-4 rounded text-[#0B2A5B] border-slate-300 focus:ring-[#1E4FA3] accent-[#0B2A5B]" type="checkbox">
            <span class="font-medium">Remember this terminal</span>
          </label>
          <span class="text-[11px] text-slate-400 font-mono">Session: 8h</span>
        </div>

        <!-- Demo Quick-Fill Accounts Pills -->
        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col gap-1.5">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider font-mono">Demo 1-Click Role Fill</span>
            <span class="text-[10px] text-slate-400 font-mono">Pass: Helpdesk@123</span>
          </div>
          <div class="grid grid-cols-4 gap-1.5">
            <button type="button" onclick="fillAccount('student1@campus.edu.vn')"
                    class="px-2 py-1 text-center rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-blue-700 hover:bg-blue-50 hover:border-blue-300 transition shadow-xs">
              Student
            </button>
            <button type="button" onclick="fillAccount('lecturer1@campus.edu.vn')"
                    class="px-2 py-1 text-center rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-indigo-700 hover:bg-indigo-50 hover:border-indigo-300 transition shadow-xs">
              Lecturer
            </button>
            <button type="button" onclick="fillAccount('tech.it1@campus.edu.vn')"
                    class="px-2 py-1 text-center rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300 transition shadow-xs">
              Technician
            </button>
            <button type="button" onclick="fillAccount('manager@campus.edu.vn')"
                    class="px-2 py-1 text-center rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-amber-700 hover:bg-amber-50 hover:border-amber-300 transition shadow-xs">
              Manager
            </button>
          </div>
        </div>

        <div class="pt-1">
          <button class="w-full h-11 rounded-xl bg-gradient-to-r from-[#0B2A5B] to-[#103B7B] hover:from-[#081F42] hover:to-[#0B2A5B] active:bg-[#07162E] text-white font-bold text-sm transition-all shadow-md shadow-[#0B2A5B]/20 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#1E4FA3] focus:ring-offset-2 group cursor-pointer" type="submit">
            <span>Sign In</span>
            <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
          </button>
        </div>
      </form>

      <!-- Bottom Emergency Hotline & Security Badges -->
      <div class="space-y-2.5 pt-2 border-t border-slate-100">
        <div class="p-2.5 rounded-xl bg-[#F8FAFD] border border-slate-200/70 flex items-center gap-2.5 text-xs text-slate-600">
          <div class="w-6 h-6 rounded-md bg-[#EAF1FB] flex items-center justify-center text-[#1E4FA3] shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </div>
          <div class="text-[11px] leading-tight text-slate-600 truncate">
            <span class="font-semibold text-slate-800">Emergency hotline:</span> IT: <span class="font-medium text-slate-900 font-mono">024 3123 4567</span> · Facilities: <span class="font-medium text-slate-900 font-mono">024 3765 4321</span>
          </div>
        </div>

        <div class="flex items-center justify-center gap-1.5 text-[10px] text-slate-400 font-mono text-center">
          <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path clip-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" fill-rule="evenodd"></path>
          </svg>
          <span>Protected by TLS 1.3 encryption · University SSO Gateway</span>
        </div>
      </div>
    </div>

  </div>
</main>

<script>
  function fillAccount(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'Helpdesk@123';
  }

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
