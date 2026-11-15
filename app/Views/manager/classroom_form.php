<?php
/**
 * View: Add New Classroom (/master/classroomCreate) và Edit Classroom (/master/classroomEdit?id=)
 * Trường: Room name (vd A1-201), Room Photos (tối đa 10 ảnh, 5MB/ảnh), Status.
 * Tòa nhà và tầng được suy ra từ tên phòng (ô Location chỉ để xem), không đổi cấu trúc CSDL.
 * Ảnh lưu ở public/uploads/classrooms/{classroom_id}/.
 */
if (!defined('APPROOT')) {
    define('APPROOT', dirname(__DIR__, 3));
}

$mode = $mode ?? 'create';
$isEdit = $mode === 'update' && is_array($classroom ?? null);
$photos = $photos ?? [];
$form_error = $form_error ?? null;
$current_nav = $current_nav ?? 'classrooms';
$breadcrumb_current = $breadcrumb_current ?? ($isEdit ? 'Edit Classroom' : 'Add New Classroom');

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Giá trị form: ưu tiên dữ liệu vừa gửi bị lỗi, sau đó tới dữ liệu phòng đang sửa.
$values = [
    'room_number' => $isEdit ? $classroom['room_number'] : '',
    'is_active' => $isEdit ? (string) (int) $classroom['is_active'] : '1',
];
if (is_array($form_error) && is_array($form_error['values'] ?? null)) {
    $values = array_merge($values, $form_error['values']);
}

$action = $isEdit
    ? BASE_URL . '/master/classroomEdit?id=' . (int) $classroom['classroom_id']
    : BASE_URL . '/master/classroomCreate';
$title = $isEdit ? 'Edit Classroom' : 'Add New Classroom';
$remaining = 10 - count($photos);

require_once APPROOT . '/app/Views/layouts/manager/header.php';
require_once APPROOT . '/app/Views/layouts/manager/sidebar.php';
?>

<div class="pl-64">
  <main class="relative pt-24 pb-12 px-8 w-full min-h-screen bg-background">
    <div class="max-w-[1000px] mx-auto flex flex-col w-full gap-5">

      <div>
        <nav class="text-[14px] text-on-surface-variant flex items-center gap-1.5">
          <a href="<?= BASE_URL ?>/master/classrooms" class="hover:text-primary">Classroom Management</a>
          <span class="material-symbols-outlined text-[16px]">chevron_right</span>
          <span class="text-on-surface"><?= $title ?></span>
        </nav>
        <h1 class="mt-2 text-[28px] font-bold text-on-surface tracking-tight"><?= $title ?></h1>
      </div>

      <?php if (is_array($form_error) && !empty($form_error['errors'])): ?>
        <div class="px-4 py-3 rounded-xl border bg-error-container border-error/20 text-on-error-container" role="alert">
          <div class="flex items-center gap-2 font-semibold text-[14px]">
            <span class="material-symbols-outlined text-[20px]">error</span>
            <span>Could not save the classroom</span>
          </div>
          <ul class="mt-2 ml-7 list-disc text-[14px] leading-6">
            <?php foreach ($form_error['errors'] as $error): ?>
              <li><?= $e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= $action ?>" enctype="multipart/form-data"
            class="bg-surface-container-lowest rounded-xl border border-surface-variant/40 shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 bg-[#0b5394] text-white">
          <span class="material-symbols-outlined text-[22px]">apartment</span>
          <h2 class="text-[16px] font-semibold">Classroom Configuration</h2>
        </div>

        <div class="px-6 py-5 flex flex-col gap-6">
          <!-- Thông tin phòng -->
          <section>
            <h3 class="text-[16px] font-semibold text-[#0b3d91]">Classroom Information</h3>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
              <label class="flex flex-col gap-1.5">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Room Name *</span>
                <input id="room-name" name="room_number" type="text" required maxlength="50"
                       value="<?= $e($values['room_number']) ?>" placeholder="Enter classroom name"
                       class="h-11 px-3 rounded-lg border border-outline-variant bg-white text-[14px] text-on-surface placeholder:text-on-surface-variant/70 focus:border-primary outline-none">
                <span id="room-hint" class="text-[12px] text-on-surface-variant">Format: A1-201 (building A1, floor 2).</span>
              </label>
              <label class="flex flex-col gap-1.5">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Location</span>
                <input id="room-location" type="text" readonly tabindex="-1" placeholder="Filled from room name"
                       class="h-11 px-3 rounded-lg border border-outline-variant bg-surface-container-low text-[14px] text-on-surface-variant outline-none cursor-default">
              </label>
            </div>
          </section>

          <!-- Ảnh phòng -->
          <section>
            <h3 class="text-[16px] font-semibold text-[#0b3d91]">Room Photos</h3>

            <?php if ($photos): ?>
              <p class="mt-3 text-[12px] text-on-surface-variant">Tick a photo to remove it when you save.</p>
              <div class="mt-2 grid grid-cols-3 md:grid-cols-5 gap-3">
                <?php foreach ($photos as $photo): ?>
                  <label class="relative block rounded-lg overflow-hidden border border-outline-variant cursor-pointer group">
                    <img src="<?= BASE_URL . '/' . $e($photo['file_path']) ?>" alt="Room photo" class="w-full h-24 object-cover">
                    <span class="absolute top-1.5 right-1.5 bg-white/90 rounded px-1.5 py-0.5 flex items-center gap-1 text-[11px] text-red-600 font-medium">
                      <input type="checkbox" name="remove_photos[]" value="<?= $e($photo['name']) ?>" class="accent-red-600">
                      Remove
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <label id="drop-zone" for="photo-input"
                   class="mt-4 flex flex-col items-center justify-center gap-2 h-36 rounded-xl border-2 border-dashed border-outline-variant bg-white text-center cursor-pointer hover:border-primary hover:bg-surface-container-low transition-colors">
              <span class="material-symbols-outlined text-[28px] text-on-surface">photo_camera</span>
              <span class="text-[13px] font-semibold text-on-surface">Click to upload or drag and drop multiple images</span>
              <span class="text-[12px] text-on-surface-variant">PNG, JPG or GIF (max 5MB each, up to <?= max(0, $remaining) ?> photos)</span>
            </label>
            <input id="photo-input" name="photos[]" type="file" accept="image/png,image/jpeg,image/gif" multiple class="hidden">
            <p id="photo-error" class="hidden mt-2 text-[13px] text-red-600"></p>
            <div id="photo-preview" class="mt-3 grid grid-cols-3 md:grid-cols-5 gap-3"></div>
          </section>

          <!-- Trạng thái -->
          <section>
            <h3 class="text-[16px] font-semibold text-[#0b3d91]">Room Status</h3>
            <label class="mt-4 flex flex-col gap-1.5 md:w-1/2 md:pr-2">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-on-surface">Status *</span>
              <select name="is_active" class="h-11 px-3 rounded-lg border border-outline-variant bg-white text-[14px] text-on-surface focus:border-primary outline-none">
                <option value="1" <?= $values['is_active'] === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $values['is_active'] === '0' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </label>
          </section>

          <div class="flex items-center justify-end gap-4 pt-2">
            <a href="<?= BASE_URL ?>/master/classrooms" class="px-5 py-2.5 text-[14px] font-medium text-on-surface hover:text-primary">Cancel</a>
            <button type="submit" class="px-8 py-2.5 rounded-lg bg-primary text-white text-[14px] font-medium shadow-sm hover:bg-[#00225c] transition-colors">
              <?= $isEdit ? 'Save Changes' : 'Create Classroom' ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </main>
