# README: Kho tri thức (Memory Bank) cho dự án Hiep-Antivirus

Chào mừng bạn đến với kho tri thức của dự án Hiep-Antivirus. Thư mục `memory-bank/` này là nguồn thông tin trung tâm, đáng tin cậy cho tất cả mọi thứ liên quan đến việc phát triển, bảo trì và mở rộng module Antivirus cho Mộng Truyện CMS.

## 1. Thư mục này chứa những gì?

Kho tri thức này được tổ chức thành các tệp Markdown riêng biệt, mỗi tệp phục vụ một mục đích cụ thể:

- **`projectbrief.md`**: Điểm khởi đầu. Trả lời các câu hỏi "Tại sao?" và "Cái gì?" về dự án.
- **`techContext.md`**: Tổng quan về ngăn xếp công nghệ (tech stack), kiến trúc hệ thống và các yếu tố kỹ thuật liên quan.
- **`productContext.md`**: Mô tả về sản phẩm, đối tượng người dùng mục tiêu và giá trị mà nó mang lại.
- **`progress.md`**: Bảng theo dõi tiến độ công việc theo các giai đoạn phát triển.
- **`systemPatterns.md`**: Các quy ước về mã hóa, cấu trúc thư mục, tương tác CSDL và các mẫu thiết kế cần tuân thủ.
- **`activeContext.md`**: "Nhật ký làm việc" của đội ngũ, ghi lại những gì đang được tập trung, các quyết định gần đây và các công việc cần làm tiếp theo.
- **`memory_bank_upgrade_guide.md`**: Lộ trình phát triển cho chính kho tri thức này.
- **`MEMORY_BANK_OPTIMIZATIONS.md`**: Các chiến lược để giữ cho tài liệu này hiệu quả, đặc biệt khi làm việc với các trợ lý AI.
- **`creative_mode_think_tool.md`**: Một khuôn khổ để phân tích và giải quyết các vấn đề phức tạp.
- **`README.md`**: Chính là tệp bạn đang đọc, đóng vai trò là bản đồ chỉ dẫn.

## 2. Làm thế nào để sử dụng kho tri thức này?

- **Đối với một nhà phát triển mới tham gia dự án:**
  1.  Bắt đầu với `README.md` (tệp này).
  2.  Đọc `projectbrief.md` để hiểu mục tiêu tổng thể.
  3.  Nghiên cứu `techContext.md` và `systemPatterns.md` để nắm vững các quy ước và kiến trúc kỹ thuật.

- **Khi bắt đầu một ngày làm việc:**
  - Tham khảo `progress.md` và `activeContext.md` để cập nhật tình hình và xác định các nhiệm vụ ưu tiên.

- **Khi cần đưa ra một quyết định thiết kế quan trọng:**
  - Sử dụng các phương pháp trong `creative_mode_think_tool.md`.
  - Ghi lại quyết định của bạn vào `activeContext.md`.

- **Khi làm việc với Trợ lý AI (như Gemini):**
  - Hãy chỉ dẫn cho AI tham chiếu đến các tệp cụ thể trong kho tri thức này để có được câu trả lời chính xác và phù hợp với ngữ cảnh dự án. Ví dụ: *"Hãy tạo cho tôi hàm X, tuân theo các quy tắc trong `systemPatterns.md`."*

## 3. Kho tri thức này dành cho ai?

- **Đội ngũ phát triển (Development Team):** Đối tượng chính. Đây là nguồn tài liệu cốt lõi để làm việc một cách nhất quán và hiệu quả.
- **Kiểm thử viên chất lượng (QA Testers):** Để hiểu các tính năng và các kịch bản cần kiểm thử.
- **Kiểm toán viên bảo mật (Security Auditors):** Để hiểu rõ về khả năng, kiến trúc và các giới hạn của trình quét.
- **Những người bảo trì trong tương lai (Future Maintainers):** Để nhanh chóng nắm bắt dự án và tiếp tục phát triển nó.

## 4. Cách thức duy trì và đóng góp

Tài liệu này là một tài liệu "sống". Nó chỉ hữu ích khi được cập nhật thường xuyên.

- **Nguyên tắc:** Cập nhật tài liệu là một phần của quy trình làm việc, không phải là một công việc làm sau cùng.
- **Quy trình:**
  1.  Khi có một sự thay đổi về kiến trúc, một quyết định mới, hoặc một quy ước mới, hãy cập nhật ngay lập-tức vào tệp Markdown tương ứng.
  2.  Commit các thay đổi đối với tài liệu cùng với các thay đổi về mã nguồn liên quan.
  3.  Sử dụng các thông điệp commit rõ ràng, ví dụ: `docs(techContext): Bổ sung thông tin về việc sử dụng Redis để cache`. 