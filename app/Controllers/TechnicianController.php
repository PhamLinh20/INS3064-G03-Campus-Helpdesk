<?php
require_once APPROOT . '/app/Core/AuthMiddleware.php';

class TechnicianController extends Controller {
    public function __construct() {
        // Chỉ cho phép Technician truy cập
        AuthMiddleware::requireRole(['Technician']);
    }

    public function dashboard(): void {
        $data = [
            'page_title' => 'Technician Dashboard - Campus Helpdesk',
            'user_name'  => $_SESSION['full_name'] ?? 'Technician',
            'role'       => $_SESSION['role'] ?? 'Technician'
        ];
        $this->view('technician/dashboard', $data);
    }

    public function profile(): void {
        header('Location: ' . BASE_URL . '/auth/profile');
        exit;
    }
}
