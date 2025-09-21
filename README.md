# Nexus Drive

![Nexus Drive Screenshot - Login Page](./Screenshot.png)
*Màn hình đăng nhập của Nexus Drive*

## Giới thiệu

**Nexus Drive** là một giải pháp quản lý tệp tin tự host (self-hosted) đơn giản, mạnh mẽ và tiện lợi. Được xây dựng bằng PHP và SQLite, ứng dụng này cung cấp các tính năng cơ bản của một dịch vụ lưu trữ đám mây, với ưu điểm vượt trội là sự gọn nhẹ, dễ cài đặt và tính di động cao. Bạn có thể tự lưu trữ và quản lý các tệp cá nhân mà không phụ thuộc vào bên thứ ba.

## Tính năng chính

*   **Tự khởi tạo (Zero-Setup):** Tự động tạo cơ sở dữ liệu SQLite và cấu hình người dùng ngay lần đầu truy cập.
*   **Di động tối đa:** Toàn bộ dữ liệu (cơ sở dữ liệu, file người dùng) được lưu trữ trong các tệp riêng, giúp dễ dàng sao lưu và di chuyển ứng dụng.
*   **Quản lý tệp cơ bản:** Duyệt, tạo thư mục mới, đổi tên, di chuyển, xóa (vào thùng rác hoặc vĩnh viễn), khôi phục tệp/thư mục.
*   **Tải lên theo đoạn (Chunked Upload):** Hỗ trợ tải lên các tệp tin lớn mà không bị giới hạn bởi cấu hình `php.ini` truyền thống, cải thiện độ ổn định.
*   **Tìm kiếm:** Tìm kiếm nhanh chóng các tệp và thư mục theo tên.
*   **Nén và Tải về hàng loạt:** Chọn nhiều tệp/thư mục và nén chúng thành một tệp ZIP duy nhất để tải về.
*   **Xem trước tệp:** Hỗ trợ xem trước nhiều định dạng tệp (hình ảnh, video, âm thanh, PDF, mã nguồn) trực tiếp trên trình duyệt.
*   **Thùng rác:** Chức năng thùng rác để khôi phục các tệp đã xóa.
*   **Liên kết chia sẻ:** Tạo liên kết công khai để chia sẻ tệp.
*   **Cơ chế tài khoản:** Hệ thống đăng nhập, đăng ký và đăng xuất đơn giản.
*   **Chế độ nhà phát triển:** Tùy chọn tắt hoàn toàn đăng nhập để tiện lợi khi làm việc trên localhost.
*   **Giao diện hiện đại:** Thiết kế tối giản, trực quan, hỗ trợ chế độ tối tự động và tương thích di động.
*   **Thông tin lưu trữ:** Theo dõi dung lượng đã sử dụng và phân loại theo loại tệp.

## Yêu cầu

*   Máy chủ web (Apache, Nginx, Caddy, v.v.)
*   PHP 7.4 trở lên
    *   **Bắt buộc:** PHP extension `pdo_sqlite` (thường đã bật sẵn)
    *   **Bắt buộc:** PHP extension `zip` (để nén tệp)
    *   **Bắt buộc:** PHP `allow_url_fopen` phải được bật (thường đã bật sẵn)
    *   **Đề xuất:** Tăng `memory_limit` và `max_execution_time` trong `php.ini` nếu bạn xử lý các tệp rất lớn hoặc nhiều thao tác nặng (ví dụ: `memory_limit = 256M`, `max_execution_time = 300`).

## Cài đặt

Việc cài đặt Nexus Drive được thiết kế để đơn giản tối đa.

1.  **Tải xuống:** Tải về toàn bộ mã nguồn của dự án.
2.  **Giải nén & Tải lên:** Giải nén tệp zip và tải toàn bộ nội dung lên thư mục gốc của máy chủ web (ví dụ: `public_html/`) hoặc một thư mục con (ví dụ: `public_html/nexusdrive/`).
3.  **Quyền ghi:** Đảm bảo thư mục gốc của dự án (nơi chứa `config.php`, `database.sqlite` và `users.php`) có quyền ghi. Trên Linux/Unix, bạn có thể thiết lập bằng lệnh:
    ```bash
    chmod -R 755 /path/to/your/nexusdrive
    # Hoặc nếu gặp lỗi ghi, thử:
    chmod -R 775 /path/to/your/nexusdrive 
    # Hoặc thậm chí chmod -R 777 nếu bạn biết mình đang làm gì và chấp nhận rủi ro.
    ```
