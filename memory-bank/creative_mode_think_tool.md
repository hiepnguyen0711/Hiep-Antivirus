# Công cụ tư duy cho Chế độ Sáng tạo (CREATIVE Mode)

Tài liệu này cung cấp một khuôn khổ để áp dụng tư duy sáng tạo và phân tích khi giải quyết các vấn đề phức tạp trong dự án Hiep-Antivirus. Mục đích là để đảm bảo các giải pháp không chỉ hiệu quả về mặt kỹ thuật mà còn được cân nhắc kỹ lưỡng về các yếu tố khác.

## 1. Phân rã bài toán (Decomposing Tasks)

Trước khi bắt tay vào giải quyết một vấn đề lớn, hãy chia nhỏ nó thành các câu hỏi hoặc các thành phần nhỏ hơn, dễ quản lý hơn.

**Ví dụ: Bài toán "Quét mã độc trong một tệp"**

Bài toán này có thể được phân rã thành:
1.  **Làm thế nào để lấy nội dung tệp?**
    - Sử dụng `file_get_contents()`? Có rủi ro về bộ nhớ với các tệp lớn không?
    - Hay đọc tệp theo từng đoạn (chunk)?
2.  **Chúng ta đang tìm kiếm cái gì?**
    - So khớp với các mẫu nhận diện (signatures) từ CSDL?
    - Tìm kiếm các hàm PHP nguy hiểm (`eval`, `system`, `passthru`)?
    - Phát hiện các đoạn mã bị làm rối (obfuscated code)?
3.  **Làm thế nào để so khớp một cách hiệu quả?**
    - Dùng `strpos()` cho các chuỗi đơn giản?
    - Dùng `preg_match()` cho các biểu thức chính quy (regex)? Hiệu suất sẽ bị ảnh hưởng như thế nào?
4.  **Hành động tiếp theo khi tìm thấy mối đe dọa là gì?**
    - Chỉ ghi nhận (log) lại?
    - Gắn cờ tệp là "nghi ngờ"?
    - Ngay lập tức di chuyển tệp vào khu cách ly?
5.  **Làm thế nào để xử lý các trường hợp đặc biệt?**
    - Tệp không thể đọc được do quyền truy cập?
    - Tệp nhị phân (binary file) có nên được quét không?

## 2. Mô hình hóa mối đe dọa (Threat Modeling)

Đối với mỗi tính năng, hãy đặt mình vào vị trí của kẻ tấn công và suy nghĩ về các cách họ có thể khai thác hoặc phá vỡ nó.

| Mục tiêu của kẻ tấn công | Mối đe dọa / Kỹ thuật tấn công | Cách Hiep-Antivirus phát hiện/ngăn chặn |
| :--- | :--- | :--- |
| **Giành quyền truy cập dai dẳng** | Tải lên một web shell (cửa hậu) đơn giản. | Quét các tệp mới/thay đổi để tìm các hàm thực thi lệnh (`shell_exec`, `system`) và các mẫu web shell phổ biến. |
| **Ẩn giấu mã độc** | Sử dụng các kỹ thuật làm rối mã như base64, gzinflate, nối chuỗi. | Bộ quét hành vi (heuristic scanner) sẽ tìm kiếm các mẫu `eval(base64_decode(...))`, `eval(gzinflate(...))`, v.v. |
| **Vô hiệu hóa trình diệt virus** | Chèn mã vào chính các tệp của Hiep-Antivirus để nó tự đưa mình vào danh sách trắng. | Tính năng quét lõi (Core Scanner) phải bao gồm cả việc xác minh tính toàn vẹn của chính các tệp của Hiep-Antivirus. |
| **Gây quá tải hệ thống** | Tải lên một tệp cực lớn để làm treo trình quét. | Bộ quét cần có giới hạn về kích thước tệp tối đa sẽ quét và xử lý thời gian chạy một cách thông minh (`set_time_limit`). |

## 3. Phân tích sự đánh đổi (Trade-off Analysis)

Hầu hết các quyết định kỹ thuật đều có sự đánh đổi. Việc ghi nhận rõ ràng các đánh đổi này giúp chúng ta đưa ra lựa chọn phù hợp nhất với mục tiêu của dự án.

**Ví dụ: Đánh đổi giữa "Độ sâu quét" và "Hiệu suất"**

| Phương án | Ưu điểm | Nhược điểm | Quyết định |
| :--- | :--- | :--- | :--- |
| **Quét Nhanh (Quick Scan)** | - Rất nhanh.<br>- Ít tốn tài nguyên. | - Chỉ kiểm tra các tệp mới/thay đổi dựa trên hash.<br>- Có thể bỏ sót mã độc đã tồn tại từ trước nếu baseline không sạch. | **Sử dụng cho các lần quét tự động, theo lịch trình** để giám sát liên tục mà không ảnh hưởng nhiều đến hiệu suất. |
| **Quét Sâu (Deep Scan)** | - Quét toàn bộ nội dung của mọi tệp bằng regex.<br>- Khả năng phát hiện cao nhất. | - Rất chậm.<br>- Tiêu tốn nhiều CPU và I/O. | **Sử dụng cho các lần quét thủ công** khi quản trị viên nghi ngờ có sự cố hoặc sau một cuộc tấn công. |

## 4. Ghi nhận quyết định (Decision Documentation)

Mọi quyết định quan trọng về kiến trúc hoặc công nghệ phải được ghi lại. Sử dụng một định dạng nhất quán trong `activeContext.md` hoặc một tệp nhật ký quyết định riêng.

- **Định dạng đề xuất:**
  - **Quyết định:** [Nêu rõ quyết định đã được đưa ra].
  - **Lý do:** [Giải thích tại sao quyết định này được chọn, dựa trên các phân tích ở trên].
  - **Các phương án đã cân nhắc:** [Liệt kê ngắn gọn các lựa chọn khác và tại sao chúng không được chọn].
  - **Hệ quả:** [Mô tả những ảnh hưởng của quyết định này đến các phần khác của dự án]. 