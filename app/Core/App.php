<?php
/**
 * ==============================================================================
 * BỘ ĐỊNH TUYẾN TRUNG TÂM (CORE ROUTER)
 * Vai trò:
 * - Bóc tách chuỗi URL nhận được từ file .htaccess theo cấu trúc:
 *   /ten-controller/ten-action/tham-so-1/tham-so-2/...
 * - Tự động gọi đúng Controller, hàm (Action) và truyền danh sách tham số tương ứng.
 * ==============================================================================
 */
class App {
    // Controller mặc định khi người dùng chỉ truy cập vào trang chủ hệ thống
    protected $controller = 'AuthController';

    // Action (Method trong Controller) mặc định
    protected string $action = 'login';

    // Mảng chứa các tham số truyền qua URL
    protected array $params = [];

    public function __construct() {
        // Phân tích đường dẫn URL thành mảng
        $url = $this->parseUrl();

        // ----------------------------------------------------------------------
        // 1. XỬ LÝ CONTROLLER:
        // ----------------------------------------------------------------------
        if (!empty($url[0])) {
            // Chuyển chuỗi thành định dạng tên Class (Ví dụ: 'ticket' -> 'TicketController')
            $controllerName = ucfirst($url[0]) . 'Controller';

            if (file_exists(APPROOT . '/app/Controllers/' . $controllerName . '.php')) {
                $this->controller = $controllerName;
                unset($url[0]); // Loại bỏ phần tử sau khi đã xử lý
            }
        }

        // Nhúng file Controller được yêu cầu và tiến hành khởi tạo
        require_once APPROOT . '/app/Controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller();

        // ----------------------------------------------------------------------
        // 2. XỬ LÝ ACTION (METHOD TRONG CONTROLLER):
        // ----------------------------------------------------------------------
        if (!empty($url[1])) {
            // Kiểm tra phương thức có tồn tại bên trong Controller vừa khởi tạo hay không
            if (method_exists($this->controller, $url[1])) {
                $this->action = $url[1];
                unset($url[1]); // Loại bỏ phần tử sau khi đã xử lý
            }
        }

        // ----------------------------------------------------------------------
        // 3. XỬ LÝ THAM SỐ (PARAMS):
        // ----------------------------------------------------------------------
        // Các giá trị còn lại trong URL sẽ được nạp vào mảng tham số
        $this->params = $url ? array_values($url) : [];

        // ----------------------------------------------------------------------
        // 4. KÍCH HOẠT HÀM:
        // ----------------------------------------------------------------------
        // Thực thi method $this->action trong đối tượng $this->controller kèm theo mảng $this->params
        call_user_func_array([$this->controller, $this->action], $this->params);
    }

    /**
     * Bóc tách và làm sạch chuỗi URL nhận từ biến $_GET['url']
     * @return array Mảng chứa các đoạn path của URL
     */
    private function parseUrl(): array {
        if (isset($_GET['url'])) {
            // Cắt bỏ dấu gạch chéo dư thừa ở cuối và lọc ký tự không hợp lệ
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}