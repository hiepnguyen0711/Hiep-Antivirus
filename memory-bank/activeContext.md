# Bối cảnh hoạt động (Active Context)

Tài liệu này ghi lại những công việc đang được tập trung phát triển, các quyết định gần đây và danh sách công việc cần làm (TODOs) trong ngắn hạn. Cập nhật lần cuối: `date +"%Y-%m-%d"`

## 1. Trọng tâm phát triển hiện tại

- **Tích hợp cơ chế giám sát tệp tin (File-Watcher Integration):**
  - **Mô tả:** Đang phát triển một cơ chế để phát hiện các thay đổi (thêm mới, chỉnh sửa, xóa) đối với các tệp tin trong hệ thống.
  - **Tình trạng:** Giải pháp hiện tại là một "poller" chạy theo cron job, thực hiện hash tệp và so sánh với baseline. Cách tiếp cận này tương thích rộng rãi nhưng có độ trễ. Đang đánh giá tính khả thi của việc sử dụng `inotify` trên các môi trường hỗ trợ để có giám sát thời gian thực.

- **Chức năng làm sạch Payload (Payload Cleaning Function):**
  - **Mô tả:** Xây dựng một hàm có khả năng "phẫu thuật" để loại bỏ các đoạn mã độc đã biết ra khỏi tệp bị nhiễm, thay vì chỉ xóa hoặc cách ly toàn bộ tệp. Điều này hữu ích cho các tệp lõi của CMS bị chèn mã.
  - **Tình trạng:** Đã phát triển logic regex để xác định và loại bỏ các payload `base64_decode(gzinflate(...))` phổ biến. Đang cần kiểm thử trên nhiều biến thể khác nhau.

- **Hoàn thiện Lược đồ CSDL cho khu vực cách ly (Quarantine DB Schema):**
  - **Mô tả:** Chốt lại thiết kế cho bảng `#_antivirus_quarantine`.
  - **Tình trạng:** Lược đồ hiện tại bao gồm các trường: `id`, `original_path` (đường dẫn gốc), `quarantine_path` (đường dẫn trong khu cách ly), `file_hash` (mã hash của tệp), `threat_info` (mô tả mối đe dọa), `quarantined_at` (thời điểm cách ly). Cần bổ sung trường `original_permissions` để có thể phục hồi tệp với đúng quyền truy cập.

## 2. Các quyết định gần đây

- **Quyết định:** Sử dụng Regex làm định dạng chính cho các mẫu nhận diện mã độc.
  - **Lý do:** Regex cung cấp sự linh hoạt cao nhất để đối phó với các biến thể mã độc và các kỹ thuật làm rối mã. Mặc dù chậm hơn so với tìm kiếm chuỗi đơn giản, nhưng độ chính xác cao hơn là ưu tiên hàng đầu.
  - **Phương án đã cân nhắc:** Tìm kiếm chuỗi cố định (nhanh nhưng không hiệu quả với mã độc biến thể), hash (chỉ hiệu quả với các tệp độc hại không đổi).

- **Quyết định:** Chức năng giám sát "thời gian thực" mặc định sẽ dựa trên cron job polling.
  - **Lý do:** Tính tương thích là yếu tố quan trọng nhất. Cron job được hỗ trợ trên hầu hết các môi trường hosting, trong khi `inotify` thì không. Giám sát bằng `inotify` sẽ được phát triển như một tính năng nâng cao, có thể bật nếu môi trường cho phép.

## 3. Danh sách công việc cần làm (TODOs)

- `[ ]` **Giao diện:** Xây dựng giao diện "Thêm mẫu nhận diện mới" trong trang quản trị.
- `[ ]` **Chức năng:** Hoàn thiện chức năng "Phục hồi từ khu cách ly", bao gồm việc khôi phục đúng quyền truy cập tệp.
- `[ ]` **Backend:** Viết logic để xử lý việc đưa một tệp/thư mục vào "danh sách trắng" (whitelist) để bộ quét bỏ qua.
- `[ ]` **Kiểm thử:** Viết các bài kiểm thử (unit tests) cho hàm làm sạch payload với nhiều loại mã độc khác nhau.
- `[ ]` **Tối ưu hóa:** Phân tích hiệu suất (profile) của bộ quét trên một trang web có kích thước lớn (>10,000 tệp) để tìm và khắc phục các điểm tắc nghẽn. 