</div>

<script>
  (function () {
    const MAX_FILES = <?= max(0, $remaining) ?>;
    const MAX_BYTES = 5 * 1024 * 1024;
    const TYPES = ['image/png', 'image/jpeg', 'image/gif'];
    const input = document.getElementById('photo-input');
    const zone = document.getElementById('drop-zone');
    const preview = document.getElementById('photo-preview');
    const errorBox = document.getElementById('photo-error');
    const roomName = document.getElementById('room-name');
    const roomHint = document.getElementById('room-hint');
    const roomLocation = document.getElementById('room-location');
    let files = [];

    // Gợi ý tòa nhà / tầng ngay khi gõ tên phòng
    function updateHint() {
      const value = roomName.value.replace(/\s+/g, '').toUpperCase();
      const m = value.match(/^([A-Z]+\d*)-(\d{3,4})$/);
      roomLocation.value = m ? 'Building ' + m[1] + ' · Floor ' + Math.floor(Number(m[2]) / 100) : '';
      if (!value) {
        roomHint.textContent = 'Format: A1-201 (building A1, floor 2).';
        roomHint.className = 'text-[12px] text-on-surface-variant';
      } else if (m) {
        roomHint.textContent = 'Building ' + m[1] + ', Floor ' + Math.floor(Number(m[2]) / 100);
        roomHint.className = 'text-[12px] text-emerald-700';
      } else {
        roomHint.textContent = 'Use the format A1-201.';
        roomHint.className = 'text-[12px] text-red-600';
      }
    }
    roomName.addEventListener('input', updateHint);
    updateHint();

    function sync() {
      const dt = new DataTransfer();
      files.forEach(function (f) { dt.items.add(f); });
      input.files = dt.files;
      preview.innerHTML = '';
      files.forEach(function (file, index) {
        const box = document.createElement('div');
        box.className = 'relative rounded-lg overflow-hidden border border-outline-variant';
        const img = document.createElement('img');
        img.className = 'w-full h-24 object-cover';
        img.src = URL.createObjectURL(file);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'absolute top-1 right-1 w-6 h-6 rounded-full bg-white/90 text-red-600 text-[14px] leading-6';
        remove.textContent = '×';
        remove.addEventListener('click', function () { files.splice(index, 1); sync(); });
        box.appendChild(img);
        box.appendChild(remove);
        preview.appendChild(box);
      });
    }

    function addFiles(list) {
      const problems = [];
      Array.from(list).forEach(function (file) {
        if (TYPES.indexOf(file.type) === -1) { problems.push(file.name + ' is not PNG, JPG or GIF.'); return; }
        if (file.size > MAX_BYTES) { problems.push(file.name + ' is larger than 5MB.'); return; }
        if (files.length >= MAX_FILES) { problems.push('You can add up to ' + MAX_FILES + ' photos.'); return; }
        files.push(file);
      });
      errorBox.textContent = problems.join(' ');
      errorBox.classList.toggle('hidden', problems.length === 0);
      sync();
    }

    input.addEventListener('change', function () { addFiles(input.files); });
    ['dragenter', 'dragover'].forEach(function (name) {
      zone.addEventListener(name, function (event) { event.preventDefault(); zone.classList.add('border-primary'); });
    });
    ['dragleave', 'drop'].forEach(function (name) {
      zone.addEventListener(name, function (event) { event.preventDefault(); zone.classList.remove('border-primary'); });
    });
    zone.addEventListener('drop', function (event) { addFiles(event.dataTransfer.files); });
  })();
</script>

<?php require_once APPROOT . '/app/Views/layouts/manager/footer.php'; ?>
