<?php
/**
 * ==============================================================================
 * MASTER DATA CONTROLLER
 * URL:
 * - /master/classrooms  Phòng học (file mẫu)
 * - /master/services    Danh mục lỗi / dịch vụ (ticket_categories)
 * - /master/users       Tài khoản
 * Chỉ bốn thao tác: xem bảng, thêm, sửa thông tin, đổi is_active (0/1).
 * Hồ sơ cá nhân nằm ở /auth/profile vì URL thuộc AuthController.
 * ==============================================================================
 */
require_once APPROOT . '/app/Core/AuthMiddleware.php';

class MasterDataController extends Controller {
    private const PRIORITIES = ['Low', 'Medium', 'High', 'Critical'];
    private const ROLES = ['Manager', 'Technician', 'Lecturer', 'Student'];
    private const MAX_PHOTOS = 10;
    private const MAX_PHOTO_BYTES = 5 * 1024 * 1024;

    private ClassroomModel $classroomModel;
    private CategoryModel $categoryModel;
    private UserModel $userModel;

    public function __construct() {
        AuthMiddleware::requireRole(['Manager']);
        $this->classroomModel = $this->model('ClassroomModel');
        $this->categoryModel = $this->model('CategoryModel');
        $this->userModel = $this->model('UserModel');
    }

    public function index(): void {
        header('Location: ' . BASE_URL . '/master/classrooms');
        exit;
    }

    public function classrooms(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleClassroomsPost();
        }

