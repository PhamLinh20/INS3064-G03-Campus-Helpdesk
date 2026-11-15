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

    public function classrooms(): void {
        header('Location: ' . BASE_URL . '/master/classrooms');
        exit;
    }

    public function services(): void {
        header('Location: ' . BASE_URL . '/master/services');
        exit;
    }

    public function users(): void {
        header('Location: ' . BASE_URL . '/master/users');
        exit;
    }

    public function profile(): void {
        header('Location: ' . BASE_URL . '/auth/profile');
        exit;
    }
}
