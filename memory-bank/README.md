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

---

## 5. Tổng quan về Module Quét Bảo mật (Security Scan Module)

Phần này cung cấp thông tin chi tiết về `security_scan.php`, công cụ quét bảo mật cốt lõi của dự án.

### 5.1. Hướng dẫn sử dụng

Module quét hoạt động thông qua các endpoint API đơn giản. Bạn có thể tương tác với nó bằng các công cụ như `curl` hoặc giao diện người dùng được xây dựng để gọi các URL này.

- **Quét toàn bộ hệ thống:**
  - **Endpoint:** `GET /security_scan.php?scan=1`
  - **Chức năng:** Bắt đầu quá trình quét các thư mục đã định cấu hình (`./sources`, `./admin`, `./uploads`, `./`). Script sẽ trả về một đối tượng JSON chứa danh sách các tệp đáng ngờ (`suspicious_files`), tệp chứa mã độc (`malware_files`), và các tệp chỉ mang tính cảnh báo (`warning_files`).
  - **Ví dụ:** `curl "http://your-site.com/security_scan.php?scan=1"`

- **Xóa các tệp mã độc:**
  - **Endpoint:** `POST /security_scan.php?delete_malware=1`
  - **Chức năng:** Nhận một danh sách các tệp cần xóa dưới dạng JSON. Script sẽ tự động sao lưu các tệp này vào một thư mục backup trước khi xóa để đảm bảo an toàn.
  - **Dữ liệu POST:**
    ```json
    {
      "malware_files": [
        "path/to/malicious_file1.php",
        "path/to/malicious_file2.php"
      ]
    }
    ```

- **Tự động sửa lỗi:**
  - **Endpoint:** `POST /security_scan.php?autofix=1`
  - **Chức năng:** Nhận một đối tượng JSON chứa kết quả quét và cố gắng tự động sửa các lỗi đã biết. Chức năng này cần được sử dụng một cách cẩn trọng.
  - **Dữ liệu POST:**
    ```json
    {
      "suspicious_files": {
        "path/to/suspicious_file.php": ["eval(", "base64_decode("]
      },
      "malware_files": [],
      "warning_files": {}
    }
    ```

### 5.2. Định hướng phát triển

- **Triết lý "Phòng thủ theo chiều sâu":** Module không chỉ tìm kiếm các mẫu mã độc đã biết (signatures) mà còn phân tích các hàm và mẫu mã đáng ngờ (heuristics). Cách tiếp cận này giúp phát hiện cả những mối đe dọa mới, chưa từng được biết đến.
- **Ưu tiên sự an toàn và khả năng phục hồi:** Mọi hành động xóa hoặc sửa đổi tệp đều phải được sao lưu trước. Điều này đảm bảo rằng nếu có lỗi xảy ra (ví dụ: sửa nhầm tệp hợp lệ), quản trị viên có thể dễ dàng khôi phục lại trạng thái ban đầu.
- **Tích hợp thay vì độc lập:** Module được thiết kế để tích hợp chặt chẽ với Mộng Truyện CMS, tận dụng các cấu trúc sẵn có như lớp CSDL `$d` và hệ thống quản trị. Nó không phải là một công cụ độc lập.
- **Hiệu suất là yếu tố quan trọng:** Trình quét phải được tối ưu hóa để giảm thiểu ảnh hưởng đến hoạt động của trang web, đặc biệt là trên các môi trường hosting chia sẻ.

### 5.3. Chức năng hướng tới (Roadmap)

Dựa trên nền tảng hiện tại của `security_scan.php`, các tính năng tiếp theo trong lộ trình phát triển bao gồm:

- **Giao diện người dùng hoàn chỉnh:** Xây dựng một giao diện quản trị thân thiện để người dùng có thể thực hiện mọi thao tác (quét, xem kết quả, xóa, sửa lỗi, xem backup) mà không cần dùng đến dòng lệnh.
- **Quản lý mẫu nhận diện (Signature Management):** Cho phép quản trị viên tự thêm, sửa, xóa các mẫu nhận diện mã độc và cảnh báo thông qua giao diện quản trị. Các mẫu này sẽ được lưu trong CSDL.
- **Quét theo lịch trình (Scheduled Scanning):** Tích hợp với cron job để tự động thực hiện quét định kỳ (hàng ngày, hàng tuần) và gửi email thông báo cho quản trị viên nếu phát hiện vấn đề.
- **So sánh và giám sát tệp tin:** Xây dựng chức năng tạo "ảnh chụp" (snapshot) ban đầu của hệ thống tệp và sau đó so sánh để phát hiện các thay đổi (tệp mới, tệp bị sửa đổi). Đây là một cách hiệu quả để phát hiện các cuộc tấn công.
- **Danh sách trắng (Whitelisting):** Cho phép quản trị viên đánh dấu các tệp hoặc cảnh báo cụ thể là "an toàn" để chúng không bị báo cáo trong các lần quét sau.
- **Báo cáo và thống kê chi tiết:** Cung cấp các biểu đồ và thống kê về tình hình bảo mật của trang web theo thời gian. 