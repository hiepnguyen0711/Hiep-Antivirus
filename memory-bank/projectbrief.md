# Tổng quan dự án Hiep-Antivirus

## 1. Mục đích

**Hiep-Antivirus** là một module bảo mật chuyên dụng được thiết kế để tích hợp vào Mộng Truyện CMS. Mục tiêu chính là bảo vệ các trang web sử dụng CMS này khỏi các mối đe dọa bảo mật phổ biến dựa trên tệp tin, chẳng hạn như mã độc, cửa hậu (backdoor), và các payload được chèn vào.

## 2. Các tính năng chính

- **Quét lõi CMS (CMS Core File Scanner):** Xác minh tính toàn vẹn của các tệp tin lõi của Mộng Truyện CMS bằng cách so sánh hash của chúng với một bản hash gốc, sạch.
- **Quét mã độc theo mẫu (Malware Signature Scanner):** Quét toàn bộ hệ thống tệp của trang web để tìm kiếm các mẫu (signatures) mã độc đã biết.
- **Phân tích hành vi đáng ngờ (Heuristic Scanner):** Tìm kiếm các đoạn mã đáng ngờ hoặc các mẫu lập trình thường được sử dụng trong mã độc, ví dụ như `eval(base64_decode(...))`, các hàm gọi hệ thống (`shell_exec`, `passthru`), và các đoạn mã bị làm rối (obfuscated code).
- **Gỡ bỏ mã độc (Malware Removal):** Cung cấp chức năng để gỡ bỏ một cách an toàn các đoạn mã độc đã được xác định khỏi các tệp tin bị lây nhiễm.
- **Cách ly (Quarantine):** Di chuyển các tệp tin bị nghi ngờ hoặc bị nhiễm mã độc vào một thư mục cách ly an toàn, ngăn chặn chúng thực thi và gây hại cho hệ thống.
- **Bảng điều khiển quản trị (Admin Dashboard):** Giao diện người dùng trực quan trong khu vực quản trị để xem kết quả quét, quản lý các tệp tin trong khu vực cách ly, cập nhật các mẫu nhận diện và cấu hình lịch quét tự động.

## 3. Bối cảnh kinh doanh

Mộng Truyện CMS là một nền tảng phổ biến, và việc các trang web xây dựng trên nó bị tấn công có thể dẫn đến mất dữ liệu, ảnh hưởng tiêu cực đến SEO, và làm suy giảm uy tín của chủ sở hữu trang web. Module Hiep-Antivirus cung cấp một lớp bảo vệ quan trọng, giúp các quản trị viên trang web yên tâm hơn và tăng cường giá trị cho nền tảng CMS.

## 4. Mục tiêu và Phạm vi

### Mục tiêu
- Tăng cường đáng kể mức độ bảo mật và độ tin cậy của các trang web chạy Mộng Truyện CMS.
- Giảm thiểu rủi ro bị tấn công và thời gian cần thiết để khắc phục sau khi bị tấn công.
- Cung cấp một công cụ bảo mật dễ sử dụng cho cả người dùng không chuyên về kỹ thuật.

### Phạm vi
- **Trong phạm vi:**
  - Phát hiện và xử lý các mối đe dọa dựa trên tệp tin trong thư mục gốc của trang web (document root).
  - Giám sát sự thay đổi của tệp tin.
  - Tích hợp vào giao diện quản trị hiện có.
- **Ngoài phạm vi:**
  - Các cuộc tấn công cấp độ mạng (ví dụ: DDoS).
  - Lỗ hổng SQL Injection hoặc XSS (mặc dù module có thể phát hiện các tệp được tải lên từ các cuộc tấn công này).
  - Bảo vệ máy chủ ở cấp độ hệ điều hành. 