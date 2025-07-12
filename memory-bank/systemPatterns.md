# Các Mẫu Hệ thống và Quy ước (System Patterns)

Tài liệu này mô tả các quy ước và mẫu kiến trúc cần tuân thủ khi phát triển module Hiep-Antivirus để đảm bảo nó tích hợp một cách nhất quán và liền mạch vào Mộng Truyện CMS.

## 1. Cấu trúc thư mục

- **`admin/sources/antivirus.php`**: Tệp điều khiển (controller) chính, xử lý logic cho trang quản trị của module Antivirus.
- **`admin/templates/antivirus/`**: Thư mục chứa các tệp giao diện (template/view) cho module.
  - `index_tpl.php`: Giao diện chính/bảng điều khiển.
  - `scan_tpl.php`: Giao diện cho chức năng quét.
  - `quarantine_tpl.php`: Giao diện quản lý các tệp bị cách ly.
  - `settings_tpl.php`: Giao diện cài đặt.
- **`sources/ajax/antivirus_*.php`**: Các tệp xử lý AJAX. Tên tệp phải thể hiện rõ chức năng.
  - `antivirus_scan_starter.php`: Bắt đầu một tiến trình quét mới.
  - `antivirus_scan_progress.php`: Lấy thông tin tiến độ quét.
  - `antivirus_quarantine_handler.php`: Xử lý các hành động liên quan đến cách ly (cách ly, phục hồi, xóa).
- **`/quarantine/`** (ở thư mục gốc): Thư mục chứa các tệp bị cách ly. Cần được bảo vệ bằng tệp `.htaccess` để cấm truy cập trực tiếp từ web.

## 2. Tương tác với Cơ sở dữ liệu

- **Bắt buộc sử dụng lớp `$d`**: Mọi truy vấn đến cơ sở dữ liệu phải được thực hiện thông qua đối tượng toàn cục `$d`, một thể hiện của lớp `DB_driver` trong `admin/lib/class.php`. Việc này đảm bảo sử dụng cùng một kết nối CSDL và tuân thủ các phương thức của CMS.
- **Quy ước đặt tên bảng**: Tất cả các bảng của module phải có tiền tố `#_antivirus_`. Tiền tố `#_` sẽ được lớp `$d` tự động thay thế bằng tiền tố CSDL thực tế của hệ thống.
  - `#_antivirus_signatures`
  - `#_antivirus_logs`
  - `#_antivirus_quarantine`
- **Ví dụ về truy vấn:**
  ```php
  // Luôn reset() trước khi thực hiện một truy vấn mới
  $d->reset();

  // Đặt bảng mục tiêu
  $d->setTable('#_antivirus_signatures');

  // Lấy danh sách các mẫu nhận diện đang hoạt động
  $signatures = $d->o_fet("SELECT * FROM #_antivirus_signatures WHERE is_active = 1");

  // Chèn một bản ghi log mới
  $log_data = [
      'scan_type' => 'manual',
      'files_scanned' => 1234,
      'threats_found' => 2,
      'scan_date' => time(),
  ];
  $d->setTable('#_antivirus_logs');
  $d->insert($log_data);
  ```

## 3. Quy ước định tuyến và URL

- **Trang quản trị chính**: Giao diện của module sẽ được truy cập thông qua URL `admin/index.php?com=antivirus&act=man`. Logic này được xử lý trong tệp `admin/index.php`.
- **Endpoint AJAX**: Các yêu cầu từ client-side sẽ được gửi đến các tệp trong `sources/ajax/`.
  - Ví dụ: `POST /sources/ajax/antivirus_scan_starter.php`

## 4. Quy ước đặt tên

- **Hàm PHP**: Sử dụng tiền tố `hiepav_` cho tất cả các hàm toàn cục của module để tránh xung đột tên với CMS hoặc các plugin khác. Ví dụ: `hiepav_scan_file()`, `hiepav_get_settings()`.
- **Biến JavaScript**: Các biến toàn cục (nếu có) nên được chứa trong một đối tượng duy nhất. Ví dụ: `var HiepAV = { settings: {}, scan_progress: 0 };`.
- **Lớp CSS**: Sử dụng tiền tố `hiepav-` cho các lớp CSS để tránh xung đột style. Ví dụ: `.hiepav-scan-button`, `.hiepav-results-table`. 