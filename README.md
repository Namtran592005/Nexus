# Nexus Drive

<p align="center">
  <img src="./Screenshot.png" alt="Nexus Drive Screenshot" width="800"/>
  <br>
  <em>Giao diện chính hiện đại, hỗ trợ chế độ Sáng & Tối của Nexus Drive.</em>
</p>

<p align="center">
  <strong>Một giải pháp lưu trữ đám mây cá nhân (self-hosted) mạnh mẽ, hiện đại và siêu nhẹ.</strong>
  <br>
  <a href="#-giới-thiệu">Tiếng Việt</a>
</p>

---

## 🚀 Introduction

**Nexus Drive** is a high-performance, modern, and exceptionally lightweight self-hosted cloud storage solution. Built with a minimalist stack (PHP & SQLite), it delivers a fluid, real-time user experience rivaling top-tier services, all within a simple, portable, and zero-setup architecture.

It's the ultimate solution for individuals and teams who demand full control over their data, offering a beautiful interface and professional-grade features without the bloat and complexity of heavier systems.

## ✨ Why Choose Nexus Drive?

*   **⚡ Blazing Fast Experience:** A true **Single Page Application (SPA)** architecture provides instant navigation and file management with zero page reloads. The interface feels snappy and responsive at every click.
*   **💎 Elegant & Modern UI:** A beautifully crafted interface with stunning **Light and Dark modes**, enhanced by a subtle **animated gradient background** for a premium feel.
*   **🚀 High-Performance Backend:** Engineered for speed. **Gzip compression** minimizes bandwidth, **streamed responses** handle massive file downloads instantly, and an **Optimistic UI** provides immediate feedback on actions.
*   **📦 Absolute Portability:** The entire application, including the database and all file contents, is self-contained. Back up, migrate, or deploy on a new server with a simple copy-paste.
*   **🔧 Zero-Setup Deployment:** No complex installation scripts or database configuration. Just upload, grant permissions, and you're live. The application intelligently self-initializes on first run.

## 📋 Feature List

### Core Functionality
*   ✅ Stunning, responsive SPA interface with **Light/Dark modes** and an **animated background**.
*   ✅ Dual view modes: feature-rich **List view** and visual **Grid view**.
*   ✅ Full file & folder management: Create, Rename, Delete, Restore from Trash.
*   ✅ Effortless file organization: Move items via an intuitive **Folder Tree Modal** or fluid **Drag & Drop**.
*   ✅ Secure **Recycle Bin** with options to restore items or empty permanently.
*   ✅ Download multiple items as a single, compressed **ZIP archive**.

### Performance & Large Files
*   ✅ **Resumable Chunk Uploading:** Reliably upload gigabyte-sized files without server timeouts.
*   ✅ **Memory-Efficient Streamed Downloads:** Download large files instantly with minimal server memory footprint.
*   ✅ **Gzip Optimization:** Reduces data transfer size, accelerating load times on all network conditions.

### Advanced Utilities
*   ✅ **Powerful Universal File Previewer:**
    *   📄 **Documents:** PDFs (native browser), `.docx`, `.xlsx` rendered client-side.
    *   💻 **Code:** A built-in code editor with syntax highlighting for dozens of languages (`.js`, `.py`, `.php`, `.sql`, `.yml`, etc.).
    *   🖼️ **Media:** Images, videos, and audio playback directly in the browser.
*   ✅ **Intelligent Search:** Instant **live search** dropdown and a dedicated full-page search view.
*   ✅ **Advanced Sharing Control:**
    *   🔒 Secure shared links with **passwords**.
    *   ⏳ Set link **expiration dates**.
    *   🔽 Allow or **disable downloads** for view-only sharing.
    *   🗑️ Easily manage and **unshare** multiple files at once from the "Shared" view.
*   ✅ **Flexible Authentication:** A simple, file-based account system with optional public registration.
*   ✅ **Developer Mode:** Disable authentication entirely for seamless local development.

## 🛠️ Requirements

*   Web server (Apache with `mod_rewrite`, or Nginx).
*   PHP 8.0 or higher.
*   **Required PHP Extensions:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ Quick Setup

1.  **Download:** Download and extract the latest release.
2.  **Upload:** Place the extracted files into your web server's public directory.
3.  **Permissions:** Grant write permissions to the web server user for the project's root directory.
    *   *On Linux:* `chmod -R 775 /path/to/nexus-drive` and `chown -R www-data:www-data /path/to/nexus-drive`.
