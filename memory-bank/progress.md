# Bảng theo dõi tiến độ - Hiep-Antivirus

Đây là bảng trạng thái công việc theo các giai đoạn phát triển chính.

---

## 🔬 Giai đoạn CREATIVE (Thiết kế & Nghiên cứu)

| Trạng thái | Tác vụ | Ghi chú |
| :---: | --- | --- |
| ✅ **Hoàn thành** | Phân tích các loại mã độc phổ biến trên nền tảng PHP. | Đã tổng hợp danh sách các hàm độc hại, các kỹ thuật làm rối mã. |
| ✅ **Hoàn thành** | Thiết kế (mockup) giao diện người dùng cho bảng điều khiển. | Giao diện gồm: trang tổng quan, trang quét, trang cách ly, cài đặt. |
| ⏳ **Đang tiến hành** | Nghiên cứu các thuật toán hashing tệp hiệu quả. | So sánh MD5, SHA1, SHA256 về tốc độ và khả năng tránh xung đột. |
| ⏳ **Đang tiến hành** | Lựa chọn định dạng lưu trữ mẫu nhận diện (signatures). | Cân nhắc giữa regex, chuỗi cố định, và hash. Regex linh hoạt nhất. |

---

## 📐 Giai đoạn PLAN (Kiến trúc & Lên kế hoạch)

| Trạng thái | Tác vụ | Ghi chú |
| :---: | --- | --- |
| ✅ **Hoàn thành** | Thiết kế lược đồ (schema) cơ sở dữ liệu cho các bảng. | `*_antivirus_signatures`, `*_antivirus_logs`, `*_antivirus_quarantine`. |
| ⏳ **Đang tiến hành** | Hoàn thiện kiến trúc cơ chế cách ly và phục hồi tệp. | Cần đảm bảo lưu trữ an toàn và phục hồi đúng vị trí, đúng permission. |
| ⬜️ **Chưa bắt đầu** | Định nghĩa chi tiết các API endpoint cho AJAX. | Quy định rõ request parameters và JSON response format cho từng endpoint. |
| ⬜️ **Chưa bắt đầu** | Lên kế hoạch triển khai cho chức năng quét theo lịch trình (cron job). | Xác định cách cấu hình và cách script sẽ được gọi. |

---

## 💻 Giai đoạn IMPLEMENT (Lập trình & Xây dựng)

| Trạng thái | Tác vụ | Ghi chú |
| :---: | --- | --- |
| ✅ **Hoàn thành** | Xây dựng chức năng cơ bản để tải các mẫu nhận diện từ CSDL. | Đã có thể lấy danh sách active signatures. |
| ✅ **Hoàn thành** | Xây dựng bộ quét tệp ban đầu (duyệt đệ quy thư mục). | Đã có thể quét toàn bộ cây thư mục của trang web. |
| ⏳ **Đang tiến hành** | Tích hợp giao diện người dùng phần Quản lý Cách ly. | Gồm danh sách tệp bị cách ly, các nút hành động (xem, phục hồi, xóa). |
| ⏳ **Đang tiến hành** | Phát triển thành phần giám sát tệp tin thời gian thực (real-time). | Hiện đang dùng phương pháp polling, nghiên cứu giải pháp tốt hơn. |
| ⬜️ **Chưa bắt đầu** | Xây dựng chức năng tự động dọn dẹp (clean) payload độc hại. | |
| ⬜️ **Chưa bắt đầu** | Tích hợp giao diện báo cáo và xem nhật ký quét. | |

---

## ⚠️ Trở ngại & Rủi ro (Blockers)

- **Hiệu suất:** Bộ quét hiện tại có thể gây tắc nghẽn I/O trên các trang web có dung lượng lớn. Cần tối ưu hóa việc đọc tệp.
- **Dương tính giả (False Positives):** Bộ phân tích hành vi ban đầu có thể nhận diện nhầm các đoạn mã hợp lệ là mã độc, đặc biệt với các plugin phức tạp. Cần có cơ chế "danh sách trắng" (whitelist).
- **Môi trường Hosting:** Nguy cơ các hàm PHP quan trọng (`scandir`, `file_get_contents`,...) bị vô hiệu hóa trên một số máy chủ. 