        $this->render('manager/classrooms', 'classrooms', 'Classrooms Management', 'classrooms', [
            'rows' => $this->classroomModel->all(),
            'stats' => $this->classroomModel->stats(),
            'assets' => $this->classroomModel->assets(),
        ]);
    }

    public function services(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleServicesPost();
        }

        $this->render('manager/services', 'services', 'Services Management', 'services', [
            'rows' => $this->categoryModel->all(),
            'departments' => $this->categoryModel->departmentsFull(),
        ]);
    }

    public function users(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleUsersPost();
        }

        $this->render('manager/users', 'users', 'Users Management', 'users', [
            'rows' => $this->userModel->all(),
            'departments' => $this->categoryModel->departments(),
            'current_user_id' => (int) ($_SESSION['user_id'] ?? 0),
        ]);
    }

    /**
     * /master/classroomCreate  Trang "Add New Classroom".
     */
    public function classroomCreate(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->saveClassroom(null);
        }

        $this->render('manager/classroom_form', 'classroom_form', 'Add New Classroom', 'classrooms', [
            'mode' => 'create',
            'classroom' => null,
            'photos' => [],
        ]);
    }

    /**
     * /master/classroomEdit?id={id}  Sửa thông tin phòng, thêm/xóa ảnh.
     */
    public function classroomEdit(): void {
        $classroom = $this->requestedClassroom();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->saveClassroom($classroom);
        }

        $this->render('manager/classroom_form', 'classroom_form', 'Edit Classroom', 'classrooms', [
            'mode' => 'update',
            'classroom' => $classroom,
            'photos' => $this->roomPhotos((int) $classroom['classroom_id']),
        ]);
    }

    /**
     * /master/classroomView?id={id}  Xem chi tiết phòng: ảnh, thiết bị, phiếu đang mở.
     */
    public function classroomView(): void {
        $classroom = $this->requestedClassroom();
        $id = (int) $classroom['classroom_id'];

        $this->render('manager/classroom_view', 'classroom_view', 'Room ' . $classroom['room_number'], 'classrooms', [
            'classroom' => $classroom,
            'photos' => $this->roomPhotos($id),
            'assets' => $this->classroomModel->assets($id),
            'tickets' => $this->classroomModel->openTickets($id),
        ]);
    }

    /**
     * POST trên trang danh sách: chỉ còn đổi trạng thái (nút thùng rác = ngừng sử dụng phòng).
     * Không xóa hẳn để các phiếu đã gắn với phòng vẫn hợp lệ.
     */
    private function handleClassroomsPost(): void {
        $action = $this->postedAction();

        try {
            if ($action === 'toggle') {
                $recordId = $this->postedId();
                $existing = $recordId === null ? null : $this->classroomModel->find($recordId);
                if (!$existing) {
                    $this->flash('error', 'That classroom no longer exists.', 'classrooms');
                }
                $next = (int) $existing['is_active'] === 1 ? 0 : 1;
                $this->classroomModel->setActive($recordId, $next);
                $verb = $next === 1 ? 'reactivated' : 'deactivated';
                $this->flash('success', 'Room ' . $existing['room_number'] . ' was ' . $verb . '.', 'classrooms');
            }

            $this->flash('error', 'That action is not available on this page.', 'classrooms');
        } catch (PDOException $e) {
            $this->flash('error', $this->saveError($e), 'classrooms');
        }
    }

    /**
     * Lưu form thêm/sửa phòng (kèm ảnh). $existing = null nghĩa là thêm mới.
     */
    private function saveClassroom(?array $existing): void {
        $isCreate = $existing === null;
        $page = 'classroom_form';
        $back = $isCreate
            ? '/master/classroomCreate'
            : '/master/classroomEdit?id=' . (int) $existing['classroom_id'];

        $parsed = $this->classroomInput();
        $recordId = $isCreate ? null : (int) $existing['classroom_id'];

        if (!$parsed['errors'] && $this->classroomModel->roomNumberTaken($parsed['room_number'], $recordId)) {
            $parsed['errors'][] = 'Room ' . $parsed['room_number'] . ' already exists.';
        }

        $removeIds = [];
        if (!$isCreate) {
            foreach ((array) ($_POST['remove_photos'] ?? []) as $raw) {
                $name = basename((string) $raw);
                if (preg_match('/^[a-z0-9_]+\\.(png|jpg|gif)$/', $name)) {
                    $removeIds[] = $name;
                }
            }
        }

        $uploads = $this->collectPhotoUploads($parsed['errors']);
        $currentCount = $isCreate ? 0 : count($this->roomPhotos($recordId));
        if ($currentCount - count($removeIds) + count($uploads) > self::MAX_PHOTOS) {
            $parsed['errors'][] = 'A room can have at most ' . self::MAX_PHOTOS . ' photos.';
        }

        if ($parsed['errors']) {
            $_SESSION['master_form_error'] = [
                'page' => $page,
                'action' => $isCreate ? 'create' : 'update',
                'values' => $parsed['values'],
                'errors' => array_values($parsed['errors']),
            ];
            $this->redirect($back);
        }

        try {
            if ($isCreate) {
                $recordId = $this->classroomModel->insert(
                    $parsed['room_number'], $parsed['building'], (int) $parsed['floor'], (int) $parsed['is_active']
                );
            } else {
                $this->classroomModel->updateInfo(
                    $recordId, $parsed['room_number'], $parsed['building'], (int) $parsed['floor'], (int) $parsed['is_active']
                );
                foreach ($removeIds as $name) {
                    $file = $this->photoDir($recordId) . '/' . $name;
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }

            $saved = $this->storePhotos($recordId, $uploads);
        } catch (PDOException $e) {
            $_SESSION['master_form_error'] = [
                'page' => $page,
                'action' => $isCreate ? 'create' : 'update',
                'values' => $parsed['values'],
                'errors' => [$this->saveError($e)],
            ];
            $this->redirect($back);
        }

        $message = 'Room ' . $parsed['room_number'] . ($isCreate ? ' was created' : ' was updated');
        if ($saved > 0) {
            $message .= ' with ' . $saved . ' new photo' . ($saved > 1 ? 's' : '');
        }
        $this->flash('success', $message . '.', 'classrooms');
    }

    private function requestedClassroom(): array {
        $raw = (string) ($_GET['id'] ?? '');
        $classroom = ctype_digit($raw) ? $this->classroomModel->find((int) $raw) : null;
        if (!$classroom) {
            $this->flash('error', 'That classroom no longer exists.', 'classrooms');
        }
        return $classroom;
    }

    /**
     * Kiểm tra các file ảnh gửi lên (PNG/JPG/GIF, tối đa 5MB mỗi ảnh).
     * @param array<int, string> $errors
     * @return array<int, array{tmp: string, ext: string}>
     */
    private function collectPhotoUploads(array &$errors): array {
        $files = $_FILES['photos'] ?? null;
        if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif'];
        $valid = [];
        foreach ($files['name'] as $i => $name) {
            $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $label = htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8');
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                $errors[] = $label . ' is larger than the server allows.';
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = $label . ' could not be uploaded.';
                continue;
            }
            $tmp = (string) $files['tmp_name'][$i];
            if ((int) $files['size'][$i] > self::MAX_PHOTO_BYTES) {
                $errors[] = $label . ' is larger than 5MB.';
                continue;
            }
            $info = @getimagesize($tmp);
            $mime = is_array($info) ? ($info['mime'] ?? '') : '';
            if (!isset($allowed[$mime])) {
                $errors[] = $label . ' is not a PNG, JPG or GIF image.';
                continue;
            }
            $valid[] = ['tmp' => $tmp, 'ext' => $allowed[$mime]];
        }
        return $valid;
    }

    /**
     * Ảnh phòng lưu theo thư mục public/uploads/classrooms/{classroom_id}/ (không cần bảng CSDL).
     */
    private function photoDir(int $classroomId): string {
        return APPROOT . '/public/uploads/classrooms/' . $classroomId;
    }

    /**
     * @return array<int, array{name: string, file_path: string}>
     */
    private function roomPhotos(int $classroomId): array {
        $files = glob($this->photoDir($classroomId) . '/*') ?: [];
        $files = array_filter($files, static fn ($f) => preg_match('/\\.(png|jpg|gif)$/i', $f) === 1);
        sort($files);
        $photos = [];
        foreach ($files as $file) {
            $name = basename($file);
            $photos[] = ['name' => $name, 'file_path' => 'public/uploads/classrooms/' . $classroomId . '/' . $name];
        }
        return $photos;
    }

    /**
     * Chép ảnh vào public/uploads/classrooms/{classroom_id}/.
     * @param array<int, array{tmp: string, ext: string}> $uploads
     */
    private function storePhotos(int $classroomId, array $uploads): int {
        if (!$uploads) {
            return 0;
        }
        $dir = $this->photoDir($classroomId);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $saved = 0;
        foreach ($uploads as $upload) {
            $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $upload['ext'];
            if (move_uploaded_file($upload['tmp'], $dir . '/' . $name)) {
                $saved++;
            }
        }
        return $saved;
    }

    private function redirect(string $path): void {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    private function handleServicesPost(): void {
        $action = $this->postedAction();

        try {
            if ($action === 'create' || $action === 'update') {
                $parsed = $this->serviceInput();
                $recordId = $this->postedId();

                if ($action === 'update' && $recordId === null) {
                    $parsed['errors'][] = 'Choose a service before saving.';
                }

                if ($action === 'update' && $recordId !== null && !$this->categoryModel->find($recordId)) {
                    $parsed['errors'][] = 'That service no longer exists.';
                }

                if (!$parsed['errors']) {
                    if ($this->categoryModel->codeTaken($parsed['category_code'], $action === 'update' ? $recordId : null)) {
                        $parsed['errors'][] = 'That service code is already used.';
                    }
                    if ($this->categoryModel->nameTaken($parsed['department_id'], $parsed['category_name'], $action === 'update' ? $recordId : null)) {
                        $parsed['errors'][] = 'That department already has a service with this name.';
                    }
                }

                if ($parsed['errors']) {
                    $this->reject('services', $action, $parsed['values'], $parsed['errors']);
                }

                if ($action === 'create') {
                    $this->categoryModel->insert(
                        $parsed['department_id'],
                        $parsed['category_code'],
                        $parsed['category_name'],
                        $parsed['description'],
                        $parsed['routing_keywords'],
                        $parsed['default_priority'],
                        $parsed['sla_response_minutes'],
                        $parsed['sla_resolution_minutes']
                    );
                    $this->flash('success', 'Service ' . $parsed['category_name'] . ' was added.', 'services');
                } else {
                    $this->categoryModel->updateInfo(
                        (int) $recordId,
                        $parsed['department_id'],
                        $parsed['category_code'],
                        $parsed['category_name'],
                        $parsed['description'],
                        $parsed['routing_keywords'],
                        $parsed['default_priority'],
                        $parsed['sla_response_minutes'],
                        $parsed['sla_resolution_minutes']
                    );
                    $this->flash('success', 'Service ' . $parsed['category_name'] . ' was updated.', 'services');
                }
            }

            if ($action === 'toggle') {
                $recordId = $this->postedId();
                $existing = $recordId === null ? null : $this->categoryModel->find($recordId);
                if (!$existing) {
                    $this->flash('error', 'That service no longer exists.', 'services');
                }
                $next = (int) $existing['is_active'] === 1 ? 0 : 1;
                $this->categoryModel->setActive($recordId, $next);
                $verb = $next === 1 ? 'activated' : 'deactivated';
                $this->flash('success', 'Service ' . $existing['category_name'] . ' was ' . $verb . '.', 'services');
            }

            if ($action === 'delete') {
                $recordId = $this->postedId();
                $existing = $recordId === null ? null : $this->categoryModel->find($recordId);
                if (!$existing) {
                    $this->flash('error', 'That category no longer exists.', 'services');
                }
                try {
                    $this->categoryModel->delete($recordId);
                } catch (PDOException $e) {
                    if ($this->isForeignKeyBlock($e)) {
                        $this->flash('error', 'Category ' . $existing['category_name'] . ' is already used by tickets or assets, so it cannot be deleted. Turn it off instead.', 'services');
                    }
                    throw $e;
                }
                $this->flash('success', 'Category ' . $existing['category_name'] . ' was deleted.', 'services');
            }

            if ($action === 'dept_create' || $action === 'dept_update') {
                $this->saveDepartment($action);
            }

            if ($action === 'dept_toggle' || $action === 'dept_delete') {
                $recordId = $this->postedId();
                $existing = $recordId === null ? null : $this->categoryModel->findDepartment($recordId);
                if (!$existing) {
                    $this->flash('error', 'That department no longer exists.', 'services');
                }
                if ($action === 'dept_toggle') {
                    $next = (int) $existing['is_active'] === 1 ? 0 : 1;
                    $this->categoryModel->setDepartmentActive($recordId, $next);
                    $this->flash('success', 'Department ' . $existing['department_name'] . ' was ' . ($next === 1 ? 'activated' : 'deactivated') . '.', 'services');
                }
                try {
                    $this->categoryModel->deleteDepartment($recordId);
                } catch (PDOException $e) {
                    if ($this->isForeignKeyBlock($e)) {
                        $this->flash('error', 'Department ' . $existing['department_name'] . ' still has categories, staff, or tickets linked to it, so it cannot be deleted. Turn it off instead.', 'services');
                    }
                    throw $e;
                }
                $this->flash('success', 'Department ' . $existing['department_name'] . ' was deleted.', 'services');
            }

            $this->flash('error', 'That action is not available on this page.', 'services');
        } catch (PDOException $e) {
            $this->flash('error', $this->saveError($e), 'services');
        }
    }

    /** Thêm hoặc sửa phòng ban từ hộp thoại trên tab Departments. */
    private function saveDepartment(string $action): void {
        $code = strtoupper($this->collapse((string) ($_POST['department_code'] ?? '')));
        $name = $this->collapse((string) ($_POST['department_name'] ?? ''));
        $description = $this->collapse((string) ($_POST['description'] ?? ''));
        $email = trim((string) ($_POST['contact_email'] ?? ''));
        $phone = trim((string) ($_POST['contact_phone'] ?? ''));
        $isActive = ((string) ($_POST['is_active'] ?? '1')) === '0' ? 0 : 1;
        $recordId = $this->postedId();
        $errors = [];

        if ($action === 'dept_update' && ($recordId === null || !$this->categoryModel->findDepartment($recordId))) {
            $errors[] = 'That department no longer exists.';
        }
        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) {
            $errors[] = 'Department code must be 2–20 characters: letters, numbers, hyphen, or underscore.';
        }
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Department name is required and must be 100 characters or fewer.';
        }
        if (mb_strlen($description) > 255) {
            $errors[] = 'Description must be 255 characters or fewer.';
        }
        if ($email !== '' && (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $errors[] = 'Enter a valid contact email.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+().\s-]{6,20}$/', $phone)) {
            $errors[] = 'Contact phone must be 6–20 digits.';
        }
        $excludeId = $action === 'dept_update' ? $recordId : null;
        if (!$errors && $this->categoryModel->departmentCodeTaken($code, $excludeId)) {
            $errors[] = 'That department code is already used.';
        }
        if (!$errors && $this->categoryModel->departmentNameTaken($name, $excludeId)) {
            $errors[] = 'That department name is already used.';
        }

        if ($errors) {
            $this->reject('services', $action, [
                'record_id' => trim((string) ($_POST['record_id'] ?? '')),
                'department_code' => $code,
                'department_name' => $name,
                'description' => $description,
                'contact_email' => $email,
                'contact_phone' => $phone,
                'is_active' => (string) $isActive,
            ], $errors);
        }

        $description = $description === '' ? null : $description;
        $email = $email === '' ? null : $email;
        $phone = $phone === '' ? null : $phone;
        if ($action === 'dept_create') {
            $this->categoryModel->insertDepartment($code, $name, $description, $email, $phone, $isActive);
            $this->flash('success', 'Department ' . $name . ' was added.', 'services');
        }
        $this->categoryModel->updateDepartment((int) $recordId, $code, $name, $description, $email, $phone, $isActive);
        $this->flash('success', 'Department ' . $name . ' was updated.', 'services');
    }

    private function isForeignKeyBlock(PDOException $e): bool {
        return (int) ($e->errorInfo[1] ?? 0) === 1451 || stripos($e->getMessage(), 'foreign key') !== false;
    }

    private function handleUsersPost(): void {
        $action = $this->postedAction();

        try {
            if ($action === 'create' || $action === 'update') {
                $parsed = $this->userInput($action);
                $recordId = $this->postedId();

                if ($action === 'update' && $recordId === null) {
                    $parsed['errors'][] = 'Choose an account before saving.';
                }

                $existing = null;
                if ($action === 'update' && $recordId !== null) {
                    $existing = $this->userModel->findById($recordId);
                    if (!$existing) {
                        $parsed['errors'][] = 'That account no longer exists.';
                    }
                }

                if (!$parsed['errors']) {
                    if ($this->userModel->emailExists($parsed['email'], $action === 'update' ? $recordId : null)) {
                        $parsed['errors'][] = 'That email is already used.';
                    }
                    if ($parsed['user_code'] !== null && $this->userModel->userCodeExists($parsed['user_code'], $action === 'update' ? $recordId : null)) {
                        $parsed['errors'][] = 'That user code is already used.';
                    }
                }

                if ($action === 'update' && $recordId === (int) ($_SESSION['user_id'] ?? 0) && $parsed['role'] !== 'Manager') {
                    $parsed['errors'][] = 'You cannot change your own role while signed in. Use My Profile for your name, email, and password.';
                }

                if ($parsed['errors']) {
                    if ((string) ($_POST['password'] ?? '') !== '') {
                        $parsed['errors'][] = 'Enter the password again. It is not kept after a failed save.';
                    }
                    $this->reject('users', $action, $parsed['values'], $parsed['errors']);
                }

                if ($action === 'create') {
                    $this->userModel->insert(
                        $parsed['user_code'],
                        $parsed['full_name'],
                        $parsed['email'],
                        (string) $parsed['password_hash'],
                        $parsed['phone'],
                        $parsed['role'],
                        $parsed['department_id'],
                        $parsed['is_active']
                    );
                    $this->flash('success', 'Account ' . $parsed['full_name'] . ' was added.', 'users');
                } else {
                    $this->userModel->updateInfo(
                        (int) $recordId,
                        $parsed['user_code'],
                        $parsed['full_name'],
                        $parsed['email'],
                        $parsed['phone'],
                        $parsed['role'],
                        $parsed['department_id'],
                        $parsed['password_hash']
                    );
                    // Đổi trạng thái từ ô Status trong form sửa (không áp dụng cho chính mình).
                    if ($recordId !== (int) ($_SESSION['user_id'] ?? 0) && (int) $existing['is_active'] !== $parsed['is_active']) {
                        $this->userModel->setActive((int) $recordId, $parsed['is_active']);
                    }
                    $this->syncSessionIdentity((int) $recordId, $parsed['user_code'], $parsed['full_name'], $parsed['email'], $parsed['role'], $parsed['department_id']);
                    $this->flash('success', 'Account ' . $parsed['full_name'] . ' was updated.', 'users');
                }
            }

            if ($action === 'toggle') {
                $recordId = $this->postedId();
                if ($recordId !== null && $recordId === (int) ($_SESSION['user_id'] ?? 0)) {
                    $this->flash('error', 'You cannot change the status of the account you are signed in with.', 'users');
                }
                $existing = $recordId === null ? null : $this->userModel->findById($recordId);
                if (!$existing) {
                    $this->flash('error', 'That account no longer exists.', 'users');
                }
                $next = (int) $existing['is_active'] === 1 ? 0 : 1;
                $this->userModel->setActive($recordId, $next);
                $verb = $next === 1 ? 'activated' : 'deactivated';
                $this->flash('success', 'Account ' . $existing['full_name'] . ' was ' . $verb . '.', 'users');
            }

            $this->flash('error', 'That action is not available on this page.', 'users');
        } catch (PDOException $e) {
            $this->flash('error', $this->saveError($e), 'users');
        }
    }

    /**
     * Đọc form phòng học: Room name (vd A1-201) và Status.
     * Tòa nhà và tầng tự suy ra từ tên phòng: "A1-201" -> Building A1, tầng 2.
     * @return array<string, mixed>
     */
    private function classroomInput(): array {
        $room = strtoupper(str_replace(' ', '', $this->collapse((string) ($_POST['room_number'] ?? ''))));
        $statusRaw = (string) ($_POST['is_active'] ?? '1');
        $errors = [];

        $building = '';
        $floor = null;
        if ($room === '') {
            $errors[] = 'Room name is required.';
        } elseif (mb_strlen($room) > 50 || !preg_match('/^([A-Z]+\d*)-(\d{3,4})$/', $room, $m)) {
            $errors[] = 'Room name must look like A1-201 (building code, a dash, then floor and room number).';
        } else {
            $building = 'Building ' . $m[1];
            $floor = intdiv((int) $m[2], 100);
        }

        $isActive = $statusRaw === '0' ? 0 : 1;

        return [
            'errors' => $errors,
            'values' => [
                'room_number' => $room,
                'is_active' => (string) $isActive,
            ],
            'room_number' => $room,
            'building' => $building,
            'floor' => $floor,
            'is_active' => $isActive,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceInput(): array {
        $code = strtoupper($this->collapse((string) ($_POST['category_code'] ?? '')));
        $name = $this->collapse((string) ($_POST['category_name'] ?? ''));
        $description = $this->collapse((string) ($_POST['description'] ?? ''));
        $keywords = $this->collapse((string) ($_POST['routing_keywords'] ?? ''));
        $priority = trim((string) ($_POST['default_priority'] ?? ''));
        $responseRaw = trim((string) ($_POST['sla_response_minutes'] ?? ''));
        $resolutionRaw = trim((string) ($_POST['sla_resolution_minutes'] ?? ''));
        $departmentRaw = trim((string) ($_POST['department_id'] ?? ''));
        $errors = [];

        $departmentId = 0;
        if (!ctype_digit($departmentRaw) || (int) $departmentRaw < 1 || !$this->categoryModel->findDepartment((int) $departmentRaw)) {
            $errors[] = 'Choose a department from the list.';
        } else {
            $departmentId = (int) $departmentRaw;
        }

        if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $code)) {
            $errors[] = 'Service code must be 2–30 characters: letters, numbers, hyphen, or underscore.';
        }
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Service name is required and must be 100 characters or fewer.';
        }
        if (mb_strlen($description) > 255) {
            $errors[] = 'Description must be 255 characters or fewer.';
        }
        if (mb_strlen($keywords) > 500) {
            $errors[] = 'Routing keywords must be 500 characters or fewer.';
        }
        if (!in_array($priority, self::PRIORITIES, true)) {
            $errors[] = 'Choose a default priority.';
            $priority = 'Medium';
        }

        $response = null;
        $resolution = null;
        if (!ctype_digit($responseRaw) || (int) $responseRaw < 1) {
            $errors[] = 'Response SLA must be a whole number of minutes greater than 0.';
        } else {
            $response = (int) $responseRaw;
        }
        if (!ctype_digit($resolutionRaw) || (int) $resolutionRaw < 1) {
            $errors[] = 'Resolution SLA must be a whole number of minutes greater than 0.';
        } else {
            $resolution = (int) $resolutionRaw;
        }
        if ($response !== null && $resolution !== null && $resolution < $response) {
            $errors[] = 'Resolution time must be at least the response time.';
        }
        if (($response !== null && $response > 525600) || ($resolution !== null && $resolution > 525600)) {
            $errors[] = 'SLA minutes cannot exceed one year (525600).';
        }

        return [
            'errors' => $errors,
            'values' => [
                'record_id' => trim((string) ($_POST['record_id'] ?? '')),
                'department_id' => $departmentRaw,
                'category_code' => $code,
                'category_name' => $name,
                'description' => $description,
                'routing_keywords' => $keywords,
                'default_priority' => $priority,
                'sla_response_minutes' => $responseRaw,
                'sla_resolution_minutes' => $resolutionRaw,
            ],
            'department_id' => $departmentId,
            'category_code' => $code,
            'category_name' => $name,
            'description' => $description === '' ? null : $description,
            'routing_keywords' => $keywords === '' ? null : $keywords,
            'default_priority' => $priority,
            'sla_response_minutes' => (int) $response,
            'sla_resolution_minutes' => (int) $resolution,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userInput(string $action): array {
        $userCode = strtoupper($this->collapse((string) ($_POST['user_code'] ?? '')));
        $fullName = $this->collapse((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));
        $departmentRaw = trim((string) ($_POST['department_id'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $isActive = ((string) ($_POST['is_active'] ?? '1')) === '0' ? 0 : 1;
        $errors = [];

        if ($userCode !== '' && !preg_match('/^[A-Z0-9_-]{2,20}$/', $userCode)) {
            $errors[] = 'User code must be 2–20 characters: letters, numbers, hyphen, or underscore.';
        }
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors[] = 'Full name must be between 2 and 100 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            $errors[] = 'Enter a valid email address, up to 100 characters.';
        }

        $phone = null;
        if ($phoneRaw !== '') {
            if (!preg_match('/^[0-9+\-\s().]{6,20}$/', $phoneRaw)) {
                $errors[] = 'Phone may contain digits, spaces, and + - ( ) and must be 6–20 characters.';
            } else {
                $phone = $phoneRaw;
            }
        }

        if (!in_array($role, self::ROLES, true)) {
            $errors[] = 'Choose a role.';
            $role = 'Student';
        }

        $departmentId = null;
        if ($departmentRaw !== '') {
            if (!ctype_digit($departmentRaw) || !$this->categoryModel->findDepartment((int) $departmentRaw)) {
                $errors[] = 'Choose a department from the list.';
            } else {
                $departmentId = (int) $departmentRaw;
            }
        }
        if ($role === 'Technician' && $departmentId === null) {
            $errors[] = 'Technicians must belong to a department.';
        }

        $passwordHash = null;
        if ($action === 'create' || $password !== '') {
            if (strlen($password) < 6 || strlen($password) > 72) {
                $errors[] = $action === 'create'
                    ? 'Password must be at least 6 characters long.'
                    : 'New password must be at least 6 characters, or leave it blank to keep the current one.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if (!is_string($passwordHash)) {
                    $errors[] = 'Could not store that password. Try a different one.';
                }
            }
        }

        return [
            'errors' => $errors,
            'values' => [
                'record_id' => trim((string) ($_POST['record_id'] ?? '')),
                'user_code' => $userCode,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phoneRaw,
                'role' => $role,
                'department_id' => $departmentRaw,
                'is_active' => (string) $isActive,
            ],
            'is_active' => $isActive,
            'user_code' => $userCode === '' ? null : $userCode,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'department_id' => $departmentId,
            'password_hash' => $passwordHash,
        ];
    }

    private function syncSessionIdentity(
        int $userId,
        ?string $userCode,
        string $fullName,
        string $email,
        string $role,
        ?int $departmentId
    ): void {
        if ($userId !== (int) ($_SESSION['user_id'] ?? 0)) {
            return;
        }
        $_SESSION['user_code'] = $userCode;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['department_id'] = $departmentId;
    }

    private function postedAction(): string {
        $action = $_POST['form_action'] ?? '';
        return is_string($action) ? $action : '';
    }

    private function postedId(): ?int {
        $raw = trim((string) ($_POST['record_id'] ?? ''));
        if ($raw === '' || !ctype_digit($raw) || (int) $raw < 1) {
            return null;
        }
        return (int) $raw;
    }

    private function collapse(string $value): string {
        $value = trim($value);
        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function saveError(PDOException $e): string {
        $message = $e->getMessage();
        if (stripos($message, 'Duplicate') !== false) {
            return 'A record with that code, name, room, or email already exists.';
        }
        if (stripos($message, 'chk_users_dept_by_role') !== false) {
            return 'Technicians must belong to a department.';
        }
        if (stripos($message, 'chk_tc_sla') !== false) {
            return 'Resolution time must be at least the response time.';
        }
        return 'The database rejected this change. Check the values and try again.';
    }

    private function render(string $view, string $page, string $title, string $nav, array $data): void {
        $this->view($view, array_merge($data, [
            'page_title' => $title . ' - Campus Helpdesk',
            'breadcrumb_current' => $title,
            'current_nav' => $nav,
            'flash' => $this->consumeFlash(),
            'form_error' => $this->consumeFormError($page),
        ]));
    }

    /**
     * @param array<int, string> $errors
     * @param array<string, string> $values
     */
    private function reject(string $page, string $action, array $values, array $errors): void {
        $_SESSION['master_form_error'] = [
            'page' => $page,
            'action' => $action,
            'values' => $values,
            'errors' => array_values($errors),
        ];
        $this->go($page);
    }

    private function flash(string $type, string $message, string $page): void {
        $_SESSION['master_flash'] = ['type' => $type, 'message' => $message];
        $this->go($page);
    }

    private function go(string $page): void {
        $paths = [
            'classrooms' => '/master/classrooms',
            'services' => '/master/services',
            'users' => '/master/users',
        ];
        header('Location: ' . BASE_URL . ($paths[$page] ?? '/master/classrooms'));
        exit;
    }

    private function consumeFlash(): ?array {
        $flash = $_SESSION['master_flash'] ?? null;
        unset($_SESSION['master_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function consumeFormError(string $page): ?array {
        $bag = $_SESSION['master_form_error'] ?? null;
        unset($_SESSION['master_form_error']);
        if (!is_array($bag) || ($bag['page'] ?? '') !== $page) {
            return null;
        }
        unset($bag['page']);
        return $bag;
    }
}
