# Bối cảnh kỹ thuật (Technical Context)

## 1. Công nghệ sử dụng

- **Ngôn ngữ Backend:** PHP 7.4. Toàn bộ logic quét, xử lý tệp và tương tác với cơ sở dữ liệu đều được viết bằng PHP để đảm bảo tính tương thích tối đa với Mộng Truyện CMS.
- **Ngôn ngữ Frontend:** JavaScript (ES6+), chủ yếu dùng cho các lời gọi AJAX để cập nhật giao diện người dùng mà không cần tải lại trang (ví dụ: hiển thị tiến trình quét, kết quả). Tận dụng jQuery nếu có sẵn trong CMS.
- **Cơ sở dữ liệu:** MySQL. Được sử dụng để lưu trữ:
    - Các mẫu nhận diện mã độc (signatures).
    - Nhật ký quét (scan logs).
    - Thông tin các tệp bị cách ly (quarantine records).
    - Hash của các tệp tin gốc (baseline hashes).
- **Web Server:** Apache. Tận dụng `.htaccess` để bảo vệ các thư mục nhạy cảm của plugin (ví dụ: thư mục cách ly) và có thể tương tác với `mod_security` nếu có.
- **Lập lịch tác vụ (Scheduling):** Sử dụng Cron Job của hệ điều hành (hoặc một giải pháp thay thế như "Poor Man's Cron" bằng PHP) để thực hiện các cuộc quét tự động theo lịch trình.

## 2. Kiến trúc hệ thống

Kiến trúc của module được thiết kế để tách biệt giữa giao diện người dùng và bộ máy quét.

```mermaid
graph TD
    subgraph Browser
        A[Bảng điều khiển Admin]
    end

    subgraph Server
        B(AJAX Endpoint)
        C{Bộ máy quét (Scanner Engine)}
        D[Logic Cách ly]
        E[Logic Cập nhật]
    end

    subgraph "Data Storage"
        F(File System)
        G(MySQL Database)
    end

    A -- "1. Bắt đầu quét" --> B
    B -- "2. Gọi bộ máy quét" --> C
    C -- "3. Đọc tệp tin" --> F
    C -- "4. Lấy mẫu nhận diện" --> G
    C -- "5. Tìm thấy mã độc" --> D
    D -- "6. Di chuyển tệp" --> F
    D -- "7. Ghi nhận cách ly" --> G
    E -- "Cập nhật mẫu" --> G
```

- **Bảng điều khiển Admin:** Giao diện trong `/admin` để người dùng tương tác.
- **AJAX Endpoint:** Các tệp PHP trong `sources/ajax/` xử lý các yêu cầu bất đồng bộ từ giao diện.
- **Bộ máy quét (Scanner Engine):** Lớp PHP cốt lõi chịu trách nhiệm thực hiện logic quét.
- **Lưu trữ:** Tệp tin được quét trực tiếp trên hệ thống tệp, trong khi dữ liệu meta, logs và signatures được quản lý trong MySQL.

## 3. Thư viện và Framework chính

- **Không có framework ngoài:** Để đảm bảo tích hợp liền mạch và gọn nhẹ, module không phụ thuộc vào các framework PHP lớn như Laravel hay Symfony.
- **Lớp `$d`:** Toàn bộ các thao tác với cơ sở dữ liệu **bắt buộc** phải thông qua lớp `$d` (`admin/lib/class.php`) của CMS để đồng bộ với kiến trúc hiện có.
- **jQuery:** Sử dụng cho các thao tác DOM và gọi AJAX ở phía client, vì đây là thư viện đã có sẵn trong CMS.

## 4. Lưu ý về môi trường Hosting

- **Quyền truy cập tệp tin:** Tiến trình PHP của web server phải có quyền đọc trên toàn bộ các tệp của trang web và quyền ghi/xóa đối với thư mục cách ly.
- **Hiệu suất:** Các hoạt động I/O (đọc tệp) có thể tiêu tốn nhiều tài nguyên. Cần tối ưu hóa bộ máy quét để tránh làm quá tải máy chủ, đặc biệt là trên các gói hosting chia sẻ. Cân nhắc sử dụng các hàm như `set_time_limit()` để ngăn script bị dừng giữa chừng.
- **Các hàm PHP bị vô hiệu hóa:** Một số môi trường hosting có thể vô hiệu hóa các hàm PHP nhạy cảm như `shell_exec`, `scandir`, hoặc `file_get_contents`. Cần kiểm tra và có phương án dự phòng. 