4.  **Access:** Open your browser and navigate to your URL. The application will auto-create the database and user files, then redirect you to the login page.

**Default Account:**
*   **Username:** `admin`
*   **Password:** `admin`

## 🔧 Configuration

All main settings are conveniently located at the top of `bootstrap.php`:

*   `define('APP_NAME', 'Nexus Drive');`: Set your application's name.
*   `define('AUTH_ENABLED', true);`:
    *   `true`: (Production) Enables the login system.
    *   `false`: (Development) Disables authentication for easy local access.
*   `define('ALLOW_REGISTRATION', false);`:
    *   `true`: Allows new users to register an account.
    *   `false`: Disables public registration.

## 📂 Project Structure

```
/
├── index.php             # Main SPA view and client-side logic
├── api.php               # API gateway for all backend actions
├── bootstrap.php         # Core config, helpers, and auth logic
├── share.php             # Public page for shared links
├── login.php             # Authentication pages
├── register.php
├── logout.php
├── database/
│   └── database.sqlite   # SQLite database (stores file metadata)
├── users.php             # File-based user storage
├── .htaccess             # Apache rewrite rules
└── src/                  # CSS, JS libraries, fonts, and other assets
```

## 📜 License

This project is licensed under the [MIT License](LICENSE).

---
---

# 🇻🇳 Giới thiệu (Tiếng Việt)

<p align="center">
  <strong>Một giải pháp lưu trữ đám mây cá nhân (self-hosted) mạnh mẽ, hiện đại và siêu nhẹ.</strong>
</p>

## 🚀 Giới thiệu

**Nexus Drive** là một giải pháp lưu trữ đám mây cá nhân (self-hosted) hiệu suất cao, hiện đại và cực kỳ nhẹ. Được xây dựng với PHP & SQLite, Nexus Drive mang đến một trải nghiệm người dùng mượt mà, không thua kém các dịch vụ hàng đầu, gói gọn trong một kiến trúc đơn giản, di động và không cần cài đặt.

Đây là giải pháp tối ưu cho cá nhân và đội nhóm muốn toàn quyền kiểm soát dữ liệu, với giao diện đẹp mắt và tính năng chuyên nghiệp mà không bị cồng kềnh bởi các hệ thống phức tạp.

## ✨ Tại sao chọn Nexus Drive?

*   **⚡ Trải nghiệm siêu tốc:** Kiến trúc **Single Page Application (SPA)** thực thụ giúp mọi thao tác điều hướng và quản lý tệp diễn ra tức thì, không cần tải lại trang. Giao diện phản hồi ngay lập tức sau mỗi cú nhấp chuột.
*   **💎 Giao diện Tinh tế & Hiện đại:** Giao diện được thiết kế đẹp mắt với chế độ **Sáng & Tối** ấn tượng, được tô điểm bằng **nền gradient chuyển động** tinh tế, mang lại cảm giác cao cấp.
*   **🚀 Backend hiệu suất cao:** Được thiết kế cho tốc độ. **Nén Gzip** giảm thiểu băng thông, **phản hồi streaming** xử lý tải các tệp khổng lồ ngay lập tức, và **Optimistic UI** cho cảm giác phản hồi tức thì.
*   **📦 Di động tuyệt đối:** Toàn bộ ứng dụng, bao gồm cả cơ sở dữ liệu và nội dung file, đều nằm gọn trong một thư mục. Sao lưu, di chuyển hay triển khai trên máy chủ mới chỉ bằng một thao tác sao chép-dán.
*   **🔧 Zero-Setup:** Không cần kịch bản cài đặt hay cấu hình cơ sở dữ liệu phức tạp. Chỉ cần tải lên, cấp quyền và bạn đã sẵn sàng. Ứng dụng tự khởi tạo thông minh trong lần chạy đầu tiên.

## 📋 Danh sách tính năng

