<?php
require_once APPROOT . '/app/Core/AuthMiddleware.php';

class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        // Tải UserModel qua hàm của Base Controller
        $this->userModel = $this->model('UserModel');
    }

    public function login(): void {
        // Nếu đã đăng nhập trước đó thì chuyển hướng vào portal tương ứng
        AuthMiddleware::redirectIfLoggedIn();

        $data = [
            'page_title' => 'Sign In - Campus Helpdesk',
            'error'      => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($email) || empty($password)) {
                $data['error'] = 'Please enter both email and password.';
            } else {
                $user = $this->userModel->findUserByEmail($email);

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id']       = $user['user_id'];
                    $_SESSION['user_code']     = $user['user_code'];
                    $_SESSION['full_name']     = $user['full_name'];
                    $_SESSION['email']         = $user['email'];
                    $_SESSION['role']          = $user['role'];
                    $_SESSION['department_id'] = $user['department_id'];

                    $this->userModel->updateLastLogin($user['user_id']);
                    AuthMiddleware::redirectIfLoggedIn();
                } else {
                    $data['error'] = 'Invalid email credentials or inactive account.';
                }
            }
        }

        $this->view('auth/login', $data);
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    /**
     * Hồ sơ của chính người đang đăng nhập. Mọi vai trò dùng chung URL /auth/profile.
     * Chỉ sửa họ tên, email, số điện thoại và mật khẩu. Role và is_active thuộc Users Management.
     */
    public function profile(): void {
        AuthMiddleware::requireLogin();

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $account = $this->userModel->findById($userId);
        if (!$account) {
            $_SESSION = [];
            session_destroy();
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $errors = [];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $result = $this->validateProfile($account);
            if ($result['errors']) {
                $errors = $result['errors'];
                $account['full_name'] = $result['full_name'];
                $account['email'] = $result['email'];
                $account['phone'] = $result['phone_raw'];
            } else {
                try {
                    $this->userModel->updateProfile(
                        $userId,
                        $result['full_name'],
                        $result['email'],
                        $result['phone'],
                        $result['password_hash']
                    );
                    $_SESSION['full_name'] = $result['full_name'];
                    $_SESSION['email'] = $result['email'];
                    $_SESSION['profile_flash'] = [
                        'type' => 'success',
                        'message' => $result['password_hash'] === null
                            ? 'Your profile was saved.'
                            : 'Your password was changed.',
                    ];
                    header('Location: ' . BASE_URL . '/auth/profile');
                    exit;
                } catch (PDOException $e) {
                    $errors = ['Could not save your profile. That email may already be in use.'];
                    $account['full_name'] = $result['full_name'];
                    $account['email'] = $result['email'];
                    $account['phone'] = $result['phone_raw'];
                }
            }
        }

        $flash = $_SESSION['profile_flash'] ?? null;
        unset($_SESSION['profile_flash'], $account['password_hash']);

        $this->view('profile', [
            'account' => $account,
            'errors' => $errors,
            'flash' => is_array($flash) ? $flash : null,
        ]);
    }

    /**
     * @param array<string, mixed> $account
     * @return array{errors: array<int, string>, full_name: string, email: string, phone: ?string, phone_raw: string, password_hash: ?string}
     */
    private function validateProfile(array $account): array {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $fullName = preg_replace('/\s+/u', ' ', $fullName) ?? $fullName;
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
        $current = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $errors = [];

        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors[] = 'Full name must be between 2 and 100 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            $errors[] = 'Enter a valid email address.';
        } elseif ($this->userModel->emailExists($email, (int) $account['user_id'])) {
            $errors[] = 'That email is already used.';
        }

        $phone = null;
        if ($phoneRaw !== '') {
            if (!preg_match('/^[0-9+\-\s().]{6,20}$/', $phoneRaw)) {
                $errors[] = 'Phone may contain digits, spaces, and + - ( ) and must be 6–20 characters.';
            } else {
                $phone = $phoneRaw;
            }
        }

        $changingPassword = $current !== '' || $newPassword !== '' || $confirm !== '';
        if ($changingPassword) {
            if ($current === '' || !password_verify($current, (string) $account['password_hash'])) {
                $errors[] = 'Current password is not correct.';
            }
            if (strlen($newPassword) < 6 || strlen($newPassword) > 72) {
                $errors[] = 'Password must be at least 6 characters long.';
            }
            if ($newPassword !== $confirm) {
                $errors[] = 'New password and confirmation do not match.';
            }
            if (!$errors && password_verify($newPassword, (string) $account['password_hash'])) {
                $errors[] = 'New password must be different from the current password.';
            }
        }

        $passwordHash = null;
        if ($changingPassword && !$errors) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if (!is_string($passwordHash)) {
                $errors[] = 'Could not store that password. Try a different one.';
                $passwordHash = null;
            }
        }

        return [
            'errors' => $errors,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'phone_raw' => $phoneRaw,
            'password_hash' => $passwordHash,
        ];
    }
}