# 🔒 HƯỚNG DẪN TRIỂN KHAI BẢO MẬT ADMIN CMS

## 📋 Tổng quan
Hướng dẫn này giúp bạn triển khai các bản vá bảo mật cho hệ thống Admin CMS trên nhiều hosting website khác nhau.

## 🎯 Mục tiêu bảo mật
- ✅ Ngăn chặn truy cập trực tiếp vào backend files
- ✅ Bảo vệ khỏi lỗ hỏng upload file
- ✅ Chống CSRF và XSS attacks
- ✅ Bảo mật CKEditor và File Manager
- ✅ Rate limiting và access control

## 📁 Cấu trúc files bảo mật

```
admin/
├── .htaccess                    # Bảo mật chính cho admin
├── security_patches.php         # Class bảo mật PHP
├── sources/
│   └── .htaccess               # Bảo vệ backend files
├── filemanager/
│   ├── .htaccess               # Bảo mật file manager
│   └── security_config.php     # Config bảo mật nâng cao
├── ckeditor/
│   └── .htaccess               # Bảo mật CKEditor
└── lib/
    └── .htaccess               # Bảo vệ thư viện
```

## 🚀 Bước 1: Chuẩn bị triển khai

### 1.1 Backup hệ thống hiện tại
```bash
# Tạo backup toàn bộ thư mục admin
cp -r admin/ admin_backup_$(date +%Y%m%d_%H%M%S)/

# Hoặc tạo file zip
zip -r admin_backup_$(date +%Y%m%d_%H%M%S).zip admin/
```

### 1.2 Kiểm tra quyền thư mục
```bash
# Đảm bảo quyền ghi cho logs
chmod 755 admin/logs/
chmod 644 admin/logs/*.log

# Quyền cho các file config
chmod 644 admin/lib/config.php
chmod 644 admin/filemanager/config/config.php
```

## 🔧 Bước 2: Upload files bảo mật

### 2.1 Upload qua FTP/SFTP
```bash
# Upload các file .htaccess
put admin/.htaccess
put admin/sources/.htaccess
put admin/filemanager/.htaccess
put admin/ckeditor/.htaccess
put admin/lib/.htaccess

# Upload files PHP bảo mật
put admin/security_patches.php
put admin/filemanager/security_config.php
```

### 2.2 Upload qua File Manager hosting
1. Đăng nhập vào hosting control panel
2. Mở File Manager
3. Navigate đến thư mục admin/
4. Upload từng file theo đúng đường dẫn

### 2.3 Upload qua cPanel
1. Vào cPanel → File Manager
2. Chọn thư mục public_html/admin/
3. Upload files và extract nếu cần

## ⚙️ Bước 3: Cấu hình bảo mật

### 3.1 Cập nhật file index.php
Thêm vào đầu file `admin/index.php`:

```php
<?php
// Include security patches
require_once 'security_patches.php';

// Existing code...
```

### 3.2 Cập nhật File Manager
Thêm vào đầu file `admin/filemanager/config/config.php`:

```php
<?php
// Include security config
require_once 'security_config.php';

// Existing config...
```

### 3.3 Cấu hình CSRF Protection
Thêm vào các form trong admin:

```php
<?php echo $hiepSecurity->getCSRFTokenInput(); ?>
```

## 🧪 Bước 4: Kiểm tra và xác minh

### 4.1 Test truy cập trực tiếp
Thử truy cập các URL sau (phải trả về 403/404):
```
https://yourdomain.com/admin/sources/san-pham.php
https://yourdomain.com/admin/lib/config.php
https://yourdomain.com/admin/filemanager/upload.php
```

### 4.2 Test upload file
1. Thử upload file .php → Phải bị chặn
2. Thử upload file .jpg.php → Phải bị chặn
3. Thử upload file hợp lệ → Phải thành công

### 4.3 Test CSRF Protection
1. Thử submit form không có CSRF token → Phải bị chặn
2. Submit form có token hợp lệ → Phải thành công

## 🌐 Bước 5: Triển khai cho nhiều website

### 5.1 Tạo script tự động
```bash
#!/bin/bash
# deploy_security.sh

WEBSITES=(
    "website1.com"
    "website2.com" 
    "website3.com"
)

for site in "${WEBSITES[@]}"; do
    echo "Deploying security patches to $site..."
    
    # Upload via rsync
    rsync -avz --progress admin/ user@$site:/public_html/admin/
    
    echo "Deployed to $site successfully!"
done
```

### 5.2 Sử dụng Ansible (nâng cao)
```yaml
# security_deployment.yml
---
- hosts: webservers
  tasks:
    - name: Upload security files
      copy:
        src: "{{ item }}"
        dest: "/var/www/html/admin/{{ item }}"
      with_items:
        - .htaccess
        - security_patches.php
        - sources/.htaccess
        - filemanager/.htaccess
        - filemanager/security_config.php
        - ckeditor/.htaccess
        - lib/.htaccess
```

## 🔍 Bước 6: Monitoring và bảo trì

### 6.1 Kiểm tra logs bảo mật
```bash
# Xem logs bảo mật
tail -f admin/logs/security_patches.log

# Tìm các attempt tấn công
grep "CRITICAL\|WARNING" admin/logs/security_patches.log
```

### 6.2 Cập nhật định kỳ
- Kiểm tra logs hàng tuần
- Cập nhật patterns malicious code
- Review và cập nhật whitelist/blacklist

### 6.3 Backup logs
```bash
# Backup logs hàng tháng
tar -czf security_logs_$(date +%Y%m).tar.gz admin/logs/
```

## 🚨 Xử lý sự cố

### Lỗi 500 Internal Server Error
1. Kiểm tra syntax file .htaccess
2. Kiểm tra quyền file (644 cho .htaccess)
3. Kiểm tra error logs của hosting

### File Manager không hoạt động
1. Kiểm tra session PHP
2. Kiểm tra đường dẫn include files
3. Tạm thời disable security để debug

### Upload file bị chặn
1. Kiểm tra extension trong whitelist
2. Kiểm tra kích thước file
3. Xem logs để biết lý do cụ thể

## 📞 Hỗ trợ

### Liên hệ
- Email: nguyenvanhiep0711@gmail.com
- Website: hiepcodeweb.com

### Tài liệu tham khảo
- OWASP Security Guidelines
- PHP Security Best Practices
- Apache .htaccess Documentation

---
**Lưu ý**: Luôn test trên môi trường development trước khi triển khai production!
