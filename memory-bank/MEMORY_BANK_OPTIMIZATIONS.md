# Các chiến lược tối ưu hóa Memory Bank

Mục tiêu của tài liệu này là đề ra các phương pháp để giữ cho Memory Bank luôn gọn gàng, hiệu quả và hữu ích, đặc biệt là khi làm việc với các trợ lý AI. Tối ưu hóa giúp AI nhanh chóng tìm thấy thông tin cần thiết và giảm lượng token không cần thiết.

## 1. Định dạng ưu tiên Token (Token-Aware Formatting)

- **Ngôn ngữ súc tích:** Sử dụng ngôn ngữ rõ ràng, đi thẳng vào vấn đề. Ưu tiên gạch đầu dòng và bảng biểu thay vì các đoạn văn dài dòng.
- **Cấu trúc rõ ràng:** Sử dụng các tiêu đề (headings `#`, `##`) một cách nhất quán để phân cấp thông tin. Điều này giúp AI dễ dàng "đọc lướt" và xác định các phần quan trọng.
- **Khối mã (Code Blocks):** Luôn sử dụng các khối mã có rào chắn (fenced code blocks) với định danh ngôn ngữ rõ ràng (ví dụ: ` ```php `) để AI có thể phân tích và hiểu mã nguồn một cách chính xác.

## 2. Các đoạn trích tái sử dụng (Reusable Snippets)

- **Nguyên tắc DRY (Don't Repeat Yourself):** Đối với các thông tin lặp đi lặp lại, hãy tạo một nơi lưu trữ trung tâm.
- **Ví dụ về ứng dụng:**
  - Tạo một tệp `_snippets.md` hoặc một phần riêng trong `systemPatterns.md` để lưu các đoạn mã thường dùng.
  - **Mẫu mối đe dọa (Threat Patterns):** Lưu các ví dụ về payload độc hại.
    ```php
    // Snippet: B64_GZINFLATE_PAYLOAD
    eval(gzinflate(base64_decode('...some long encoded string...')));
    ```
  - Khi cần đề cập đến nó trong một tài liệu khác, chỉ cần tham chiếu đến snippet thay vì sao chép lại toàn bộ. Điều này giúp tiết kiệm token và đảm bảo tính nhất quán.

## 3. Quy trình làm việc với Sơ đồ Mermaid

- **Trực quan hóa kiến trúc:** Sử dụng Mermaid để vẽ các sơ đồ luồng dữ liệu, kiến trúc hệ thống, hoặc chuỗi sự kiện. Sơ đồ giúp truyền tải các khái niệm phức tạp một cách nhanh chóng và rõ ràng hơn văn bản thuần túy.
- **Ví dụ về luồng quét AJAX:**
  ```mermaid
  sequenceDiagram
      participant User as Người dùng
      participant Browser as Trình duyệt
      participant Server as Máy chủ (PHP)
      participant DB as CSDL (MySQL)

      User->>Browser: Nhấn nút "Quét ngay"
      Browser->>Server: Gửi yêu cầu AJAX để bắt đầu quét
      Server->>DB: Lấy danh sách tệp và mẫu nhận diện
      Server-->>Browser: Phản hồi ID của phiên quét
      loop Lấy tiến trình
          Browser->>Server: Gửi yêu cầu AJAX lấy tiến trình
          Server-->>Browser: Trả về % hoàn thành và kết quả tạm thời
      end
      Browser->>User: Hiển thị kết quả quét cuối cùng
  ```
- **Lợi ích:** AI có thể phân tích cú pháp Mermaid để hiểu được các mối quan hệ và luồng đi trong hệ thống.

## 4. Tải theo ngữ cảnh (Incremental Loading)

- **Nguyên tắc:** Hướng dẫn AI chỉ tải những tài liệu thực sự cần thiết cho tác vụ hiện tại.
- **Chiến lược:**
  - **Đặt tên tệp rõ ràng:** Tên tệp phải thể hiện chính xác nội dung bên trong (ví dụ: `techContext.md`, `systemPatterns.md`).
  - **Tạo "tệp chỉ mục":** `README.md` hoạt động như một tệp chỉ mục, giúp AI hiểu được mục đích của từng tệp trong Memory Bank.
  - **Đưa ra chỉ dẫn rõ ràng:** Khi yêu cầu AI thực hiện một tác vụ, hãy gợi ý những tệp cần tham khảo.
    - *Ví dụ:* "Hãy viết hàm `hiepav_scan_file()`, tuân thủ các quy ước trong `systemPatterns.md` và sử dụng các công nghệ được mô tả trong `techContext.md`."

- **Lợi ích:** Giảm đáng kể số lượng token được đưa vào ngữ cảnh của AI, giúp tăng tốc độ phản hồi, giảm chi phí và tăng độ chính xác của kết quả. 