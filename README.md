
---

# Nexus Drive

<p align="center">
  <img src="./Screenshot.png" alt="Nexus Drive Screenshot" width="800"/>
  <br>
  <em>Giao diện chính hiện đại, hỗ trợ chế độ Sáng & Tối của Nexus Drive.</em>
</p>

---

## 🚀 Introduction

**Nexus Drive** is a high-performance, secure, and exceptionally lightweight self-hosted cloud storage solution. Built with a minimalist stack (PHP & SQLite), it delivers a fluid, real-time user experience rivaling top-tier services, all within a simple, portable, and zero-setup architecture.

It's the ultimate solution for individuals and teams who demand **full control and privacy** over their data, offering a beautiful, responsive interface and professional-grade features without the bloat and complexity of heavier systems.

## ✨ Why Choose Nexus Drive?

*   🛡️ **Enterprise-Grade Security:** Your data is protected by a robust security model, including **Two-Factor Authentication (2FA)** and **Brute-Force Protection** to prevent unauthorized access.
*   ⚡ **Blazing Fast Experience:** A true **Single Page Application (SPA)** architecture provides instant navigation and file management with zero page reloads. The interface feels snappy and responsive at every click.
*   💎 **Elegant & Modern UI:** A beautifully crafted, fully responsive interface with stunning **Light and Dark modes**. The experience is enhanced by a subtle **animated gradient background** for a premium feel.
*   🚀 **High-Performance Backend:** Engineered for speed. **Gzip compression** minimizes bandwidth, **streamed responses** handle massive file downloads instantly, and an **Optimistic UI** provides immediate feedback on actions.
*   📦 **Absolute Portability:** The entire application, including the database and all file contents, is self-contained. Back up, migrate, or deploy on a new server with a simple copy-paste.
*   🔧 **Zero-Setup Deployment:** No complex installation scripts or database configuration. Just upload, grant permissions, and you're live. The application intelligently self-initializes on first run.

## 📋 Feature List

### 🛡️ Security & Authentication
*   ✅ **Two-Factor Authentication (2FA):** Secure your account with TOTP-based 2FA using apps like Google Authenticator or Authy.
*   ✅ **Brute-Force Protection:** Automatically blocks IP addresses after multiple failed login attempts.
*   ✅ **Advanced Sharing Control:**
    *   🔒 Secure shared links with **passwords**.
    *   ⏳ Set link **expiration dates** with a user-friendly date picker.
    *   🔽 Allow or **disable downloads** for view-only sharing.
*   ✅ **Secure Password Hashing:** Uses the industry-standard `bcrypt` algorithm.

### 🎨 Interface & User Experience
*   ✅ Stunning, fully **responsive** SPA interface that works beautifully on desktop and mobile.
*   ✅ **Light/Dark modes** and an **animated background**.
*   ✅ Dual view modes: feature-rich **List view** and visual **Grid view**.
*   ✅ Effortless file organization with an intuitive **Folder Tree Modal** or fluid **Drag & Drop**.
*   ✅ Context-aware **right-click menu** for quick actions.
*   ✅ Secure **Recycle Bin** with options to restore items or empty permanently.

### 🗂️ File Management & Utilities
*   ✅ **Powerful Universal File Previewer:**
    *   📄 **Documents:** PDFs (native), `.docx`, and `.xlsx` are rendered directly in the browser.
    *   💻 **Code Editor:** A built-in ACE editor with syntax highlighting for dozens of languages.
    *   🖼️ **Media:** In-browser playback for images, videos, and audio.
*   ✅ **Resumable Chunk Uploading:** Reliably upload gigabyte-sized files.
*   ✅ **Memory-Efficient Streamed Downloads:** Download large files instantly.
*   ✅ Download multiple items as a single, compressed **ZIP archive**.
*   ✅ **Intelligent Search:** Instant **live search** and a dedicated full-page search.
*   ✅ Manage shared links easily from the dedicated "Shared" view.

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
├── login.php             # Authentication pages (Step 1)
├── verify_2fa.php        # 2FA Verification (Step 2)
├── setup_2fa.php         # 2FA Management page
├── register.php
├── logout.php
├── lib/
│   └── TwoFactorAuth.php # 2FA library
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

# Giới thiệu

<p align="center">
  <strong>Một giải pháp lưu trữ đám mây cá nhân (self-hosted) an toàn, hiệu suất cao và siêu nhẹ.</strong>
</p>

## 🚀 Giới thiệu

**Nexus Drive** là một giải pháp lưu trữ đám mây cá nhân (self-hosted) hiệu suất cao, an toàn và cực kỳ nhẹ. Được xây dựng với PHP & SQLite, Nexus Drive mang đến một trải nghiệm người dùng mượt mà, không thua kém các dịch vụ hàng đầu, gói gọn trong một kiến trúc đơn giản, di động và không cần cài đặt.

