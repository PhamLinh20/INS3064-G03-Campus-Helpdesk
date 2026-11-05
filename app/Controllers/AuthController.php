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
}