<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Campus Helpdesk') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-slate-200 p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Campus Helpdesk</h1>
            <p class="text-sm text-slate-500 mt-1">IT & Facilities Operations Portal</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/auth/login" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Email Address</label>
                <input type="email" name="email" required
                       placeholder="e.g. manager@campus.edu.vn"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Password</label>
                <input type="password" name="password" required
                       placeholder="••••••••"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition shadow-sm">
                Sign In
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-400 space-y-1">
            <p class="font-medium text-slate-500">Test Credentials (Pass: Helpdesk@123):</p>
            <p>• Manager: <code class="text-slate-600">manager@campus.edu.vn</code></p>
            <p>• Technician: <code class="text-slate-600">tech.it1@campus.edu.vn</code></p>
            <p>• Student: <code class="text-slate-600">student1@campus.edu.vn</code></p>
        </div>
    </div>
</body>
</html>