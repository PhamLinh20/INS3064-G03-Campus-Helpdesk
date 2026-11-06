<?php
require_once APPROOT . '/app/Core/AuthMiddleware.php';

class UserController extends Controller {
    public function __construct() {
        // Chỉ cho phép Student và Lecturer truy cập
        AuthMiddleware::requireRole(['Student', 'Lecturer']);
    }

    public function dashboard(): void {
        $data = [
            'page_title' => 'User Dashboard - Campus Helpdesk',
            'user_name'  => $_SESSION['full_name'] ?? 'User',
            'role'       => $_SESSION['role'] ?? 'Student'
        ];
        $this->view('user/dashboard', $data);
    }
}
