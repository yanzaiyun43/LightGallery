<?php
require_once 'config.php';

// 设置 JSON 响应头（因为 config.php 中已经移除了全局 header）
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'rename':
        handleRename();
        break;
    case 'list':
        handleList();
        break;
    case 'check':
        handleCheck();
        break;
    default:
        jsonResponse(false, '未知操作');
}

function handleLogin() {
    $password = $_POST['password'] ?? '';
    
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
        jsonResponse(true, '登录成功');
    } else {
        jsonResponse(false, '密码错误');
    }
}

function handleLogout() {
    session_destroy();
    jsonResponse(true, '退出成功');
}

function handleCheck() {
    jsonResponse(true, '检查成功', ['logged_in' => isLoggedIn()]);
}

function handleDelete() {
    if (!isLoggedIn()) {
        jsonResponse(false, '未登录');
    }
    
    $path = $_POST['path'] ?? '';
    
    // 安全检查：防止目录遍历
    $path = str_replace('..', '', $path);
    $fullPath = realpath($path);
    $uploadDir = realpath(UPLOAD_DIR);
    
    if (!$fullPath || strpos($fullPath, $uploadDir) !== 0) {
        jsonResponse(false, '非法路径');
    }
    
    if (!file_exists($fullPath)) {
        jsonResponse(false, '文件不存在');
    }
    
    if (unlink($fullPath)) {
        // 尝试删除空目录
        $dir = dirname($fullPath);
        if (is_dir($dir) && count(glob("$dir/*")) === 0) {
            @rmdir($dir);
        }
        jsonResponse(true, '删除成功');
    } else {
        jsonResponse(false, '删除失败');
    }
}

function handleRename() {
    if (!isLoggedIn()) {
        jsonResponse(false, '未登录');
    }
    
    $path = $_POST['path'] ?? '';
    $newName = $_POST['name'] ?? '';
    
    // 安全检查
    $path = str_replace('..', '', $path);
    $newName = preg_replace('/[^a-zA-Z0-9_\-\x{4e00}-\x{9fa5}]/u', '', $newName);
    
    if (empty($newName)) {
        jsonResponse(false, '文件名不能为空');
    }
    
    $fullPath = realpath($path);
    $uploadDir = realpath(UPLOAD_DIR);
    
    if (!$fullPath || strpos($fullPath, $uploadDir) !== 0) {
        jsonResponse(false, '非法路径');
    }
    
    if (!file_exists($fullPath)) {
        jsonResponse(false, '文件不存在');
    }
    
    $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
    $dir = dirname($fullPath);
    $newPath = $dir . '/' . $newName . '.' . $extension;
    
    // 检查目标文件是否已存在
    if (file_exists($newPath)) {
        jsonResponse(false, '目标文件名已存在');
    }
    
    if (rename($fullPath, $newPath)) {
        jsonResponse(true, '重命名成功');
    } else {
        jsonResponse(false, '重命名失败');
    }
}

function handleList() {
    $images = [];
    $uploadDir = UPLOAD_DIR;
    
    if (!is_dir($uploadDir)) {
        jsonResponse(true, '暂无图片', ['data' => []]);
    }
    
    // 递归扫描上传目录
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ALLOWED_EXT)) {
            $path = $file->getPathname();
            $relativePath = str_replace('\\', '/', $path);
            $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/' . $relativePath;
            
            $images[] = [
                'name' => $file->getFilename(),
                'path' => $relativePath,
                'url' => $url,
                'size' => formatSize($file->getSize()),
                'date' => date('Y-m-d H:i', $file->getMTime()),
                'timestamp' => $file->getMTime()
            ];
        }
    }
    
    // 按时间倒序排列
    usort($images, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    jsonResponse(true, '获取成功', ['data' => $images]);
}

function formatSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}
?>
