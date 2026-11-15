<?php
/**
 * View: chi tiết phòng (/master/classroomView?id=)
 * Hiển thị thông tin phòng, ảnh, thiết bị trong phòng và các phiếu đang mở.
 */
if (!defined('APPROOT')) {
    define('APPROOT', dirname(__DIR__, 3));
}

$photos = $photos ?? [];
$assets = $assets ?? [];
$tickets = $tickets ?? [];
$current_nav = $current_nav ?? 'classrooms';
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$isActive = (int) $classroom['is_active'] === 1;
$faulty = 0;
foreach ($assets as $asset) {
    if (in_array($asset['status'], ['Faulty', 'Under_Repair'], true)) {
        $faulty++;
    }
}
if (!$isActive) {
    [$statusLabel, $statusClass] = ['Inactive', 'bg-slate-100 text-slate-500 border-slate-200'];
} elseif ($tickets || $faulty > 0) {
    [$statusLabel, $statusClass] = ['Need Attention', 'bg-amber-50 text-amber-700 border-amber-200'];
} else {
    [$statusLabel, $statusClass] = ['Healthy', 'bg-emerald-50 text-emerald-700 border-emerald-200'];
}
$assetBadge = [
    'Active' => ['Working', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'Faulty' => ['Faulty', 'bg-red-50 text-red-700 border-red-200'],
    'Under_Repair' => ['Under Repair', 'bg-amber-50 text-amber-700 border-amber-200'],
    'Decommissioned' => ['Decommissioned', 'bg-slate-100 text-slate-500 border-slate-200'],
];

require_once APPROOT . '/app/Views/layouts/manager/header.php';
require_once APPROOT . '/app/Views/layouts/manager/sidebar.php';
?>

<div class="pl-64">
  <main class="relative pt-24 pb-12 px-8 w-full min-h-screen bg-background">
    <div class="max-w-[1100px] mx-auto flex flex-col w-full gap-5">

      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <nav class="text-[14px] text-on-surface-variant flex items-center gap-1.5">
            <a href="<?= BASE_URL ?>/master/classrooms" class="hover:text-primary">Classroom Management</a>
            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            <span class="text-on-surface">Room <?= $e($classroom['room_number']) ?></span>
          </nav>
          <div class="mt-2 flex items-center gap-3">
            <h1 class="text-[28px] font-bold text-on-surface tracking-tight">Room <?= $e($classroom['room_number']) ?></h1>
            <span class="inline-flex px-2.5 py-1 rounded-full border text-[11px] font-semibold <?= $statusClass ?>"><?= $statusLabel ?></span>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/master/classroomEdit?id=<?= (int) $classroom['classroom_id'] ?>"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl shadow-sm hover:bg-[#00225c] text-[14px] font-medium">
          <span class="material-symbols-outlined text-[18px]">edit</span> Edit Classroom
        </a>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ([
            ['Building', $classroom['building']],
            ['Floor', 'Floor ' . (int) $classroom['floor']],
            ['Open Tickets', count($tickets)],
            ['Assets', count($assets) . ' (' . $faulty . ' need repair)'],
        ] as [$label, $value]): ?>
          <div class="bg-surface-container-lowest rounded-2xl p-4 border border-surface-variant/30 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant"><?= $label ?></p>
            <p class="mt-1.5 text-[16px] font-semibold text-on-surface"><?= $e($value) ?></p>
          </div>
        <?php endforeach; ?>
      </div>

      <section class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm p-5">
        <h2 class="text-[16px] font-semibold text-[#0b3d91]">Room Photos</h2>
        <?php if ($photos): ?>
          <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach ($photos as $photo): ?>
              <a href="<?= BASE_URL . '/' . $e($photo['file_path']) ?>" target="_blank" class="block rounded-lg overflow-hidden border border-outline-variant">
                <img src="<?= BASE_URL . '/' . $e($photo['file_path']) ?>" alt="Room photo" class="w-full h-36 object-cover hover:opacity-90">
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="mt-2 text-[14px] text-on-surface-variant">No photos yet. Add some from Edit Classroom.</p>
        <?php endif; ?>
      </section>

      <section class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm overflow-hidden">
        <h2 class="px-5 pt-5 pb-3 text-[16px] font-semibold text-[#0b3d91]">Equipment in this room</h2>
        <table class="w-full text-left">
          <thead>
            <tr class="bg-surface-container-low text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
              <th class="px-5 py-3">Asset Code</th><th class="px-5 py-3">Asset Name</th>
              <th class="px-5 py-3">Category</th><th class="px-5 py-3">Faults</th><th class="px-5 py-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-variant/30">
            <?php foreach ($assets as $asset): ?>
              <?php [$aLabel, $aClass] = $assetBadge[$asset['status']] ?? [$asset['status'], 'bg-slate-100 text-slate-600 border-slate-200']; ?>
              <tr>
                <td class="px-5 py-3 text-[13px] font-mono"><?= $e($asset['asset_code']) ?></td>
                <td class="px-5 py-3 text-[13px] font-medium"><?= $e($asset['asset_name']) ?></td>
                <td class="px-5 py-3 text-[13px] text-on-surface-variant"><?= $e($asset['category_name'] ?? '—') ?></td>
                <td class="px-5 py-3 text-[13px]"><?= (int) $asset['fault_count'] ?></td>
                <td class="px-5 py-3"><span class="inline-flex px-2.5 py-1 rounded-full border text-[11px] font-semibold <?= $aClass ?>"><?= $e($aLabel) ?></span></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$assets): ?>
              <tr><td colspan="5" class="px-5 py-8 text-center text-[14px] text-on-surface-variant">No equipment recorded for this room.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <section class="bg-surface-container-lowest rounded-2xl border border-surface-variant/30 shadow-sm overflow-hidden">
        <h2 class="px-5 pt-5 pb-3 text-[16px] font-semibold text-[#0b3d91]">Open incidents</h2>
        <table class="w-full text-left">
          <thead>
            <tr class="bg-surface-container-low text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">
              <th class="px-5 py-3">Ticket</th><th class="px-5 py-3">Title</th>
              <th class="px-5 py-3">Priority</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Reported</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-variant/30">
            <?php foreach ($tickets as $ticket): ?>
              <tr>
                <td class="px-5 py-3 text-[13px] font-semibold text-[#1d4ed8]"><?= $e($ticket['ticket_code']) ?></td>
                <td class="px-5 py-3 text-[13px]"><?= $e($ticket['title']) ?></td>
                <td class="px-5 py-3 text-[13px]"><?= $e($ticket['priority']) ?></td>
                <td class="px-5 py-3 text-[13px]"><?= $e(str_replace('_', ' ', $ticket['status'])) ?></td>
                <td class="px-5 py-3 text-[13px] text-on-surface-variant"><?= $e(date('d M Y, H:i', strtotime($ticket['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$tickets): ?>
              <tr><td colspan="5" class="px-5 py-8 text-center text-[14px] text-on-surface-variant">No open tickets for this room.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </div>
  </main>
</div>

<?php require_once APPROOT . '/app/Views/layouts/manager/footer.php'; ?>