Đây là giải pháp tối ưu cho cá nhân và đội nhóm muốn **toàn quyền kiểm soát và bảo mật** dữ liệu, với giao diện đẹp mắt, responsive và tính năng chuyên nghiệp mà không bị cồng kềnh bởi các hệ thống phức tạp.

## ✨ Tại sao chọn Nexus Drive?

*   🛡️ **Bảo mật Đẳng cấp Doanh nghiệp:** Dữ liệu của bạn được bảo vệ bởi một mô hình bảo mật vững chắc, bao gồm **Xác thực hai yếu tố (2FA)** và **Chống tấn công Brute-Force** để ngăn chặn truy cập trái phép.
*   ⚡ **Trải nghiệm siêu tốc:** Kiến trúc **Single Page Application (SPA)** thực thụ giúp mọi thao tác điều hướng và quản lý tệp diễn ra tức thì, không cần tải lại trang. Giao diện phản hồi ngay lập tức sau mỗi cú nhấp chuột.
*   💎 **Giao diện Tinh tế & Hiện đại:** Giao diện được thiết kế đẹp mắt, **responsive toàn diện**, hoạt động hoàn hảo trên desktop và mobile. Trải nghiệm được nâng tầm với chế độ **Sáng & Tối** ấn tượng và **nền gradient chuyển động** tinh tế.
*   🚀 **Backend hiệu suất cao:** Được thiết kế cho tốc độ. **Nén Gzip** giảm thiểu băng thông, **phản hồi streaming** xử lý tải các tệp khổng lồ ngay lập tức, và **Optimistic UI** cho cảm giác phản hồi tức thì.
*   📦 **Di động tuyệt đối:** Toàn bộ ứng dụng, bao gồm cả cơ sở dữ liệu và nội dung file, đều nằm gọn trong một thư mục. Sao lưu, di chuyển hay triển khai trên máy chủ mới chỉ bằng một thao tác sao chép-dán.
*   🔧 **Zero-Setup:** Không cần kịch bản cài đặt hay cấu hình cơ sở dữ liệu phức tạp. Chỉ cần tải lên, cấp quyền và bạn đã sẵn sàng. Ứng dụng tự khởi tạo thông minh trong lần chạy đầu tiên.

## 📋 Danh sách tính năng

### 🛡️ Bảo mật & Xác thực
*   ✅ **Xác thực hai yếu tố (2FA):** Bảo vệ tài khoản của bạn bằng mã TOTP qua các ứng dụng như Google Authenticator hoặc Authy.
*   ✅ **Chống tấn công Brute-Force:** Tự động khóa IP sau nhiều lần đăng nhập thất bại.
*   ✅ **Kiểm soát chia sẻ nâng cao:**
    *   🔒 Bảo vệ liên kết chia sẻ bằng **mật khẩu**.
    *   ⏳ Đặt **ngày hết hạn** với giao diện chọn ngày thân thiện.
    *   🔽 Cho phép hoặc **chặn tải về** để chia sẻ ở chế độ chỉ xem.
*   ✅ **Băm mật khẩu an toàn:** Sử dụng thuật toán `bcrypt` tiêu chuẩn ngành.

### 🎨 Giao diện & Trải nghiệm người dùng
*   ✅ Giao diện SPA **responsive toàn diện**, hoạt động mượt mà trên desktop và mobile.
*   ✅ Chế độ **Sáng/Tối** và **nền động**.
*   ✅ Hai chế độ xem: **Danh sách** đa tính năng và **Lưới** trực quan.
*   ✅ Tổ chức tệp dễ dàng bằng **Modal Cây thư mục** hoặc **Kéo-thả** mượt mà.
*   ✅ **Menu chuột phải** theo ngữ cảnh để thực hiện thao tác nhanh.
*   ✅ **Thùng rác** an toàn với tùy chọn khôi phục hoặc xóa vĩnh viễn.

### 🗂️ Quản lý File & Tiện ích
*   ✅ **Trình xem trước tệp đa năng:**
    *   📄 **Tài liệu:** PDF (trình xem gốc), `.docx` và `.xlsx` được render trực tiếp trên trình duyệt.
    *   💻 **Trình soạn thảo code:** Tích hợp ACE editor với tô sáng cú pháp cho hàng chục ngôn ngữ.
    *   🖼️ **Media:** Xem ảnh, video và nghe nhạc trực tiếp.
*   ✅ **Tải lên theo đoạn (Chunk Uploading):** Tải lên các tệp hàng gigabyte một cách ổn định.
*   ✅ **Tải về theo dòng (Streamed Downloads):** Tải các tệp lớn ngay lập tức.
*   ✅ Tải nhiều mục về dưới dạng một file **nén ZIP**.
*   ✅ **Tìm kiếm thông minh:** **Tìm kiếm trực tiếp** (live search) và trang tìm kiếm chuyên dụng.
*   ✅ Dễ dàng quản lý các link đã chia sẻ từ mục "Đã chia sẻ".

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

Dự án này được cấp phép theo [Giấy phép MIT](LICENSE).