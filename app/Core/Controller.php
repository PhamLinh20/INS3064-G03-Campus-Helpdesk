<?php
/**
 * ==============================================================================
 * LỚP ĐIỀU KHIỂN CƠ SỞ (BASE CONTROLLER)
 * Vai trò:
 * - Là lớp cha của tất cả các Controller khác trong thư mục app/Controllers/.
 * - Cung cấp sẵn các phương thức tiện ích để nạp Model và đổ dữ liệu sang View.
 * ==============================================================================
 */
class Controller {
    /**
     * Nạp và khởi tạo một Model tương ứng
     * @param string $model Tên class Model cần gọi (Ví dụ: 'TicketModel', 'UserModel')
     * @return object Đối tượng Model được tạo
     */
    public function model(string $model) {
        $file = APPROOT . '/app/Models/' . $model . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return new $model();
        } else {
            die("Không tìm thấy Model: " . $model);
        }
    }

    /**
     * Nạp file giao diện View và truyền dữ liệu hiển thị
     * @param string $viewPath Đường dẫn tương đối của file View tính từ thư mục app/Views/ (Ví dụ: 'auth/login')
     * @param array $data Mảng dữ liệu được Controller truyền sang cho View hiển thị
     */
    public function view(string $viewPath, array $data = []): void {
        // Hàm extract sẽ chuyển đổi các key của mảng $data thành các biến độc lập
        // Ví dụ: $data['title'] = 'Trang chủ' -> Trong View dùng trực tiếp biến $title
        extract($data);

        $file = APPROOT . '/app/Views/' . $viewPath . '.php';

        if (file_exists($file)) {
            require_once $file;
        } else {
            die("Không tìm thấy View: " . $viewPath);
        }
    }
}