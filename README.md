# LightGallery - 极简图床

LightGallery 是一个轻量级、易部署的图片托管系统，支持图片上传、管理、预览和多格式链接复制。前端采用 Vue 3 的CDN方案构建，后端使用 PHP，无需数据库，开箱即用。

## 功能特点

- 📤 **拖拽上传**：支持点击或拖拽上传图片，实时显示上传进度
- 🔒 **密码保护**：管理员登录后上传和管理图片，访客仅可浏览
- 🖼️ **图片管理**：管理员可重命名、删除图片，支持递归目录清理
- 📋 **多格式链接**：一键复制直链、Markdown、HTML 代码
- 🎨 **现代界面**：毛玻璃效果、响应式布局、暗色/亮色自动适配（基于系统主题）
- 📱 **移动端适配**：底部悬浮上传按钮，触屏操作友好
- 🗂️ **自动分页**：图片列表按时间倒序排列，支持分页浏览
- 🛡️ **安全验证**：文件类型双重验证（MIME + 文件头），防止恶意文件上传

## 环境要求

- PHP >= 7.4
- 启用以下扩展：
    - `fileinfo`（用于 MIME 类型检测）
    - `session`（用于登录状态管理）
    - `json`（用于 API 交互）
- Web 服务器（Apache / Nginx）需配置重写规则（可选，用于友好 URL）

## 安装步骤

### 1. 下载源码

将 `index.html`、`config.php`、`action.php`、`upload.php` 上传至服务器同一目录。

### 2. 设置目录权限

- 确保 `uploads/` 目录存在且可写（如果系统没有自动创建请手动创建）：
- 建议将 `uploads/` 设置为禁止直接浏览（通过 `.htaccess` 或 Nginx 配置）。

### 3. 配置管理员密码

编辑 `config.php`，修改 `ADMIN_PASSWORD` 的值：

```php
define('ADMIN_PASSWORD', '你的密码');
```

### 4. 测试运行

访问 index.html，点击「管理登录」输入密码，即可开始上传和管理图片。

## 配置说明

所有配置集中在 config.php 中：

| 常量 | 说明 | 默认值 |
| ------ |------ |------ |
| ADMIN_PASSWORD | 管理员登录密码 | 123456 |
| UPLOAD_MAX_SIZE | 单张图片最大尺寸（字节） | 10 * 1024 * 1024 (10MB) |
| ALLOWED_TYPES | 允许的 MIME 类型数组 | ['image/jpeg', 'image/png', 'image/gif', 'image/webp'] |
| ALLOWED_EXT | 允许的文件扩展名数组 | ['jpg', 'jpeg', 'png', 'gif', 'webp'] |
| UPLOAD_DIR | 上传目录根路径 | 'uploads' |

**注意**：上传目录会自动按年月（例如 2025-03）创建子目录，文件名由日期和随机字符串组成，避免重名。

## 使用指南

### 登录管理

· 点击右上角「管理登录」→ 输入密码 → 登录后即可上传、删除、重命名图片。

### 上传图片

· **拖拽上传**：将图片文件拖拽到上传区域即可。
· **点击上传**：点击上传区域或「选择文件」按钮。
· **移动端**：点击右下角悬浮按钮选择图片。

### 管理图片

· **复制链接**：鼠标悬停图片，点击「🔗」图标；或在图片下方点击「直链」「MD」「HTML」按钮。
· **重命名**：登录后，点击图片下方的「重命名」按钮，输入新名称（不含扩展名）。
· **删除**：登录后，点击图片下方的「删除」按钮，确认后删除文件。如果文件夹为空，会自动删除。

### 浏览图片

· 图片列表按上传时间倒序排列，支持分页（每页 12 张）。
· 点击图片可预览大图。

## API 接口

所有接口返回 JSON，格式为：

```json
{
  "success": true/false,
  "message": "提示信息",
  ...其他数据
}
```

| 接口 | 方法 | 参数 | 说明 |
| ------ |------ |------ |------ |
| action.php?action=login | POST | password | 登录，成功后设置 session |
| action.php?action=logout | POST | - | 退出登录，销毁 session |
| action.php?action=check | GET/POST | - | 检查登录状态，返回 logged_in 布尔值 |
| action.php?action=list | GET | - | 获取所有图片信息（按时间倒序） |
| action.php?action=delete | POST | path | 删除指定路径的图片（需登录） |
| action.php?action=rename | POST | path, name | 重命名图片（需登录，仅修改文件名，保留扩展名） |
| upload.php | POST | file | 上传图片（需登录） |

**示例**（使用 curl 上传）：

```bash
curl -X POST -F "file=@/path/to/image.jpg" -b "PHPSESSID=..." upload.php
```

## 安全说明

· 所有路径操作均经过 realpath 和前缀检查，防止目录遍历攻击。
· 文件名使用正则过滤，仅保留字母、数字、下划线、连字符和中文。
· 文件上传通过 finfo 检测真实 MIME 类型，并验证文件头魔数，避免伪装图片。
· 登录状态基于 PHP session，默认 session.save_path 需确保安全。
· 建议为 uploads/ 目录添加 .htaccess 禁止 PHP 解析，防止 webshell 上传：

```
# .htaccess 示例
<FilesMatch "\.(php|phtml|php3|php4|php5|phps)$">
    Deny from all
</FilesMatch>
```

## 常见问题

**Q：上传时提示「不支持的文件类型」？**
A：请检查文件是否真的是图片，或者修改 ALLOWED_TYPES 和 ALLOWED_EXT 添加你需要的格式。

**Q：上传失败，提示「文件头验证失败」？**
A：这可能是文件损坏或被篡改，请使用正常图片重试。如确为合法图片，可调整 validHeaders 中的魔数检测逻辑。

**Q：图片列表为空，但 uploads/ 目录下有文件？**
A：请检查文件扩展名是否在 ALLOWED_EXT 中，以及目录结构是否正确（支持嵌套子目录）。系统会递归扫描所有子目录。

**Q：如何修改默认每页图片数量？**
A：编辑 index.html，找到 pageSize: 12，改为你需要的数值。

**Q：登录后上传进度条不动？**
A：可能是网络延迟或服务器配置问题，检查 php.ini 中 upload_max_filesize 和 post_max_size 是否大于 10MB。

## 更新日志

· v1.0
· 初始版本，实现基础上传、管理、预览功能
· 支持分页和移动端适配
· 文件安全验证机制

