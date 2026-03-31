<?php
// 系统配置
define('ADMIN_PASSWORD', '123456'); // 修改为你的管理员密码
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_DIR', 'uploads');

// 启动session
session_start();

// 响应头
header('Content-Type: application/json; charset=utf-8');

// 工具函数
function jsonResponse($success, $message = '', $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function generateRandomName($extension) {
    return date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($extension);
}

function getUploadPath() {
    $dateDir = date('Y-m');
    $fullPath = UPLOAD_DIR . '/' . $dateDir;
    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
    return $dateDir;
}
?>
