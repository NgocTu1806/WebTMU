# TÀI LIỆU ĐẶC TẢ & MÔ TẢ CHI TIẾT CÁC CHỨC NĂNG HỆ THỐNG
# HỆ THỐNG CHIA SẺ TÀI LIỆU HỌC TẬP TMU (TMU DOCUMENT SHARING)

---

## MỤC LỤC
1. [Giới Thiệu Tổng Quan Hệ Thống](#1-giới-thiệu-tổng-quan-hệ-thống)
2. [Các Tác Nhân Trong Hệ Thống (Actors)](#2-các-tác-nhân-trong-hệ-thống-actors)
3. [Mô Tả Chi Tiết Các Phân Hệ Chức Năng](#3-mô-tả-chi-tiết-các-phân-hệ-chức-năng)
   - [3.1. Phân hệ Trang chủ & Giao diện toàn trang (Homepage & Layout)](#31-phân-hệ-trang-chủ--giao-diện-toàn-trang-homepage--layout)
   - [3.2. Phân hệ Danh mục Khoa/Viện & Môn học (Academic Categories)](#32-phân-hệ-danh-mục-khoaviện--môn-học-academic-categories)
   - [3.3. Phân hệ Tìm kiếm & Bộ lọc nâng cao (Search & Filter)](#33-phân-hệ-tìm-kiếm--bộ-lọc-nâng-cao-search--filter)
   - [3.4. Phân hệ Quản lý & Chi tiết Tài liệu (Document Management & Viewer)](#34-phân-hệ-quản-lý--chi-tiết-tài-liệu-document-management--viewer)
   - [3.5. Phân hệ Bình luận & Thảo luận Tài liệu (Comments & Discussions)](#35-phân-hệ-bình-luận--thảo-luận-tài-liệu-comments--discussions)
   - [3.6. Phân hệ Diễn đàn Hỏi đáp Sinh viên (Forum Q&A)](#36-phân-hệ-diễn-đàn-hỏi-đáp-sinh-viên-forum-qa)
   - [3.7. Phân hệ Trợ lý Học tập AI (AI Assistant)](#37-phân-hệ-trợ-lý-học-tập-ai-ai-assistant)
   - [3.8. Phân hệ Xác thực & Quản lý Tài khoản (Auth & User Profile)](#38-phân-hệ-xác-thực--quản-lý-tài-khoản-auth--user-profile)
   - [3.9. Phân hệ Quản trị & Kiểm duyệt (Admin & Moderator Dashboard)](#39-phân-hệ-quản-trị--kiểm-duyệt-admin--moderator-dashboard)
4. [Bảng Ma Trận Phân Quyền (RBAC Matrix)](#4-bảng-ma-trận-phân-quyền-rbac-matrix)
5. [Kiến Trúc Kỹ Thuật & Bảo Mật](#5-kiến-trúc-kỹ-thuật--bảo-mật)

---

## 1. Giới Thiệu Tổng Quan Hệ Thống

Hệ thống **TMU Tài Liệu** là nền tảng chia sẻ và lưu trữ tài liệu học tập số hóa trực tuyến dành riêng cho cộng đồng sinh viên và giảng viên **Trường Đại học Thương Mại (TMU)**. 

### Mục tiêu dự án:
- Tạo môi trường chia sẻ giáo trình, bài giảng, đề thi, đề cương ôn tập, tiểu luận, luận văn uy tín, chất lượng cao.
- Phân loại học liệu khoa học theo từng Khoa/Viện chuyên ngành, từng Môn học cụ thể và từng Năm học/Học kỳ.
- Tích hợp diễn đàn hỏi đáp học thuật có cơ chế kiểm duyệt nội dung an toàn, văn minh.
- Hỗ trợ công cụ đọc trước tài liệu PDF trực tuyến và trợ lý ảo AI hỗ trợ học tập thông minh.

### Kiến trúc công nghệ nền tảng:
- **Backend:** PHP thuần hướng đối tượng (Pure PHP OOP) xây dựng theo mô hình kiến trúc MVC (Model-View-Controller) tự phát triển, không dùng framework bên ngoài.
- **Cơ sở dữ liệu:** MySQL (hệ quản trị InnoDB, mã hóa `utf8mb4_unicode_ci`).
- **Frontend:** HTML5, CSS3, JavaScript Vanilla, Bootstrap 5, Font Awesome Icons.
- **Bảo mật:** CSRF Tokens, XSS Filtering, Prepared Statements (chống SQL Injection), Password Hashing (Bcrypt), kiểm tra định dạng MIME file nghiêm ngặt.

---

## 2. Các Tác Nhân Trong Hệ Thống (Actors)

| STT | Tác nhân (Actor) | Định danh | Quyền hạn và Mô tả |
| :---: | :--- | :---: | :--- |
| 1 | **Khách vãng lai** | `Guest` | Người dùng chưa đăng nhập: có thể xem trang chủ, tra cứu, tìm kiếm, đọc trước PDF, tải file và xem các câu hỏi/thảo luận công khai. |
| 2 | **Sinh viên / Thành viên** | `Member` | Người dùng đã đăng ký tài khoản và đăng nhập: sở hữu toàn bộ quyền của Khách, kèm quyền đăng tải tài liệu, đánh giá sao, bình luận, tạo câu hỏi diễn đàn, trả lời câu hỏi và quản lý hồ sơ cá nhân. |
| 3 | **Kiểm duyệt viên** | `Moderator` | Người dùng có quyền kiểm duyệt nội dung: xem hàng đợi xét duyệt, duyệt/từ chối tài liệu tải lên, duyệt/từ chối bài đăng diễn đàn, xóa bình luận/câu hỏi vi phạm. |
| 4 | **Quản trị viên** | `Admin` | Người dùng có quyền lực cao nhất hệ thống: toàn quyền kiểm duyệt, quản lý cấu trúc Khoa/Môn học, quản lý danh sách người dùng, cấp quyền, cấu hình hệ thống và theo dõi báo cáo thống kê toàn diện. |

---

## 3. Mô Tả Chi Tiết Các Phân Hệ Chức Năng

### 3.1. Phân hệ Trang chủ & Giao diện toàn trang (Homepage & Layout)

#### 1. Thanh điều hướng toàn trang (Header / Navigation Bar)
- **Logo & Thương hiệu:** Hiển thị nhận diện thương hiệu "TMU Tài Liệu" với biểu tượng trường ĐH Thương Mại, nhấp vào chuyển hướng về trang chủ.
- **Thanh tìm kiếm nhanh (Quick Search):** Cho phép nhập từ khóa để tìm kiếm tài liệu tức thì từ bất kỳ trang nào.
- **Menu liên kết chức năng:**
  - `Tài liệu`: Danh sách tất cả tài liệu học tập trong hệ thống.
  - `Danh mục`: Danh sách Khoa/Viện và cây danh mục môn học.
  - `Hỏi đáp`: Diễn đàn thảo luận và hỏi đáp sinh viên.
  - `Đăng tải`: Nút lối tắt dẫn đến trang upload tài liệu mới (yêu cầu đăng nhập).
- **Khu vực tương tác tài khoản:**
  - Khi chưa đăng nhập: Hiển thị 2 nút `Đăng nhập` và `Đăng ký`.
  - Khi đã đăng nhập: Hiển thị avatar đại diện, chuông thông báo (kèm số lượng chưa đọc), và dropdown menu cá nhân (Hồ sơ, Tài liệu của tôi, Yêu thích, Lịch sử tải, Trang quản trị nếu là Admin/Mod, Đăng xuất).
- **Giao diện Responsive Mobile (`< 992px`):**
  - Khi bấm nút 3 gạch sẽ mở **Mobile Drawer 2 cấp** trượt từ trái sang với nội dung căn trái, icon bo góc và mũi tên điều hướng (`>`).
  - Bấm vào mục **Người dùng** (có mũi tên `>`) sẽ trượt sang màn hình tài khoản (có nút quay lại `←`) hiển thị đầy đủ các mục thông tin cá nhân, quản trị và đăng xuất.
  - Phông chữ, kích thước ảnh banner/avatar và bảng dữ liệu được tự động thu gọn hợp lý trên màn hình nhỏ; mọi thao tác xóa/từ chối/khóa tài khoản đều hiển thị **Popup Modal xác nhận** đồng nhất thay cho hộp thoại trình duyệt.

#### 2. Hero Section (Banner chào đón & Tìm kiếm trọng tâm)
- Khẩu hiệu giới thiệu hệ thống học liệu TMU kết hợp hình ảnh khuôn viên trường TMU.
- Form tìm kiếm lớn hỗ trợ nhập tên môn học, giáo trình, đề thi kèm nút tìm kiếm nổi bật.
- Các nút liên kết nhanh (CTA): Đóng góp tài liệu, Xem theo Khoa/Viện.

#### 3. Khối Thống kê số liệu hệ sinh thái (System Statistics Counter)
- Hiển thị trực quan các chỉ số đo lường thời gian thực:
  - Tổng số tài liệu đã duyệt và công khai.
  - Tổng số Khoa/Viện và Bộ môn trực thuộc.
  - Tổng số thành viên sinh viên đã gia nhập.
  - Tổng số lượt tải tài liệu phục vụ ôn thi, nghiên cứu.

#### 4. Khối Hiển thị Tài liệu mới đăng (Recent Documents)
- Trình bày dạng thẻ (Card grid) các tài liệu vừa được phê duyệt đưa lên hệ thống gần nhất.
- Mỗi thẻ gồm: Ảnh thu nhỏ / Icon loại tài liệu, Tên tài liệu, Môn học, Khoa, Tác giả, Ngày đăng, Lượt xem, Lượt tải và điểm đánh giá sao.

#### 5. Khối Hiển thị Tài liệu tải nhiều nhất (Popular / Most Downloaded Documents)
- Lọc và hiển thị top các tài liệu có số lượt tải cao nhất hệ thống, phản ánh tài liệu được sinh viên quan tâm nhất trong kỳ thi.

#### 6. Khối Hiển thị Danh sách Khoa/Viện (Faculties Showcase)
- Hiển thị thẻ thông tin từng Khoa/Viện trực thuộc trường: Tên khoa, Mã khoa, Mô tả ngắn, Thống kê số môn học và số lượng tài liệu hiện có trong khoa.

#### 7. Chân trang (Footer)
- Thông tin bản quyền, địa chỉ Trường Đại học Thương Mại (79 Hồ Tùng Mậu, Cầu Giấy, Hà Nội).
- Liên kết nhanh đến các điều khoản sử dụng, chính sách bản quyền và thông tin liên hệ ban quản trị.

---

### 3.2. Phân hệ Danh mục Khoa/Viện & Môn học (Academic Categories)

#### 1. Duyệt danh mục Khoa/Viện toàn trường
- Đường dẫn: `/faculties`
- Trình bày toàn bộ danh sách các Khoa/Viện (Khoa CNTT, Marketing, Kinh tế - Luật, Kế toán - Kiểm toán, Quản trị kinh doanh, Tài chính - Ngân hàng, Thương mại điện tử...).
- Mỗi thẻ Khoa hiển thị thông tin tóm tắt, số lượng môn học trực thuộc và tổng số tài liệu học tập sẵn sàng khai thác.

#### 2. Trang chi tiết Khoa/Viện
- Đường dẫn: `/faculties/{id}`
- Hiển thị thông tin chi tiết về Khoa/Viện.
- Liệt kê toàn bộ danh sách các Môn học thuộc khoa quản lý.
- Hiển thị danh sách tài liệu mới nhất thuộc riêng Khoa/Viện đó.

#### 3. Trang chi tiết Môn học
- Đường dẫn: `/subjects/{id}`
- Hiển thị tên môn học, mã môn học, số tín chỉ, khoa phụ trách.
- Liệt kê danh sách tất cả tài liệu học tập của môn học (được phân loại theo: Giáo trình, Slide bài giảng, Đề thi, Đề cương ôn tập, Bài tập lớn/Tiểu luận...).
- Bộ lọc nhanh theo Loại tài liệu ngay trong trang môn học.

#### 4. API nạp môn học động (Dynamic Subjects API)
- Endpoint: `GET /api/subjects?faculty_id={id}`
- Dùng cho AJAX khi người dùng đăng tài liệu hoặc tìm kiếm: khi chọn một Khoa/Viện, dropdown môn học sẽ tự động tải các môn học tương ứng mà không cần reload trang.

---

### 3.3. Phân hệ Tìm kiếm & Bộ lọc nâng cao (Search & Filter)

#### 1. Công cụ tìm kiếm từ khóa thông minh
- Đường dẫn: `/search` hoặc `/documents`
- Hỗ trợ tìm kiếm không phân biệt hoa thường theo: Tiêu đề tài liệu, Mô tả, Tên tác giả/Giảng viên, Từ khóa liên quan (Tags).

#### 2. Bộ lọc đa tiêu chí (Multi-criteria Filters)
Người dùng có thể kết hợp đồng thời nhiều bộ lọc để tìm chính xác tài liệu mong muốn:
- **Lọc theo Khoa/Viện:** Chọn xem tài liệu của một khoa cụ thể.
- **Lọc theo Môn học:** Tự động phụ thuộc theo khoa đã chọn hoặc chọn môn độc lập.
- **Lọc theo Loại tài liệu:**
  - Giáo trình chính khóa
  - Slide bài giảng
  - Đề thi / Đáp án (Giữa kỳ, Cuối kỳ)
  - Đề cương ôn tập tóm tắt
  - Bài tập lớn / Đồ án / Tiểu luận mẫu
  - Tài liệu tham khảo mở rộng
- **Lọc theo Năm học:** Lọc theo các năm học (VD: 2023 - 2024, 2024 - 2025,...).
- **Lọc theo Học kỳ:** Học kỳ 1, Học kỳ 2, Học kỳ Hè.

#### 3. Chế độ sắp xếp kết quả linh hoạt
- **Mới nhất:** Ưu tiên tài liệu mới được duyệt đưa lên hệ thống.
- **Xem nhiều nhất:** Xếp theo số lượt click xem chi tiết tài liệu.
- **Tải nhiều nhất:** Xếp theo số lượt tải xuống máy.
- **Đánh giá cao nhất:** Xếp theo điểm số sao trung bình do sinh viên bình chọn.

#### 4. Phân trang dữ liệu (Pagination)
- Phân trang chuẩn xác, hỗ trợ chuyển trang mượt mà kết hợp giữ nguyên các tham số tìm kiếm và bộ lọc đang áp dụng.

---

### 3.4. Phân hệ Quản lý & Chi tiết Tài liệu (Document Management & Viewer)

#### 1. Xem thông tin chi tiết tài liệu
- Đường dẫn: `/documents/{id}`
- **Thông tin học thuật:** Tên tài liệu, Môn học, Khoa/Viện, Loại tài liệu, Năm học, Học kỳ, Tên giảng viên hướng dẫn (nếu có).
- **Thông số kỹ thuật:** Định dạng tệp (`.pdf`, `.docx`, `.pptx`, `.xlsx`, `.zip`), Dung lượng tệp, Số trang (nếu là PDF).
- **Chỉ số tương tác:** Người đăng tải, Ngày đăng, Số lượt xem, Số lượt tải về, Điểm đánh giá trung bình và số lượt đánh giá.
- **Mô tả chi tiết:** Nội dung giới thiệu tóm tắt tài liệu, mục lục, lưu ý khi ôn tập.

#### 2. Trình đọc trước tệp tin PDF trực tiếp (Embedded PDF Preview)
- Đường dẫn preview: `/documents/{id}/preview`
- Tích hợp khung hiển thị (iframe viewer) an toàn, cho phép sinh viên xem trực tiếp nội dung các trang đầu của tài liệu ngay trên trình duyệt mà không cần phải tải tệp về máy tính/điện thoại.

#### 3. Cơ chế tải tệp tin an toàn (Secure Download Engine)
- Đường dẫn: `/documents/{id}/download`
- Kiểm tra tính hợp lệ của tệp trên máy chủ lưu trữ (Storage).
- Tự động tăng biến đếm lượt tải (`download_count`) của tài liệu.
- Ghi nhận lịch sử tải tệp của người dùng vào bảng `download_history` phục vụ việc tra cứu lại sau này.
- Trả về tệp tin đính kèm với đúng header MIME type và tên file gốc.

#### 4. Đăng tải tài liệu mới (Document Upload)
- Đường dẫn: `/documents/create` (Yêu cầu đăng nhập)
- **Form đăng tải đầy đủ trường dữ liệu:**
  - Tiêu đề tài liệu (Bắt buộc).
  - Chọn Khoa/Viện và Môn học (AJAX nạp động).
  - Chọn Loại tài liệu, Năm học, Học kỳ.
  - Tên giảng viên / Tác giả tài liệu.
  - Mô tả nội dung tóm tắt.
  - Chọn tệp đính kèm (hỗ trợ kéo thả).
- **Kiểm soát tính hợp lệ, chống trùng lặp & an toàn:**
  - Định dạng cho phép: PDF, DOCX, DOC, PPTX, PPT, XLSX, XLS, ZIP, RAR.
  - Giới hạn dung lượng tối đa: 50MB.
  - **Chống đăng trùng lặp tài liệu (Duplicate Detection):** Kiểm tra trùng tiêu đề trong cùng môn học và kiểm tra trùng nội dung tệp bằng mã băm **SHA-256 checksum** (chặn đăng lại tệp đã tồn tại dù đổi tên).
  - Tệp tải lên được lưu trữ ở thư mục an toàn ngoài vùng public access trực tiếp.
  - Tệp mới đăng sẽ tự động mang trạng thái **`pending` (Chờ duyệt)** và chuyển đến hàng đợi của ban kiểm duyệt (tài liệu do Admin/Moderator đăng được tự động duyệt `approved`).

#### 5. Chỉnh sửa & Xóa tài liệu
- **Trước khi được duyệt (`pending`):** Thành viên đăng tài liệu có thể chỉnh sửa lại thông tin/tệp hoặc xóa tài liệu của mình.
- **Sau khi đã được duyệt (`approved`) hoặc đã qua kiểm duyệt:** Thành viên **không thể** tự chỉnh sửa hoặc xóa tài liệu; chỉ **Quản trị viên (`Admin`)** mới có quyền chỉnh sửa hoặc xóa vĩnh viễn.

#### 6. Đánh giá chất lượng tài liệu (Rating System)
- Sinh viên đã đăng nhập có thể chấm điểm tài liệu theo thang từ **1 đến 5 sao** kèm lời nhận xét.
- Hệ thống tự động tính điểm trung bình hiển thị dạng sao vàng trực quan.

#### 7. Đánh dấu Yêu thích (Bookmark / Favorites)
- Nút "Thêm vào yêu thích" với hiệu ứng trái tim.
- Cho phép sinh viên lưu trữ các tài liệu hay vào tủ sách cá nhân để truy cập nhanh trước kỳ thi.

#### 8. Báo cáo tài liệu vi phạm (Report Abuse)
- Cho phép người dùng báo cáo tài liệu có vấn đề: Nội dung phản cảm/độc hại, Vi phạm bản quyền, Sai lệch môn học/khoa viện, Tệp hỏng không đọc được.

---

### 3.5. Phân hệ Bình luận & Thảo luận Tài liệu (Comments & Discussions)

#### 1. Bình luận dưới tài liệu
- Cho phép các thành viên đặt câu hỏi, nhận xét hoặc cảm ơn người đăng ngay bên dưới trang chi tiết tài liệu.

#### 2. Trả lời bình luận đa cấp (Nested / Threaded Replies)
- Cho phép người dùng bấm "Trả lời" (Reply) trực tiếp vào một bình luận cụ thể của người khác.
- Cấu trúc cây bình luận hiển thị thụt lề rõ ràng, giúp cuộc thảo luận diễn ra liền mạch theo đúng ngữ cảnh.

#### 3. Quản lý bình luận
- Hiển thị tên người bình luận, avatar, thời gian tương đối (VD: "5 phút trước", "2 ngày trước").
- Tác giả bình luận có thể xóa bình luận của mình.
- Kiểm duyệt viên và Quản trị viên có nút "Xóa" nhanh các bình luận spam hoặc vi phạm quy tắc ứng xử.

---

### 3.6. Phân hệ Diễn đàn Hỏi đáp Sinh viên (Forum Q&A)

#### 1. Danh sách câu hỏi thảo luận
- Đường dẫn: `/forum`
- Hiển thị các câu hỏi học tập, kinh nghiệm ôn thi, xin tài liệu từ cộng đồng sinh viên TMU.
- **Bộ lọc diễn đàn:** Lọc theo Khoa/Viện, Lọc theo Môn học, Tìm kiếm câu hỏi theo từ khóa.
- **Thẻ câu hỏi hiển thị:** Tiêu đề câu hỏi, Tác giả, Thời gian đăng, Môn học liên quan, Số lượt trả lời và số lượt xem bài viết.

#### 2. Đăng câu hỏi mới & Quy trình kiểm duyệt (Moderation Workflow)
- Đường dẫn: `/forum/create` (Yêu cầu đăng nhập)
- Sinh viên soạn tiêu đề câu hỏi, chọn Khoa, chọn Môn học và nội dung chi tiết câu hỏi (hỗ trợ định dạng văn bản).
- **Cơ chế kiểm duyệt chặt chẽ:**
  - Bài viết do sinh viên (`member`) đăng tải sẽ mặc định được gán trạng thái **`pending` (Chờ duyệt)** và hiện thông báo rõ ràng: *"Câu hỏi của bạn đã được gửi và đang chờ Ban Quản trị phê duyệt trước khi hiển thị công khai"*.
  - Chỉ khi Quản trị viên hoặc Kiểm duyệt viên phê duyệt thì bài viết mới xuất hiện trên diễn đàn chung.
  - Nếu tác giả là Moderator hoặc Admin đăng bài thì sẽ được tự động duyệt (`approved`) ngay lập tức.

#### 3. Chi tiết câu hỏi & Trả lời thảo luận
- Đường dẫn: `/forum/{id}`
- **Hiển thị nội dung đầy đủ:** Câu hỏi chi tiết của sinh viên, thông tin người hỏi.
- **Khu vực trả lời (Answers):**
  - Sinh viên khác có thể nhập câu trả lời, chia sẻ lời giải, hướng dẫn ôn tập.
  - Chỉ cho phép gửi câu trả lời khi câu hỏi đã ở trạng thái `approved` (Đã duyệt).

#### 4. Phê duyệt & Quản trị bài viết diễn đàn
- Nút bấm phê duyệt (`Approve`) hoặc từ chối (`Reject`) trực tiếp trên bài viết dành riêng cho Quản trị viên và Kiểm duyệt viên.
- Chức năng xóa bài viết hoặc xóa câu trả lời spam/vi phạm.

---

### 3.7. Phân hệ Trợ lý Học tập AI (AI Assistant)

#### 1. Cửa sổ Chatbot AI học tập thông minh
- Tích hợp trực tiếp trên website (sử dụng Google Gemini API).
- Hỗ trợ giải đáp nhanh các thắc mắc học thuật, tóm tắt nội dung bài học, giải thích khái niệm kinh tế/thương mại/CNTT.

#### 2. Quản lý phiên làm việc & Lịch sử trò chuyện
- API `GET /api/ai/history`: Lấy lịch sử đoạn chat gần nhất của người dùng.
- API `POST /api/ai/chat`: Gửi câu hỏi và nhận câu trả lời tức thì từ AI.
- API `POST /api/ai/clear`: Xóa lịch sử trò chuyện để bắt đầu chủ đề mới.

---

### 3.8. Phân hệ Xác thực & Quản lý Tài khoản (Auth & User Profile)

#### 1. Đăng ký tài khoản (Sign Up)
- Đường dẫn: `/register`
- Nhập thông tin: Họ và tên, Mã sinh viên (MSSV), Email (khuyến khích định dạng `@tmu.edu.vn`), Mật khẩu và Xác nhận mật khẩu.
- Hệ thống gửi mã **OTP 6 chữ số** qua **SMTP Gmail (PHPMailer)** có hiệu lực 60 giây (tự động khóa 5 phút nếu nhập sai quá 5 lần) để xác thực tài khoản chính chủ.
- Mật khẩu được băm an toàn bằng thuật toán `Bcrypt`.

#### 2. Đăng nhập hệ thống (Sign In)
- Đường dẫn: `/login`
- Đăng nhập bằng Email và Mật khẩu.
- Tính năng "Ghi nhớ đăng nhập" (Remember Me) qua cookie mã hóa an toàn.
- Cơ chế bảo vệ chống dò quét mật khẩu (Brute-force Protection): tạm khóa phiên nếu nhập sai quá số lần quy định.

#### 3. Quên mật khẩu & Đặt lại mật khẩu (Forgot & Reset Password)
- Đường dẫn: `/forgot-password` và `/reset-password/{token}`
- Người dùng nhập email đăng ký để nhận liên kết đặt lại mật khẩu chứa token ngẫu nhiên bảo mật cao có thời hạn sử dụng.

#### 4. Trang thông tin cá nhân (Profile)
- Đường dẫn: `/profile`
- Xem thông tin chi tiết: Họ tên, Email, MSSV, Khoa chuyên ngành, Ngày tham gia, Vai trò trong hệ thống.
- Cập nhật ảnh đại diện (Upload Avatar với cắt ảnh chuẩn).
- Cập nhật thông tin liên hệ và giới thiệu bản thân.
- Đổi mật khẩu với mã xác thực OTP qua email.

#### 5. Quản lý "Tài liệu của tôi" (My Uploaded Documents)
- Đường dẫn: `/my-documents`
- Theo dõi toàn bộ tài liệu do chính sinh viên đã tải lên.
- Phân loại rõ ràng trạng thái tài liệu:
  - 🟡 **Chờ duyệt (`pending`):** Đang trong hàng đợi kiểm duyệt của ban quản trị.
  - 🟢 **Đã duyệt (`approved`):** Đã công khai trên hệ thống cho toàn trường xem và tải.
  - 🔴 **Bị từ chối (`rejected`):** Kèm lý do phản hồi chi tiết từ kiểm duyệt viên để sinh viên chỉnh sửa lại.
- Nút sửa thông tin và xóa tài liệu chỉ khả dụng khi tài liệu đang ở trạng thái **Chờ duyệt (`pending`)**; sau khi đã được duyệt, thao tác sửa/xóa sẽ bị khóa đối với thành viên.

#### 6. Tủ tài liệu Yêu thích (Favorites Collection)
- Đường dẫn: `/favorites`
- Danh sách các tài liệu đã bấm yêu thích, giúp sinh viên truy cập lại nhanh chóng bất cứ lúc nào.

#### 7. Lịch sử tải xuống (Download History)
- Đường dẫn: `/history`
- Liệt kê toàn bộ các tài liệu sinh viên đã từng tải về máy kèm thời gian tải.

#### 8. Trung tâm Thông báo (Notifications)
- Đường dẫn: `/notifications`
- Nhận thông báo tự động khi:
  - Tài liệu của bạn đã được kiểm duyệt viên phê duyệt công khai.
  - Tài liệu của bạn bị từ chối phê duyệt (kèm lý do).
  - Có người bình luận hoặc trả lời bình luận của bạn.

---

### 3.9. Phân hệ Quản trị & Kiểm duyệt (Admin & Moderator Dashboard)

#### 1. Bảng điều khiển tổng quan (Dashboard)
- Đường dẫn: `/admin`
- Thống kê các chỉ số quan trọng toàn hệ thống:
  - Tổng số người dùng & thành viên mới đăng ký trong tuần/tháng.
  - Tổng số tài liệu đã duyệt và tài liệu đang chờ xử lý.
  - Tổng số câu hỏi diễn đàn đang chờ duyệt.
  - Tổng lượt tải tệp tin và dung lượng lưu trữ đã sử dụng.
- Biểu đồ trực quan hóa dữ liệu theo thời gian thực.

#### 2. Trung tâm Kiểm duyệt nội dung (Moderation Center)
- Đường dẫn: `/admin/moderation`
- Giao diện gồm 2 Tab chuyên biệt:
  - **Tab 1: Kiểm duyệt Tài liệu học tập (`Tài liệu chờ duyệt`):**
    - Xem thông tin chi tiết, người đăng, khoa môn.
    - Đọc trước trực tiếp tệp tin đính kèm để thẩm định nội dung.
    - Nút **Duyệt (`Approve`)**: Chuyển trạng thái sang `approved`, tự động thông báo cho người đăng.
    - Nút **Từ chối (`Reject`)**: Nhập lý do từ chối (thiếu thông tin, sai môn học, tài liệu chất lượng kém...) để gửi phản hồi cho sinh viên.
  - **Tab 2: Kiểm duyệt Diễn đàn hỏi đáp (`Diễn đàn chờ duyệt`):**
    - Hiển thị danh sách các câu hỏi mới gửi từ sinh viên.
    - Xem tiêu đề, môn học, nội dung chi tiết câu hỏi.
    - Nút **Duyệt bài**: Đưa câu hỏi hiển thị công khai lên diễn đàn chung.
    - Nút **Từ chối**: Hủy bỏ bài đăng không phù hợp hoặc vi phạm nội quy.

#### 3. Quản lý Cơ cấu Học thuật & Danh mục (Category Management)
- Đường dẫn: `/admin/categories`
- **Quản lý Khoa/Viện:**
  - Thêm mới Khoa/Viện (Tên khoa, Mã khoa, Mô tả).
  - Sửa thông tin Khoa/Viện.
  - Bật/Tắt trạng thái hoạt động.
  - Xóa Khoa/Viện.
- **Quản lý Môn học:**
  - Thêm mới Môn học (Tên môn, Mã môn, Thuộc khoa nào, Số tín chỉ).
  - Cập nhật thông tin môn học.
  - Bật/Tắt trạng thái hoạt động hoặc Xóa môn học.
- **Quản lý Loại tài liệu:**
  - Định nghĩa các loại tài liệu: Giáo trình, Đề thi, Đề cương, Slide bài giảng, Khóa luận tốt nghiệp...

#### 4. Quản lý Người dùng (User Management)
- Đường dẫn: `/admin/users`
- Danh sách tất cả thành viên trong hệ thống (tìm kiếm theo tên, email, MSSV).
- Xem chi tiết hồ sơ cá nhân, ngày tham gia, danh sách tài liệu đã đóng góp.
- **Khóa / Mở khóa tài khoản:** Vô hiệu hóa tài khoản vi phạm quy chế.
- **Phân quyền vai trò:** Nâng cấp/hạ cấp vai trò người dùng giữa các quyền: `member` (Thành viên), `moderator` (Kiểm duyệt viên), `admin` (Quản trị viên).

#### 5. Quản lý Tài liệu toàn hệ thống (Document Management)
- Đường dẫn: `/admin/documents`
- Tra cứu và lọc tài liệu trong CSDL theo **từ khóa tiêu đề**, **môn học**, **loại tài liệu** (Giáo trình, Slide bài giảng, Đề thi...), và **trạng thái** (`approved`, `pending`, `rejected`, `hidden`).
- Tính năng **Ẩn (`Hide`)** hoặc **Hiện (`Unhide`)** tài liệu (áp dụng khi tài liệu đang bị khiếu nại bản quyền).
- Tính năng **Xóa vĩnh viễn** tài liệu và tệp tin vật lý khỏi máy chủ.

#### 6. Báo cáo & Thống kê chuyên sâu (System Statistics)
- Đường dẫn: `/admin/statistics`
- Thống kê lượng truy cập, số lượt tải theo từng môn học và từng khoa.
- Bảng xếp hạng các thành viên có đóng góp nhiều tài liệu chất lượng nhất (Top Contributors).

---

## 4. Bảng Ma Trận Phân Quyền (RBAC Matrix)

Dưới đây là bảng tổng hợp quyền truy cập của từng nhóm người dùng đối với toàn bộ các tính năng của hệ thống:

| Nhóm chức năng | Tên tính năng cụ thể | 🧑 Khách (`Guest`) | 👤 Sinh viên (`Member`) | 🛡️ K.Duyệt (`Moderator`) | 👑 Quản trị (`Admin`) |
| :--- | :--- | :---: | :---: | :---: | :---: |
| **Trang chủ & Layout** | Xem banner, tài liệu mới, tài liệu tải nhiều | ✔ | ✔ | ✔ | ✔ |
| | Xem số liệu thống kê hệ thống | ✔ | ✔ | ✔ | ✔ |
| **Danh mục Khoa/Môn** | Xem danh sách Khoa/Viện & Môn học | ✔ | ✔ | ✔ | ✔ |
| | Xem tài liệu theo từng Khoa / Môn | ✔ | ✔ | ✔ | ✔ |
| **Tìm kiếm & Lọc** | Tìm kiếm từ khóa, áp dụng bộ lọc đa tiêu chí | ✔ | ✔ | ✔ | ✔ |
| | Sắp xếp tài liệu (Mới, Xem nhiều, Tải nhiều) | ✔ | ✔ | ✔ | ✔ |
| **Tài liệu** | Xem thông tin chi tiết tài liệu | ✔ | ✔ | ✔ | ✔ |
| | Đọc trước file PDF trực tiếp (Iframe preview) | ✔ | ✔ | ✔ | ✔ |
| | Tải file tài liệu về máy | ✔ | ✔ | ✔ | ✔ |
| | Đăng tải tài liệu mới (vào hàng chờ duyệt) | ✗ | ✔ | ✔ | ✔ |
| | Chỉnh sửa / Xóa tài liệu cá nhân | ✗ | ✔ *(Chỉ khi chờ duyệt)* | ✔ *(Chỉ khi chờ duyệt)* | ✔ |
| | Đánh giá sao & Đánh dấu yêu thích | ✗ | ✔ | ✔ | ✔ |
| | Báo cáo tài liệu vi phạm | ✗ | ✔ | ✔ | ✔ |
| **Bình luận** | Xem danh sách bình luận | ✔ | ✔ | ✔ | ✔ |
| | Viết bình luận mới / Trả lời bình luận | ✗ | ✔ | ✔ | ✔ |
| | Xóa bình luận của bản thân | ✗ | ✔ | ✔ | ✔ |
| | Xóa bình luận vi phạm của người khác | ✗ | ✗ | ✔ | ✔ |
| **Diễn đàn Hỏi đáp** | Xem danh sách câu hỏi & chi tiết thảo luận | ✔ | ✔ | ✔ | ✔ |
| | Đăng câu hỏi mới (chờ ban quản trị duyệt) | ✗ | ✔ | ✔ *(Tự duyệt)* | ✔ *(Tự duyệt)* |
| | Gửi câu trả lời thảo luận | ✗ | ✔ | ✔ | ✔ |
| | Phê duyệt / Từ chối bài đăng câu hỏi | ✗ | ✗ | ✔ | ✔ |
| | Xóa bài viết / câu trả lời trên diễn đàn | ✗ | ✗ | ✔ | ✔ |
| **Trợ lý AI** | Chat hỏi đáp học tập với Gemini AI | ✔ | ✔ | ✔ | ✔ |
| **Tài khoản cá nhân** | Đăng ký tài khoản mới / Đăng nhập / Đổi MK | ✔ | ✔ | ✔ | ✔ |
| | Xem hồ sơ, tủ sách yêu thích, lịch sử tải | ✗ | ✔ | ✔ | ✔ |
| | Quản lý tài liệu đã tải lên của bản thân | ✗ | ✔ | ✔ | ✔ |
| **Quản trị & Kiểm duyệt**| Xem Dashboard quản trị tổng quan | ✗ | ✗ | ✔ | ✔ |
| | Kiểm duyệt tài liệu (Phê duyệt / Từ chối) | ✗ | ✗ | ✔ | ✔ |
| | Kiểm duyệt bài diễn đàn (Duyệt / Từ chối) | ✗ | ✗ | ✔ | ✔ |
| | Quản lý danh mục Khoa/Viện, Môn học, Loại TL | ✗ | ✗ | ✗ | ✔ |
| | Quản lý người dùng (Khóa tài khoản, Phân quyền)| ✗ | ✗ | ✗ | ✔ |
| | Xem báo cáo & thống kê chi tiết toàn trường | ✗ | ✗ | ✗ | ✔ |

---

## 5. Kiến Trúc Kỹ Thuật & Bảo Mật

### 5.1. Mô hình kiến trúc MVC Thuần
- **Model (`app/Models`):** Đóng gói toàn bộ các câu lệnh truy vấn dữ liệu thông qua PDO Prepared Statements, đảm bảo nguyên tắc toàn vẹn dữ liệu và chống hoàn toàn lỗi SQL Injection.
- **View (`app/Views`):** Phân chia rõ ràng giao diện người dùng thường và phân hệ quản trị. Sử dụng layout dùng chung (`layouts/main.php`, `layouts/admin.php`) kế thừa đồng bộ.
- **Controller (`app/Controllers`):** Điều hướng luồng nghiệp vụ, kiểm tra tính hợp lệ của dữ liệu đầu vào (Validation) trước khi gọi Model và render View.

### 5.2. Cơ chế Bảo mật Hệ thống
- **Chống tấn công XSS:** Tất cả dữ liệu hiển thị từ phía người dùng đều được escape thông qua hàm `e()` / `htmlspecialchars()`.
- **Chống tấn công CSRF:** Mọi form gửi dữ liệu (POST request) đều được đính kèm `csrf_token()` và xác thực ở Base Controller.
- **Bảo mật tệp tin tải lên (File Storage Security):**
  - Không lưu file trực tiếp trong thư mục web công khai (`public/`).
  - Kiểm tra đuôi file, kích thước file và MIME Type thật của file trước khi chấp nhận.
  - Tên file được băm ngẫu nhiên để chống ghi đè và tấn công thực thi file mã độc.
  - Cung cấp tệp thông qua Stream Controller kiểm tra quyền tải hợp lệ.
- **Mã hóa thông tin đăng nhập:** Mật khẩu người dùng được băm một chiều với chuẩn công nghiệp `PASSWORD_BCRYPT`.

---

*Tài liệu này được biên soạn để mô tả toàn bộ các chức năng và cấu trúc vận hành của Hệ thống Chia sẻ Tài liệu Học tập TMU.*
