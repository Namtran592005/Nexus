# Nexus Drive

![Nexus Drive Screenshot](./Screenshot.png)  
*Main interface of Nexus Drive*

## Introduction

**Nexus Drive** is a powerful, modern, and extremely lightweight self-hosted cloud storage solution. Built with PHP and SQLite, Nexus Drive delivers a smooth user experience comparable to top services, all packaged in a simple, portable, and easy-to-deploy architecture.

It’s the perfect solution for those who want full control of their data, with a beautiful interface and professional features without the complexity of heavy systems.

## Why Choose Nexus Drive?

*   **Superior User Experience:** The **Single Page Application (SPA)** architecture ensures instant navigation and file management without page reloads.  
*   **High Performance:** Optimized from backend to frontend—**Gzip compression**, **streaming responses** for maximum download speeds, and an **Optimistic UI** for instant feedback.  
*   **Absolute Portability:** The entire app, including database and file contents, is contained in a single folder. Backup, migrate, or deploy simply by copy-and-paste.  
*   **Zero Setup:** No complicated installation required. Just upload, grant permissions, and access. The app auto-initializes everything needed.  

## Feature List

### Management & Interaction
*   ✅ Modern, responsive SPA interface with **Light/Dark mode**.  
*   ✅ Two viewing modes: **List** and **Grid**.  
*   ✅ File/Folder management: Create, Rename, Delete, Restore.  
*   ✅ Move files/folders anywhere via **Tree Modal** or **Drag & Drop**.  
*   ✅ **Recycle Bin** with restore or permanent delete.  
*   ✅ Batch download as **ZIP**.  

### Performance & Large Files
*   ✅ **Chunk Uploading:** Stable upload for very large files.  
*   ✅ **Streamed Downloads:** Download large files instantly with minimal server memory usage.  
*   ✅ **Gzip optimization:** Reduce transfer size, boost speed on slow networks.  

### Utilities & Sharing
*   ✅ **Versatile file preview:** Images, videos, audio, PDFs, and source code.  
*   ✅ **Smart search:** Live search and full-page search.  
*   ✅ **Advanced sharing:**  
    *   Set **passwords** for shared links.  
    *   Configure **expiration dates**.  
    *   Allow/deny **downloads**.  
*   ✅ **Flexible account system** with optional registration.  
*   ✅ **Developer mode:** Disable authentication entirely for localhost development.  

## Requirements

*   Web server (Apache with `mod_rewrite`, or Nginx recommended).  
*   PHP 8.0 or higher.  
*   **PHP Extensions:**  
    *   `pdo_sqlite` (required).  
    *   `zip` (required).  

## Quick Setup

1.  **Download source code:** Get and extract the project.  
2.  **Upload to server:** Place extracted files in your web root.  
3.  **Grant write permissions:** Ensure project root and `database/` are writable by the web server user.  
    *   *On Linux:* `chmod -R 775 /path/to/nexus-drive` and `chown -R www-data:www-data /path/to/nexus-drive`.  
4.  **Access:** Open your browser and visit your URL. The app auto-creates database, user file, and brings you to login page.  

**Default Account:**  
*   **Username:** `admin`  
*   **Password:** `admin`  

## Configuration

Edit `bootstrap.php` to adjust main settings:  

*   `define('APP_NAME', 'Nexus Drive');`: Set app name.  
*   `define('AUTH_ENABLED', true);`:  
    *   `true`: Enable login system (production).  
    *   `false`: Disable login (for localhost dev).  
*   `define('ALLOW_REGISTRATION', false);`:  
    *   `true`: Allow user self-registration.  
    *   `false`: Disable public registration.  

## Project Structure

*   `index.php`: Main view & client-side JavaScript.  
*   `api.php`: API gateway handling business logic.  
*   `bootstrap.php`: Core config, utilities, and authentication check.  
*   `share.php`: Public view for shared links.  
*   `login.php`, `register.php`, `logout.php`: Auth pages.  
*   `database/database.sqlite`: SQLite database file.  
*   `users.php`: User data storage file.  
*   `.htaccess`: Apache server configuration.  
*   `src/`: Assets (CSS, JS libraries, fonts, images).  

## License

This project is released under the [MIT License](LICENSE).  

---

# Bản gốc tiếng Việt

(Đã lược bỏ phần so sánh với Tiny File Manager)

# Vietnamese

## Giới thiệu

**Nexus Drive** là một giải pháp lưu trữ đám mây cá nhân (self-hosted cloud storage) mạnh mẽ, hiện đại và cực kỳ nhẹ. Được xây dựng bằng PHP và SQLite, Nexus Drive mang đến trải nghiệm người dùng mượt mà như các dịch vụ hàng đầu, gói gọn trong một kiến trúc đơn giản, di động và dễ triển khai.

Đây là giải pháp hoàn hảo cho những ai muốn toàn quyền kiểm soát dữ liệu của mình, với một giao diện đẹp mắt và các tính năng chuyên nghiệp mà không cần đến các hệ thống phức tạp.

## Tại sao chọn Nexus Drive?

