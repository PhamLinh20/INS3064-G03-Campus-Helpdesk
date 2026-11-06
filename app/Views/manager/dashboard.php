<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'Manager Dashboard') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
  <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-amber-600 text-white flex items-center justify-center font-bold">M</div>
      <div>
        <h1 class="text-base font-bold text-slate-900">Campus Helpdesk — Managers Portal</h1>
        <p class="text-xs text-slate-500">Central Operations & Dispatch Queue</p>
      </div>
    </div>
    <div class="flex items-center gap-4">
      <span class="text-xs px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-semibold"><?= htmlspecialchars($role) ?>: <?= htmlspecialchars($user_name) ?></span>
      <a href="<?= BASE_URL ?>/auth/logout" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Logout</a>
    </div>
  </header>
  <main class="max-w-5xl mx-auto p-6 mt-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
      <h2 class="text-xl font-bold text-slate-900 mb-2">Welcome to Managers Portal, <?= htmlspecialchars($user_name) ?>!</h2>
      <p class="text-sm text-slate-600 mb-6">Trang Quản trị điều phối toàn diện của Điều hành viên. Module đang được xây dựng.</p>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="p-5 rounded-xl bg-slate-50 border border-slate-100">
          <p class="text-xs font-semibold text-slate-500 uppercase">Điều Phối Phiếu</p>
          <p class="text-xs text-slate-400 mt-1">FR-M02: Gán KTV & Tự tính SLA</p>
        </div>
        <div class="p-5 rounded-xl bg-slate-50 border border-slate-100">
          <p class="text-xs font-semibold text-slate-500 uppercase">Giám Sát Quá Hạn</p>
          <p class="text-xs text-slate-400 mt-1">FR-M03: Xử lý Overdue & Từ chối</p>
        </div>
        <div class="p-5 rounded-xl bg-slate-50 border border-slate-100">
          <p class="text-xs font-semibold text-slate-500 uppercase">Quản Trị Danh Mục</p>
          <p class="text-xs text-slate-400 mt-1">FR-M05: CRUD Master Data</p>
        </div>
        <div class="p-5 rounded-xl bg-slate-50 border border-slate-100">
          <p class="text-xs font-semibold text-slate-500 uppercase">Báo Cáo & CSAT</p>
          <p class="text-xs text-slate-400 mt-1">FR-M04: Xuất báo cáo CSV/Excel</p>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
