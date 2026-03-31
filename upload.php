<?php
require_once 'config.php';

// 检查登录状态
if (!isLoggedIn()) {
    jsonResponse(false, '请先登录');
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, '非法请求');
}

// 检查文件
if (!isset($_FILES['file'])) {
    jsonResponse(false, '未选择文件');
}

$file = $_FILES['file'];

// 检查上传错误
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
        UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
        UPLOAD_ERR_PARTIAL => '文件部分上传失败',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时目录',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败'
    ];
    jsonResponse(false, $errors[$file['error']] ?? '上传失败');
}

// 检查文件大小
if ($file['size'] > UPLOAD_MAX_SIZE) {
    jsonResponse(false, '文件大小超过10MB限制');
}

// 获取文件信息
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// 验证MIME类型
if (!in_array($mimeType, ALLOWED_TYPES)) {
    jsonResponse(false, '不支持的文件类型：' . $mimeType);
}

// 获取文件扩展名
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
if (!in_array(strtolower($extension), ALLOWED_EXT)) {
    jsonResponse(false, '不支持的文件扩展名');
}

// 验证文件头（双重验证）
$validHeaders = [
    'image/jpeg' => "\xFF\xD8\xFF",
    'image/png' => "\x89PNG\r\n\x1a\n",
    'image/gif' => "GIF",
    'image/webp' => "RIFF"
];

$handle = fopen($file['tmp_name'], 'r');
$header = fread($handle, 8);
fclose($handle);

$valid = false;
foreach ($validHeaders as $type => $magic) {
    if (strpos($header, $magic) === 0) {
        $valid = true;
        break;
    }
}

if (!$valid) {
    jsonResponse(false, '文件头验证失败，可能为恶意文件');
}

// 生成随机文件名
$dateDir = getUploadPath();
$newName = generateRandomName($extension);
$targetPath = UPLOAD_DIR . '/' . $dateDir . '/' . $newName;

// 移动文件
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    jsonResponse(false, '文件保存失败');
}

// 返回成功信息
$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/' . $targetPath;

jsonResponse(true, '上传成功', [
    'path' => $targetPath,
    'url' => $url,
    'name' => $newName,
    'size' => $file['size']
]);
?>