### Chức năng cốt lõi
*   ✅ Giao diện SPA ấn tượng, responsive, hỗ trợ chế độ **Sáng/Tối** và **nền động**.
*   ✅ Hai chế độ xem: **Danh sách** đa tính năng và **Lưới** trực quan.
*   ✅ Quản lý tệp & thư mục: Tạo, Đổi tên, Xóa, Khôi phục từ Thùng rác.
*   ✅ Tổ chức tệp dễ dàng: Di chuyển mục bằng **Modal Cây thư mục** hoặc **Kéo-thả** mượt mà.
*   ✅ **Thùng rác** an toàn với tùy chọn khôi phục hoặc xóa vĩnh viễn.
*   ✅ Tải nhiều mục về dưới dạng một file **nén ZIP**.

### Hiệu suất & Tập tin lớn
*   ✅ **Tải lên theo đoạn (Chunk Uploading):** Tải lên các tệp hàng gigabyte một cách ổn định, không lo hết thời gian chờ của máy chủ.
*   ✅ **Tải về theo dòng (Streamed Downloads):** Tải các tệp lớn ngay lập tức với mức sử dụng bộ nhớ server tối thiểu.
*   ✅ **Tối ưu hóa Gzip:** Giảm kích thước dữ liệu truyền tải, tăng tốc độ tải trang trên mọi điều kiện mạng.

### Tiện ích nâng cao
*   ✅ **Trình xem trước tệp đa năng:**
    *   📄 **Tài liệu:** PDF (trình xem gốc), `.docx`, `.xlsx` được render phía client.
    *   💻 **Mã nguồn:** Trình soạn thảo code tích hợp với tô sáng cú pháp cho hàng chục ngôn ngữ (`.js`, `.py`, `.php`, `.sql`, `.yml`, v.v.).
    *   🖼️ **Media:** Xem ảnh, video và nghe nhạc trực tiếp trên trình duyệt.
*   ✅ **Tìm kiếm thông minh:** **Tìm kiếm trực tiếp** (live search) và trang tìm kiếm chuyên dụng.
*   ✅ **Kiểm soát chia sẻ nâng cao:**
    *   🔒 Bảo vệ liên kết chia sẻ bằng **mật khẩu**.
    *   ⏳ Đặt **ngày hết hạn** cho liên kết.
    *   🔽 Cho phép hoặc **chặn tải về** để chia sẻ ở chế độ chỉ xem.
    *   🗑️ Dễ dàng quản lý và **ngừng chia sẻ** nhiều tệp cùng lúc từ mục "Đã chia sẻ".
*   ✅ **Xác thực linh hoạt:** Hệ thống tài khoản đơn giản dựa trên tệp, có tùy chọn cho phép đăng ký công khai.
*   ✅ **Chế độ Developer:** Tắt hoàn toàn xác thực để phát triển trên localhost một cách liền mạch.

## 🛠️ Yêu cầu

*   Máy chủ web (Apache với `mod_rewrite`, hoặc Nginx).
*   PHP 8.0 trở lên.
*   **PHP Extensions bắt buộc:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ Cài đặt nhanh

1.  **Tải về:** Tải và giải nén phiên bản mới nhất.
2.  **Upload:** Đặt các tệp đã giải nén vào thư mục công khai của máy chủ web.
3.  **Cấp quyền:** Cấp quyền ghi cho người dùng của máy chủ web trên thư mục gốc của dự án.
    *   *Trên Linux:* `chmod -R 775 /path/to/nexus-drive` và `chown -R www-data:www-data /path/to/nexus-drive`.
4.  **Truy cập:** Mở trình duyệt và truy cập URL của bạn. Ứng dụng sẽ tự tạo CSDL, tệp người dùng và chuyển hướng bạn đến trang đăng nhập.

**Tài khoản mặc định:**
*   **Tên đăng nhập:** `admin`
*   **Mật khẩu:** `admin`

## 🔧 Cấu hình

Tất cả cài đặt chính được đặt ở đầu tệp `bootstrap.php`:

*   `define('APP_NAME', 'Nexus Drive');`: Đặt tên ứng dụng của bạn.
*   `define('AUTH_ENABLED', true);`:
    *   `true`: (Production) Bật hệ thống đăng nhập.
    *   `false`: (Development) Tắt xác thực để truy cập dễ dàng trên localhost.
*   `define('ALLOW_REGISTRATION', false);`:
    *   `true`: Cho phép người dùng mới tự đăng ký.
    *   `false`: Tắt đăng ký công khai.

## 📜 Giấy phép

Dự án này được cấp phép theo [Giấy phép MIT](LICENSE)
