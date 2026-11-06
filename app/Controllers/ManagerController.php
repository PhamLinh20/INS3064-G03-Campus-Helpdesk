<?php
require_once APPROOT . '/app/Core/AuthMiddleware.php';

class ManagerController extends Controller {
    public function __construct() {
        // Chỉ cho phép Manager truy cập
        AuthMiddleware::requireRole(['Manager']);
    }

    public function dashboard(): void {
        $data = [
            'page_title' => 'Manager Dashboard - Campus Helpdesk',
            'user_name'  => $_SESSION['full_name'] ?? 'Manager',
            'role'       => 'Manager'
        ];
        $this->view('manager/dashboard', $data);
    }
}