*   **Trải nghiệm người dùng vượt trội:** Kiến trúc **Single Page Application (SPA)** giúp mọi thao tác điều hướng, quản lý tệp diễn ra tức thì, không cần tải lại trang.
*   **Hiệu suất đỉnh cao:** Tối ưu hóa từ backend đến frontend—từ **nén Gzip**, **phản hồi streaming** cho tốc độ tải file tối đa, đến **Optimistic UI** cho cảm giác phản hồi ngay lập tức.
*   **Di động tuyệt đối:** Toàn bộ ứng dụng, bao gồm cả cơ sở dữ liệu và nội dung file, đều nằm trong một thư mục duy nhất. Sao lưu, di chuyển hay triển khai chỉ đơn giản là sao chép và dán.
*   **Zero-Setup:** Không cần cài đặt phức tạp. Chỉ cần tải lên, cấp quyền và truy cập. Ứng dụng sẽ tự động khởi tạo mọi thứ cần thiết.

## Danh sách tính năng

### Quản lý & Tương tác
*   ✅ Giao diện SPA hiện đại, đáp ứng (responsive), hỗ trợ chế độ **Sáng/Tối**.
*   ✅ Hai chế độ xem: **Danh sách (List)** và **Lưới (Grid)**.
*   ✅ Quản lý tệp/thư mục: Tạo, Đổi tên, Xóa, Khôi phục.
*   ✅ Di chuyển tệp/thư mục đến bất kỳ đâu bằng **Modal Cây thư mục** hoặc **Kéo-thả**.
*   ✅ **Thùng rác** với khả năng khôi phục hoặc xóa vĩnh viễn.
*   ✅ Tải về hàng loạt dưới dạng file **ZIP**.

### Hiệu suất & Tập tin lớn
*   ✅ **Tải lên theo đoạn (Chunk Uploading):** Tải lên các tệp dung lượng cực lớn một cách ổn định.
*   ✅ **Tải về theo dòng (Streamed Downloads):** Tải xuống các tệp lớn ngay lập tức với mức sử dụng bộ nhớ server tối thiểu.
*   ✅ **Tối ưu hóa Gzip:** Giảm kích thước dữ liệu truyền tải, tăng tốc độ tải trang trên mạng chậm.

### Tiện ích & Chia sẻ
*   ✅ **Xem trước tệp đa năng:** Hỗ trợ xem trước hình ảnh, video, âm thanh, PDF, và mã nguồn.
*   ✅ **Tìm kiếm thông minh:** Tìm kiếm trực tiếp (live search) và tìm kiếm toàn trang.
*   ✅ **Chia sẻ nâng cao:**
    *   Đặt **mật khẩu** cho liên kết chia sẻ.
    *   Thiết lập **ngày hết hạn**.
    *   Tùy chọn **cho phép/không cho phép tải về**.
*   ✅ **Hệ thống tài khoản** linh hoạt với tùy chọn bật/tắt đăng ký.
*   ✅ **Chế độ Developer:** Tắt hoàn toàn xác thực để phát triển trên localhost.

## Yêu cầu

*   Máy chủ web (khuyên dùng Apache với `mod_rewrite`, hoặc Nginx).
*   PHP 8.0 trở lên.
*   **PHP Extensions:**
    *   `pdo_sqlite` (bắt buộc).
    *   `zip` (bắt buộc).

## Cài đặt nhanh

1.  **Tải mã nguồn:** Tải về và giải nén dự án.
2.  **Tải lên máy chủ:** Upload các tệp đã giải nén lên thư mục web của bạn.
3.  **Cấp quyền ghi:** Đảm bảo thư mục gốc của dự án và thư mục con `database/` có quyền ghi cho user của web server.
    *   *Trên Linux:* `chmod -R 775 /path/to/nexus-drive` và `chown -R www-data:www-data /path/to/nexus-drive`.
4.  **Truy cập:** Mở trình duyệt và truy cập vào URL của bạn. Ứng dụng sẽ tự động tạo cơ sở dữ liệu, tệp người dùng và đưa bạn đến trang đăng nhập.

**Tài khoản mặc định:**
*   **Tên đăng nhập:** `admin`
*   **Mật khẩu:** `admin`

## Cấu hình

Mở tệp `bootstrap.php` để tùy chỉnh các cài đặt chính:

*   `define('APP_NAME', 'Nexus Drive');`: Đặt tên ứng dụng của bạn.
*   `define('AUTH_ENABLED', true);`:
    *   `true`: Bật hệ thống đăng nhập (cho môi trường production).
    *   `false`: Tắt đăng nhập (cho development trên localhost).
*   `define('ALLOW_REGISTRATION', false);`:
    *   `true`: Cho phép người dùng tự đăng ký.
    *   `false`: Tắt đăng ký công khai.

## Cấu trúc dự án

*   `index.php`: Giao diện chính (View) và JavaScript phía client.
*   `api.php`: API Gateway xử lý tất cả logic nghiệp vụ.
*   `bootstrap.php`: Tệp lõi chứa cấu hình, hàm tiện ích, và kiểm tra xác thực.
*   `share.php`: Trang xem công khai cho các liên kết chia sẻ.
*   `login.php`, `register.php`, `logout.php`: Các trang xác thực.
*   `database/database.sqlite`: Tệp cơ sở dữ liệu SQLite.
*   `users.php`: Tệp lưu trữ thông tin người dùng.
*   `.htaccess`: Cấu hình cho máy chủ Apache.
*   `src/`: Thư mục chứa các tài nguyên như CSS, JS libraries, fonts, images.

## Giấy phép

Dự án này được phát hành dưới [Giấy phép MIT](LICENSE).  