4.  **Truy cập lần đầu:** Mở trình duyệt web và truy cập vào URL của dự án của bạn (ví dụ: `http://localhost/nexusdrive/` hoặc `https://yourdomain.com/`).
    *   Trong lần truy cập đầu tiên, ứng dụng sẽ tự động tạo tệp cơ sở dữ liệu `database.sqlite` và tệp cấu hình người dùng `users.php` trong thư mục gốc.
    *   Sau đó, bạn sẽ được chuyển hướng đến trang đăng nhập.

## Sử dụng

### 1. Đăng nhập / Đăng ký

*   **Tài khoản mặc định:** Lần đầu chạy, file `users.php` sẽ được tạo với tài khoản `admin` và mật khẩu `admin`.
*   **Đăng nhập:** Sử dụng tài khoản mặc định hoặc tài khoản bạn đã đăng ký để truy cập.
*   **Đăng ký:** Nếu `ALLOW_REGISTRATION` được đặt thành `true` trong `config.php`, bạn có thể đăng ký tài khoản mới từ trang đăng nhập.

### 2. Cấu hình nhanh

Sau khi cài đặt, bạn có thể điều chỉnh các cài đặt chính trong tệp `config.php`:

*   `define('APP_NAME', 'Nexus Drive');`: Đặt tên ứng dụng của bạn.
*   `define('AUTH_ENABLED', false);`:
    *   Đặt `false` để tắt hoàn toàn hệ thống đăng nhập. Rất tiện lợi cho phát triển trên `localhost`. Bạn sẽ tự động đăng nhập với tên "Local User".
    *   Đặt `true` để bật hệ thống đăng nhập, yêu cầu người dùng phải đăng nhập để truy cập ứng dụng. (Khuyên dùng cho môi trường sản phẩm/hosting).
*   `define('ALLOW_REGISTRATION', true);`:
    *   Đặt `true` để cho phép người dùng tự đăng ký tài khoản.
    *   Đặt `false` để tắt đăng ký, chỉ admin mới có thể thêm người dùng mới bằng cách chỉnh sửa trực tiếp tệp `users.php` hoặc tạo mật khẩu hash bằng `generate_hash.php`.

### 3. Tạo mật khẩu hash (generate_hash.php)

Để thêm người dùng mới thủ công hoặc cập nhật mật khẩu, bạn cần mật khẩu đã mã hóa.

1.  Mở tệp `generate_hash.php`.
2.  Thay `your_strong_password_here` bằng mật khẩu bạn muốn.
3.  Truy cập `generate_hash.php` trên trình duyệt của bạn (ví dụ: `http://localhost/nexusdrive/generate_hash.php`).
4.  Sao chép chuỗi mã hóa và dán vào tệp `users.php` tương ứng với tên người dùng bạn muốn.
5.  **Quan trọng:** Xóa hoặc đổi tên tệp `generate_hash.php` sau khi sử dụng để bảo mật.

## Cân nhắc bảo mật

*   **Quyền truy cập thư mục:** Đảm bảo thư mục của bạn không có quyền ghi quá rộng rãi (ví dụ: 777) trên môi trường sản phẩm. `755` thường là đủ cho các tệp và thư mục, với quyền ghi cho một số tệp như `database.sqlite` và `users.php`.
*   **Mật khẩu:** Luôn sử dụng mật khẩu mạnh.
*   **File `users.php`:** Mặc dù được bảo vệ, nhưng việc lưu trữ người dùng trong một file PHP vẫn kém linh hoạt và an toàn hơn so với một bảng CSDL chuyên dụng cho các ứng dụng lớn. Đối với dự án nhỏ/cá nhân, đây là một lựa chọn hợp lý.
*   **HTTPS:** Luôn sử dụng HTTPS trên môi trường sản phẩm để bảo vệ dữ liệu truyền tải.

## Đóng góp

Mọi đóng góp, báo cáo lỗi và đề xuất cải tiến đều được hoan nghênh.

## Giấy phép

Dự án này được phát hành dưới Giấy phép MIT. Xem tệp `LICENSE` để biết thêm chi tiết.

---
