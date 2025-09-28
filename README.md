# Nexus Drive: The Multi-User Self-Hosted Cloud

<p align="center">
  <img src="./Screenshot.png" alt="Nexus Drive Screenshot" width="800"/>
  <br>
  <em>Giao diện chính hiện đại, hỗ trợ chế độ Sáng & Tối của Nexus Drive.</em>
</p>

---

## 🚀 Introduction

**Nexus Drive** is now a high-performance, **multi-tenant** (multi-user), and exceptionally lightweight self-hosted cloud storage solution. Built with a minimalist stack (PHP & SQLite), it provides segregated storage for each user and delivers a fluid, real-time experience without the complexities of heavy frameworks.

It is the ideal platform for small teams, families, or power-users who demand **full control, privacy, and user management** over their data, all within a simple, highly portable, and zero-setup architecture.

## ✨ Why Choose Nexus Drive?

*   🔐 **Multi-User Architecture:** Each user operates within their own isolated SQLite database, ensuring **strict data separation and privacy**.
*   🛡️ **Admin Control Panel:** The dedicated admin account gains powerful tools, including **User Management (Lock/Unlock/Delete accounts)** and the ability to **Impersonate (Switch to) other users' accounts** for direct data inspection and support.
*   ⚡ **Blazing Fast SPA:** A true **Single Page Application (SPA)** provides instant navigation, rivaling native application feel.
*   💎 **Elegant & Modern UI:** Stunning, fully responsive UI with beautiful **Light and Dark modes** and subtle **animated gradient backgrounds**.
*   🚀 **High-Performance Backend:** **Gzip compression**, **streamed responses** for large file downloads, and robust **chunk uploading** for reliable handling of gigabyte-sized files.
*   📦 **Absolute Portability:** The entire application is self-contained. Each user's data is stored in a simple `.sqlite` file (e.g., `user.sqlite`), making backup and migration trivial.

## 📋 Feature List

### 👑 New Admin & Multi-User Features
*   ✅ **User Management:** View all users, their storage usage, and status (locked/active).
*   ✅ **Impersonation Mode:** Admin can "Switch to" any user's drive to manage files directly on their behalf.
*   ✅ **Account Control:** Lock, unlock, or permanently delete user accounts and their associated database files.
*   ✅ **Global Settings:** Toggle **Public Registration** directly from the Admin's settings panel.

### 🛡️ Security & Authentication
*   ✅ **Two-Factor Authentication (2FA):** Secure your login with TOTP-based 2FA (Google Authenticator, Authy).
*   ✅ **Brute-Force Protection:** Automatically blocks IP addresses after failed login attempts.
*   ✅ **Advanced Sharing Control:**
    *   🔒 Secure shared links with **passwords**.
    *   ⏳ Set link **expiration dates**.
    *   🔽 Allow or **disable downloads** for view-only sharing.
*   ✅ **Secure Password Hashing:** Uses the industry-standard `bcrypt` algorithm.

### 🗂️ File Management & Utilities
*   ✅ **Powerful Universal File Previewer:**
    *   📄 **Documents:** PDFs (native), `.docx`, and `.xlsx` are rendered directly in the browser.
    *   💻 **Code Editor:** Built-in ACE editor with syntax highlighting.
    *   🖼️ **Media:** In-browser playback for images, videos, and audio.
*   ✅ **Resumable Chunk Uploading:** Reliably upload gigabyte-sized files.
*   ✅ Download multiple items as a single, compressed **ZIP archive**.
*   ✅ **Intelligent Search:** Instant **live search** and a dedicated full-page search.
*   ✅ **Drag & Drop** file and folder manipulation.
*   ✅ Secure **Recycle Bin** with options to restore or permanently delete.

## 🛠️ Requirements

*   Web server (Apache with `mod_rewrite`, or Nginx).
*   PHP 8.0 or higher.
*   **Required PHP Extensions:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ Quick Setup

1.  **Download:** Download and extract the latest release.
2.  **Upload:** Place the extracted files into your web server's public directory.
3.  **Permissions:** Grant write permissions to the web server user for the project's root directory and the `/database` folder.
    *   *On Linux:* `chmod -R 775 /path/to/nexus-drive` and `chown -R www-data:www-data /path/to/nexus-drive`.
4.  **Access:** Open your browser and navigate to your URL. The application will auto-create the main database (`database/database.sqlite`), then redirect you to the login page.

**Default Admin Account:**
*   **Username:** `admin`
*   **Password:** `admin`

## 📂 Project Structure

| File/Folder | Purpose |
| :--- | :--- |
| `index.php` | Main Single Page Application (SPA) view and client-side logic. |
| `api.php` | REST API gateway for all backend actions (handles all CRUD/Admin logic). |
| `bootstrap.php` | Core Configuration, helper functions, and **Multi-DB Connection logic**. |
| `database/` | **Storage for all user data files.** |
| ├── `database.sqlite` | Main **Admin/System** database (stores admin's data, login attempts). |
| └── `[user].sqlite` | **Isolated personal database for each regular user.** |
| `users.php` | File-based storage for user accounts, passwords, 2FA secrets, and lock status. |
| `app_config.json` | Stores global application settings (e.g., `ALLOW_REGISTRATION`). |
| `setup_2fa.php` | 2FA Management page. |
| `src/` | CSS, JS libraries, fonts, and other assets. |

## 📜 License

This project is licensed under the [MIT License](LICENSE).

---
---

# Giới thiệu

<p align="center">
  <strong>Giải pháp lưu trữ đám mây cá nhân (Multi-User Self-Hosted Cloud) an toàn, hiệu suất cao và siêu nhẹ.</strong>
</p>

## 🚀 Giới thiệu

**Nexus Drive** giờ đây là một giải pháp lưu trữ đám mây cá nhân (self-hosted) **đa người dùng (multi-tenant)**, hiệu suất cao và cực kỳ nhẹ. Được xây dựng với PHP & SQLite, Nexus Drive cung cấp không gian lưu trữ tách biệt hoàn toàn cho mỗi người dùng và mang đến một trải nghiệm mượt mà, không cần cài đặt.

Đây là nền tảng lý tưởng cho các nhóm nhỏ, gia đình hoặc người dùng chuyên nghiệp muốn **toàn quyền kiểm soát, bảo mật và quản lý người dùng**, tất cả gói gọn trong một kiến trúc đơn giản và di động.

## ✨ Tại sao chọn Nexus Drive?

*   🔐 **Kiến trúc Đa người dùng:** Mỗi người dùng hoạt động trong một cơ sở dữ liệu SQLite riêng biệt, đảm bảo **tách biệt và bảo mật dữ liệu tuyệt đối**.
*   🛡️ **Bảng điều khiển Quản trị (Admin):** Tài khoản admin độc quyền có các công cụ mạnh mẽ như **Quản lý người dùng (Khóa/Mở khóa/Xóa tài khoản)** và khả năng **Đăng nhập mạo danh (Impersonate)** tài khoản người dùng khác để hỗ trợ trực tiếp.
*   ⚡ **SPA siêu tốc:** Kiến trúc **Single Page Application (SPA)** thực thụ giúp mọi thao tác điều hướng diễn ra tức thì.
*   💎 **Giao diện Tinh tế & Hiện đại:** Giao diện **responsive toàn diện** với chế độ **Sáng & Tối** đẹp mắt và **nền gradient chuyển động** tinh tế.
*   🚀 **Backend hiệu suất cao:** **Nén Gzip**, **phản hồi streaming** cho tải về tệp lớn và **tải lên theo đoạn (chunk uploading)** ổn định cho các tệp hàng gigabyte.
*   📦 **Di động tuyệt đối:** Ứng dụng tự chứa hoàn toàn. Dữ liệu của mỗi người dùng nằm trong một tệp `.sqlite` đơn giản, giúp sao lưu và di chuyển cực kỳ dễ dàng.

## 📋 Danh sách tính năng

### 👑 Tính năng Quản trị & Đa người dùng mới
*   ✅ **Quản lý Người dùng:** Xem tất cả người dùng, dung lượng lưu trữ và trạng thái (khóa/hoạt động).
*   ✅ **Chế độ Mạo danh (Impersonation):** Admin có thể "Chuyển sang" ổ đĩa của bất kỳ người dùng nào để quản lý tệp trực tiếp (hỗ trợ/kiểm tra).
*   ✅ **Kiểm soát Tài khoản:** Khóa, mở khóa, hoặc xóa vĩnh viễn tài khoản người dùng cùng với tệp CSDL liên quan.
*   ✅ **Cài đặt Toàn cục:** Bật/tắt **Đăng ký Công khai** trực tiếp từ bảng cài đặt của Admin.

### 🛡️ Bảo mật & Xác thực
*   ✅ **Xác thực hai yếu tố (2FA):** Bảo vệ đăng nhập bằng mã TOTP (Google Authenticator, Authy).
*   ✅ **Chống tấn công Brute-Force:** Tự động khóa IP sau nhiều lần đăng nhập thất bại.
*   ✅ **Kiểm soát chia sẻ nâng cao:**
    *   🔒 Bảo vệ liên kết chia sẻ bằng **mật khẩu**.
    *   ⏳ Đặt **ngày hết hạn**.
    *   🔽 Cho phép hoặc **chặn tải về** (chế độ chỉ xem).
*   ✅ **Băm mật khẩu an toàn:** Sử dụng thuật toán `bcrypt` tiêu chuẩn ngành.

### 🗂️ Quản lý File & Tiện ích
*   ✅ **Trình xem trước tệp đa năng mạnh mẽ:**
    *   📄 **Tài liệu:** PDF (trình xem gốc), `.docx` và `.xlsx` được render trực tiếp.
    *   💻 **Trình soạn thảo code:** Tích hợp ACE editor với tô sáng cú pháp.
    *   🖼️ **Media:** Xem ảnh, video và nghe nhạc trực tiếp.
*   ✅ **Tải lên theo đoạn (Resumable Chunk Uploading):** Tải lên các tệp lớn một cách ổn định.
*   ✅ Tải nhiều mục về dưới dạng một file **nén ZIP**.
*   ✅ **Tìm kiếm thông minh:** **Tìm kiếm trực tiếp** (live search) và trang tìm kiếm chuyên dụng.
*   ✅ Thao tác Kéo-thả tệp và thư mục mượt mà.
*   ✅ **Thùng rác** an toàn với tùy chọn khôi phục hoặc xóa vĩnh viễn.

## 🛠️ Yêu cầu

*   Máy chủ web (Apache với `mod_rewrite`, hoặc Nginx).
*   PHP 8.0 trở lên.
*   **PHP Extensions bắt buộc:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ Cài đặt nhanh

1.  **Tải về:** Tải và giải nén phiên bản mới nhất.
2.  **Upload:** Đặt các tệp đã giải nén vào thư mục công khai của máy chủ web.
3.  **Cấp quyền:** Cấp quyền ghi cho người dùng của máy chủ web trên thư mục gốc và thư mục `/database`.
    *   *Trên Linux:* `chmod -R 775 /path/to/nexus-drive` và `chown -R www-data:www-data /path/to/nexus-drive`.
4.  **Truy cập:** Mở trình duyệt và truy cập URL của bạn. Ứng dụng sẽ tự động tạo CSDL chính (`database/database.sqlite`), sau đó chuyển hướng bạn đến trang đăng nhập.

**Tài khoản Admin mặc định:**
*   **Tên đăng nhập:** `admin`
*   **Mật khẩu:** `admin`

## 📂 Cấu trúc Dự án

| File/Thư mục | Mục đích |
| :--- | :--- |
| `index.php` | Giao diện ứng dụng chính (SPA) và logic client-side. |
| `api.php` | Cổng API REST cho tất cả các hành động backend (xử lý tất cả logic CRUD/Admin). |
| `bootstrap.php` | Cấu hình cốt lõi, hàm hỗ trợ và **Logic kết nối Đa CSDL**. |
| `database/` | **Nơi lưu trữ tất cả tệp dữ liệu người dùng.** |
| ├── `database.sqlite` | CSDL **Admin/Hệ thống** chính (lưu dữ liệu admin, nhật ký đăng nhập). |
| └── `[user].sqlite` | **CSDL cá nhân tách biệt cho mỗi người dùng thường.** |
| `users.php` | Lưu trữ tệp-dựa trên cho tài khoản, mật khẩu, mã 2FA và trạng thái khóa. |
| `app_config.json` | Lưu trữ các cài đặt ứng dụng toàn cục (ví dụ: `ALLOW_REGISTRATION`). |
| `setup_2fa.php` | Trang quản lý 2FA. |
| `src/` | Các thư viện CSS, JS, font và tài sản khác. |

## 📜 Giấy phép

Dự án này được cấp phép theo [Giấy phép MIT](LICENSE).

---

# Nexus Drive: La Nube Autoalojada Multi-Usuario

---

## 🚀 Introducción (Español)

**Nexus Drive** es ahora una solución de almacenamiento en la nube autoalojada de alto rendimiento, **multi-inquilino** (multi-usuario) y excepcionalmente ligera. Construida con una pila minimalista (PHP y SQLite), proporciona almacenamiento segregado para cada usuario y ofrece una experiencia fluida y en tiempo real sin las complejidades de los frameworks pesados.

Es la plataforma ideal para equipos pequeños, familias o usuarios avanzados que exigen **control total, privacidad y gestión de usuarios** sobre sus datos, todo dentro de una arquitectura simple, altamente portátil y de configuración cero.

## ✨ ¿Por qué Elegir Nexus Drive? (Español)

*   🔐 **Arquitectura Multi-Usuario:** Cada usuario opera dentro de su propia base de datos SQLite aislada, lo que garantiza una **estricta separación y privacidad de los datos**.
*   🛡️ **Panel de Control de Administrador:** La cuenta de administrador dedicada obtiene herramientas potentes, incluida la **Gestión de Usuarios (Bloquear/Desbloquear/Eliminar cuentas)** y la capacidad de **Suplantar (Cambiar a) las cuentas de otros usuarios** para inspección y soporte directo de datos.
*   ⚡ **SPA Ultrarrápida:** Una verdadera **Aplicación de una Sola Página (SPA)** proporciona navegación instantánea, rivalizando con la sensación de una aplicación nativa.
*   💎 **Interfaz de Usuario Elegante y Moderna:** Interfaz de usuario impresionante y totalmente receptiva con hermosos **modos Claro y Oscuro** y sutiles **fondos de gradiente animados**.
*   🚀 **Backend de Alto Rendimiento:** **Compresión Gzip**, **respuestas transmitidas** para descargas de archivos grandes y robusta **carga por fragmentos** para un manejo confiable de archivos de gigabytes.
*   📦 **Portabilidad Absoluta:** Toda la aplicación es autónoma. Los datos de cada usuario se almacenan en un simple archivo `.sqlite` (por ejemplo, `usuario.sqlite`), lo que hace que la copia de seguridad y la migración sean triviales.

## 📋 Lista de Características (Español)

### 👑 Nuevas Características de Administración y Multi-Usuario
*   ✅ **Gestión de Usuarios:** Ver todos los usuarios, su uso de almacenamiento y estado (bloqueado/activo).
*   ✅ **Modo de Suplantación:** El administrador puede "Cambiar a" la unidad de cualquier usuario para administrar archivos directamente en su nombre.
*   ✅ **Control de Cuentas:** Bloquear, desbloquear o eliminar permanentemente cuentas de usuario y sus archivos de base de datos asociados.
*   ✅ **Configuración Global:** Alternar el **Registro Público** directamente desde el panel de configuración del Administrador.

### 🛡️ Seguridad y Autenticación
*   ✅ **Autenticación de Dos Factores (2FA):** Asegure su inicio de sesión con 2FA basado en TOTP (Google Authenticator, Authy).
*   ✅ **Protección Contra Fuerza Bruta:** Bloquea automáticamente las direcciones IP después de intentos fallidos de inicio de sesión.
*   ✅ **Control Avanzado de Uso Compartido:**
    *   🔒 Asegure enlaces compartidos con **contraseñas**.
    *   ⏳ Establezca **fechas de vencimiento** de enlaces.
    *   🔽 Permitir o **deshabilitar descargas** para compartir solo para ver.
*   ✅ **Hash de Contraseña Seguro:** Utiliza el algoritmo `bcrypt` estándar de la industria.

### 🗂️ Gestión de Archivos y Utilidades
*   ✅ **Potente Visualizador Universal de Archivos:**
    *   📄 **Documentos:** Los PDF (nativos), `.docx` y `.xlsx` se renderizan directamente en el navegador.
    *   💻 **Editor de Código:** Un editor ACE integrado con resaltado de sintaxis.
    *   🖼️ **Medios:** Reproducción en el navegador de imágenes, videos y audio.
*   ✅ **Carga Reanudable por Fragmentos:** Cargue archivos de gigabytes de forma fiable.
*   ✅ Descargue múltiples elementos como un único **archivo ZIP** comprimido.
*   ✅ **Búsqueda Inteligente:** **Búsqueda en vivo** instantánea y una búsqueda dedicada de página completa.
*   ✅ Manipulación de archivos y carpetas mediante **Arrastrar y Soltar**.
*   ✅ **Papelera de Reciclaje** segura con opciones para restaurar o eliminar permanentemente.

## 🛠️ Requisitos (Español)

*   Servidor web (Apache con `mod_rewrite`, o Nginx).
*   PHP 8.0 o superior.
*   **Extensiones de PHP Requeridas:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ Configuración Rápida (Español)

1.  **Descargar:** Descargue y extraiga la última versión.
2.  **Subir:** Coloque los archivos extraídos en el directorio público de su servidor web.
3.  **Permisos:** Otorgue permisos de escritura al usuario del servidor web para el directorio raíz del proyecto y la carpeta `/database`.
    *   *En Linux:* `chmod -R 775 /ruta/a/nexus-drive` y `chown -R www-data:www-data /ruta/a/nexus-drive`.
4.  **Acceso:** Abra su navegador y navegue a su URL. La aplicación creará automáticamente la base de datos principal (`database/database.sqlite`), luego lo redirigirá a la página de inicio de sesión.

**Cuenta de Administrador Predeterminada:**
*   **Nombre de usuario:** `admin`
*   **Contraseña:** `admin`

## 📂 Estructura del Proyecto (Español)

| Archivo/Carpeta | Propósito |
| :--- | :--- |
| `index.php` | Vista principal de la Aplicación de una Sola Página (SPA) y lógica del lado del cliente. |
| `api.php` | Pasarela API REST para todas las acciones de backend (maneja toda la lógica CRUD/Admin). |
| `bootstrap.php` | Configuración central, funciones de ayuda y **lógica de conexión Multi-DB**. |
| `database/` | **Almacenamiento para todos los archivos de datos de usuario.** |
| ├── `database.sqlite` | Base de datos principal de **Administrador/Sistema** (almacena los datos del administrador, intentos de inicio de sesión). |
| └── `[user].sqlite` | **Base de datos personal aislada para cada usuario normal.** |
| `users.php` | Almacenamiento basado en archivos para cuentas de usuario, contraseñas, secretos 2FA y estado de bloqueo. |
| `app_config.json` | Almacena la configuración global de la aplicación (por ejemplo, `ALLOW_REGISTRATION`). |
| `setup_2fa.php` | Página de Gestión de 2FA. |
| `src/` | Bibliotecas CSS, JS, fuentes y otros activos. |

## 📜 Licencia (Español)

Este proyecto tiene licencia [MIT](LICENSE).

---
---

# Nexus Drive: Le Cloud Auto-hébergé Multi-Utilisateurs

---

## 🚀 Introduction (Français)

**Nexus Drive** est désormais une solution de stockage cloud auto-hébergée de haute performance, **multi-locataire** (multi-utilisateurs) et exceptionnellement légère. Construite avec une pile minimaliste (PHP et SQLite), elle fournit un stockage séparé pour chaque utilisateur et offre une expérience fluide et en temps réel sans les complexités des frameworks lourds.

C'est la plateforme idéale pour les petites équipes, les familles ou les utilisateurs expérimentés qui exigent un **contrôle total, la confidentialité et la gestion des utilisateurs** sur leurs données, le tout dans une architecture simple, hautement portable et à configuration zéro.

## ✨ Pourquoi Choisir Nexus Drive? (Français)

*   🔐 **Architecture Multi-Utilisateurs:** Chaque utilisateur opère dans sa propre base de données SQLite isolée, assurant une **séparation et une confidentialité strictes des données**.
*   🛡️ **Panneau de Configuration Administrateur:** Le compte administrateur dédié dispose d'outils puissants, notamment la **Gestion des Utilisateurs (Verrouiller/Déverrouiller/Supprimer des comptes)** et la possibilité d'**Usurper l'identité (Basculer vers) les comptes d'autres utilisateurs** pour une inspection et un support direct des données.
*   ⚡ **SPA Ultra-rapide:** Une véritable **Application à Page Unique (SPA)** offre une navigation instantanée, rivalisant avec la sensation d'une application native.
*   💎 **Interface Utilisateur Élégante et Moderne:** Une interface utilisateur époustouflante et entièrement réactive avec de magnifiques **modes Clair et Sombre** et de subtils **fonds de dégradé animés**.
*   🚀 **Backend Haute Performance:** **Compression Gzip**, **réponses en flux** pour les téléchargements de fichiers volumineux et **téléchargement par morceaux** robuste pour une gestion fiable des fichiers de l'ordre du gigaoctet.
*   📦 **Portabilité Absolue:** L'ensemble de l'application est autonome. Les données de chaque utilisateur sont stockées dans un simple fichier `.sqlite` (par exemple, `utilisateur.sqlite`), ce qui rend la sauvegarde et la migration triviales.

## 📋 Liste des Fonctionnalités (Français)

### 👑 Nouvelles Fonctionnalités d'Administration et Multi-Utilisateurs
*   ✅ **Gestion des Utilisateurs:** Afficher tous les utilisateurs, leur utilisation du stockage et leur statut (verrouillé/actif).
*   ✅ **Mode d'Usurpation d'identité:** L'administrateur peut "Basculer vers" le lecteur de n'importe quel utilisateur pour gérer les fichiers directement en son nom.
*   ✅ **Contrôle de Compte:** Verrouiller, déverrouiller ou supprimer définitivement les comptes utilisateur et leurs fichiers de base de données associés.
*   ✅ **Paramètres Globaux:** Basculer l'**Enregistrement Public** directement depuis le panneau de paramètres de l'administrateur.

### 🛡️ Sécurité et Authentification
*   ✅ **Authentification à Deux Facteurs (2FA):** Sécurisez votre connexion avec 2FA basé sur TOTP (Google Authenticator, Authy).
*   ✅ **Protection Contre la Force Brute:** Bloque automatiquement les adresses IP après des tentatives de connexion échouées.
*   ✅ **Contrôle de Partage Avancé:**
    *   🔒 Sécurisez les liens partagés avec des **mots de passe**.
    *   ⏳ Définissez des **dates d'expiration** des liens.
    *   🔽 Autoriser ou **désactiver les téléchargements** pour le partage en mode lecture seule.
*   ✅ **Hachage de Mot de Passe Sécurisé:** Utilise l'algorithme `bcrypt` standard de l'industrie.

### 🗂️ Gestion de Fichiers et Utilitaires
*   ✅ **Puissant Visualiseur de Fichiers Universel:**
    *   📄 **Documents:** Les PDF (natifs), `.docx` et `.xlsx` sont rendus directement dans le navigateur.
    *   💻 **Éditeur de Code:** Un éditeur ACE intégré avec coloration syntaxique.
    *   🖼️ **Médias:** Lecture dans le navigateur d'images, de vidéos et d'audio.
*   ✅ **Téléchargement Reprenable par Morceaux:** Téléchargez des fichiers de gigaoctets de manière fiable.
*   ✅ Téléchargez plusieurs éléments sous la forme d'une seule **archive ZIP** compressée.
*   ✅ **Recherche Intelligente:** **Recherche en direct** instantanée et une recherche dédiée en pleine page.
*   ✅ Manipulation de fichiers et de dossiers par **Glisser-Déposer**.
*   ✅ **Corbeille** sécurisée avec options de restauration ou de suppression permanente.

## 🛠️ Exigences (Français)

*   Serveur web (Apache avec `mod_rewrite`, ou Nginx).
*   PHP 8.0 ou supérieur.
*   **Extensions PHP Requises:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ Configuration Rapide (Français)

1.  **Télécharger:** Téléchargez et extrayez la dernière version.
2.  **Uploader:** Placez les fichiers extraits dans le répertoire public de votre serveur web.
3.  **Permissions:** Accordez des autorisations d'écriture à l'utilisateur du serveur web pour le répertoire racine du projet et le dossier `/database`.
    *   *Sous Linux:* `chmod -R 775 /chemin/vers/nexus-drive` et `chown -R www-data:www-data /chemin/vers/nexus-drive`.
4.  **Accéder:** Ouvrez votre navigateur et naviguez vers votre URL. L'application créera automatiquement la base de données principale (`database/database.sqlite`), puis vous redirigera vers la page de connexion.

**Compte Administrateur par Défaut:**
*   **Nom d'utilisateur:** `admin`
*   **Mot de passe:** `admin`

## 📂 Structure du Projet (Français)

| Fichier/Dossier | Objectif |
| :--- | :--- |
| `index.php` | Vue principale de l'Application à Page Unique (SPA) et logique côté client. |
| `api.php` | Passerelle API REST pour toutes les actions backend (gère toute la logique CRUD/Admin). |
| `bootstrap.php` | Configuration de base, fonctions d'aide et **logique de connexion Multi-BD**. |
| `database/` | **Stockage pour tous les fichiers de données utilisateur.** |
| ├── `database.sqlite` | Base de données principale **Admin/Système** (stocke les données de l'administrateur, tentatives de connexion). |
| └── `[user].sqlite` | **Base de données personnelle isolée pour chaque utilisateur normal.** |
| `users.php` | Stockage basé sur des fichiers pour les comptes utilisateur, les mots de passe, les secrets 2FA et le statut de verrouillage. |
| `app_config.json` | Stocke les paramètres d'application globaux (par exemple, `ALLOW_REGISTRATION`). |
| `setup_2fa.php` | Page de Gestion 2FA. |
| `src/` | Bibliothèques CSS, JS, polices et autres actifs. |

## 📜 Licence (Français)

Ce projet est sous licence [MIT](LICENSE).

---
---

# Nexus Drive: Die Multi-User Self-Hosted Cloud

---

## 🚀 Einführung (Deutsch)

**Nexus Drive** ist jetzt eine leistungsstarke, **mandantenfähige** (Multi-User) und außergewöhnlich leichtgewichtige Self-Hosted-Cloud-Speicherlösung. Gebaut mit einem minimalistischen Stack (PHP & SQLite), bietet es segregierten Speicher für jeden Benutzer und liefert ein flüssiges, Echtzeit-Erlebnis ohne die Komplexität schwerer Frameworks.

Es ist die ideale Plattform für kleine Teams, Familien oder Power-User, die **volle Kontrolle, Datenschutz und Benutzerverwaltung** über ihre Daten verlangen, alles innerhalb einer einfachen, hochportablen und Zero-Setup-Architektur.

## ✨ Warum Nexus Drive Wählen? (Deutsch)

*   🔐 **Multi-User-Architektur:** Jeder Benutzer arbeitet innerhalb seiner eigenen isolierten SQLite-Datenbank, was eine **strikte Datentrennung und Datenschutz** gewährleistet.
*   🛡️ **Admin-Steuerpult:** Das dedizierte Administratorkonto erhält leistungsstarke Werkzeuge, einschließlich der **Benutzerverwaltung (Konten sperren/entsperren/löschen)** und der Möglichkeit, **sich als andere Benutzer auszugeben (Switch to)**, um Daten direkt zu überprüfen und Support zu leisten.
*   ⚡ **Blitzschnelle SPA:** Eine echte **Single Page Application (SPA)** bietet sofortige Navigation, vergleichbar mit dem Gefühl einer nativen Anwendung.
*   💎 **Elegante und Moderne Benutzeroberfläche:** Atemberaubende, vollständig reaktionsfähige Benutzeroberfläche mit wunderschönen **Hell- und Dunkelmodi** und subtilen **animierten Farbverlaufshintergründen**.
*   🚀 **Hochleistungs-Backend:** **Gzip-Kompression**, **gestreamte Antworten** für große Datei-Downloads und robustes **Chunk-Uploading** für die zuverlässige Handhabung von Gigabyte-großen Dateien.
*   📦 **Absolute Portabilität:** Die gesamte Anwendung ist in sich geschlossen. Die Daten jedes Benutzers werden in einer einfachen `.sqlite`-Datei (z. B. `benutzer.sqlite`) gespeichert, was Sicherung und Migration trivial macht.

## 📋 Funktionsliste (Deutsch)

### 👑 Neue Admin- und Multi-User-Funktionen
*   ✅ **Benutzerverwaltung:** Alle Benutzer, deren Speichernutzung und Status (gesperrt/aktiv) anzeigen.
*   ✅ **Impersonation-Modus:** Der Administrator kann zur Freigabe eines Benutzers "wechseln", um Dateien direkt in seinem Namen zu verwalten.
*   ✅ **Kontosteuerung:** Benutzerkonten und die zugehörigen Datenbankdateien sperren, entsperren oder dauerhaft löschen.
*   ✅ **Globale Einstellungen:** **Öffentliche Registrierung** direkt über das Einstellungsfeld des Administrators umschalten.

### 🛡️ Sicherheit und Authentifizierung
*   ✅ **Zwei-Faktor-Authentifizierung (2FA):** Sichern Sie Ihre Anmeldung mit TOTP-basiertem 2FA (Google Authenticator, Authy).
*   ✅ **Schutz vor Brute-Force-Angriffen:** Blockiert IP-Adressen nach fehlgeschlagenen Anmeldeversuchen automatisch.
*   ✅ **Erweiterte Freigabesteuerung:**
    *   🔒 Freigabelinks mit **Passwörtern** sichern.
    *   ⏳ **Ablaufdaten** für Links festlegen.
    *   🔽 Downloads für Nur-Lese-Freigaben zulassen oder **deaktivieren**.
*   ✅ **Sichere Passwort-Hash-Funktion:** Verwendet den Industriestandard `bcrypt`-Algorithmus.

### 🗂️ Dateiverwaltung und Dienstprogramme
*   ✅ **Leistungsstarker Universal-Dateivorschauer:**
    *   📄 **Dokumente:** PDFs (nativ), `.docx` und `.xlsx` werden direkt im Browser gerendert.
    *   💻 **Code-Editor:** Ein integrierter ACE-Editor mit Syntax-Hervorhebung.
    *   🖼️ **Medien:** In-Browser-Wiedergabe für Bilder, Videos und Audio.
*   ✅ **Wiederaufnehmbares Chunk-Uploading:** Laden Sie Gigabyte-große Dateien zuverlässig hoch.
*   ✅ Laden Sie mehrere Elemente als ein einziges, komprimiertes **ZIP-Archiv** herunter.
*   ✅ **Intelligente Suche:** Sofortige **Live-Suche** und eine dedizierte Vollseiten-Suche.
*   ✅ **Drag & Drop** Datei- und Ordnerbearbeitung.
*   ✅ Sicherer **Papierkorb** mit Optionen zum Wiederherstellen oder dauerhaften Löschen.

## 🛠️ Anforderungen (Deutsch)

*   Webserver (Apache mit `mod_rewrite` oder Nginx).
*   PHP 8.0 oder höher.
*   **Erforderliche PHP-Erweiterungen:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ Schnelleinrichtung (Deutsch)

1.  **Herunterladen:** Laden Sie die neueste Version herunter und entpacken Sie sie.
2.  **Hochladen:** Platzieren Sie die extrahierten Dateien im öffentlichen Verzeichnis Ihres Webservers.
3.  **Berechtigungen:** Erteilen Sie dem Webserver-Benutzer Schreibberechtigungen für das Stammverzeichnis des Projekts und den Ordner `/database`.
    *   *Unter Linux:* `chmod -R 775 /pfad/zu/nexus-drive` und `chown -R www-data:www-data /pfad/zu/nexus-drive`.
4.  **Zugriff:** Öffnen Sie Ihren Browser und navigieren Sie zu Ihrer URL. Die Anwendung erstellt automatisch die Hauptdatenbank (`database/database.sqlite`) und leitet Sie dann zur Anmeldeseite weiter.

**Standard-Admin-Konto:**
*   **Benutzername:** `admin`
*   **Passwort:** `admin`

## 📂 Projektstruktur (Deutsch)

| Datei/Ordner | Zweck |
| :--- | :--- |
| `index.php` | Hauptansicht der Single Page Application (SPA) und Client-seitige Logik. |
| `api.php` | REST API-Gateway für alle Backend-Aktionen (verarbeitet die gesamte CRUD/Admin-Logik). |
| `bootstrap.php` | Kernkonfiguration, Hilfsfunktionen und **Multi-DB-Verbindungslogik**. |
| `database/` | **Speicher für alle Benutzerdatendateien.** |
| ├── `database.sqlite` | Haupt-**Admin-/System**-Datenbank (speichert Admin-Daten, Anmeldeversuche). |
| └── `[user].sqlite` | **Isolierte persönliche Datenbank für jeden normalen Benutzer.** |
| `users.php` | Dateibasierter Speicher für Benutzerkonten, Passwörter, 2FA-Geheimnisse und Sperrstatus. |
| `app_config.json` | Speichert globale Anwendungseinstellungen (z. B. `ALLOW_REGISTRATION`). |
| `setup_2fa.php` | 2FA-Verwaltungsseite. |
| `src/` | CSS-, JS-Bibliotheken, Schriftarten und andere Assets. |

## 📜 Lizenz (Deutsch)

Dieses Projekt ist unter der [MIT-Lizenz](LICENSE) lizenziert.

---

## 🚀 简介 (中文)

**Nexus Drive** 现已成为一个高性能、**多租户**（多用户）且极其轻量级的自托管云存储解决方案。它采用简约技术栈（PHP 和 SQLite）构建，为每个用户提供隔离的存储空间，并提供流畅的实时体验，而没有笨重框架的复杂性。

对于要求对其数据拥有**完全控制、隐私和用户管理**的小型团队、家庭或高级用户来说，它是理想的平台，所有这些都包含在一个简单、高度便携且零配置的架构中。

## ✨ 为什么选择 Nexus Drive？ (中文)

*   🔐 **多用户架构：** 每个用户都在自己隔离的 SQLite 数据库中操作，确保**严格的数据分离和隐私**。
*   🛡️ **管理员控制面板：** 专用的管理员账户获得了强大的工具，包括**用户管理（锁定/解锁/删除账户）**以及**冒充（切换到）其他用户账户**进行直接数据检查和支持的能力。
*   ⚡ **闪电般的 SPA：** 真正的**单页应用程序 (SPA)** 提供即时导航，媲美原生应用体验。
*   💎 **优雅现代的 UI：** 令人惊叹的、完全响应式的 UI，带有精美的**浅色和深色模式**以及微妙的**动画渐变背景**。
*   🚀 **高性能后端：** **Gzip 压缩**、用于大文件下载的**流式响应**，以及用于可靠处理千兆字节文件的强大**分块上传**。
*   📦 **绝对便携性：** 整个应用程序是自包含的。每个用户的数据都存储在一个简单的 `.sqlite` 文件（例如，`user.sqlite`）中，使得备份和迁移变得轻而易举。

## 📋 功能列表 (中文)

### 👑 新的管理员和多用户功能
*   ✅ **用户管理：** 查看所有用户、他们的存储使用情况和状态（锁定/活动）。
*   ✅ **冒充模式：** 管理员可以“切换到”任何用户的驱动器，以他们的名义直接管理文件。
*   ✅ **账户控制：** 锁定、解锁或永久删除用户账户及其关联的数据库文件。
*   ✅ **全局设置：** 直接从管理员的设置面板切换**公共注册**。

### 🛡️ 安全和认证
*   ✅ **双因素认证 (2FA)：** 使用基于 TOTP 的 2FA（Google 身份验证器、Authy）保护您的登录。
*   ✅ **暴力破解保护：** 在登录失败尝试多次后自动阻止 IP 地址。
*   ✅ **高级共享控制：**
    *   🔒 使用**密码**保护共享链接。
    *   ⏳ 设置链接**到期日期**。
    *   🔽 允许或**禁用下载**以进行只读共享。
*   ✅ **安全密码哈希：** 使用行业标准的 `bcrypt` 算法。

### 🗂️ 文件管理和实用程序
*   ✅ **强大的通用文件预览器：**
    *   📄 **文档：** PDF（原生）、`.docx` 和 `.xlsx` 直接在浏览器中渲染。
    *   💻 **代码编辑器：** 内置 ACE 编辑器，具有语法高亮功能。
    *   🖼️ **媒体：** 浏览器内播放图像、视频和音频。
*   ✅ **可恢复的分块上传：** 可靠地上传千兆字节文件。
*   ✅ 将多个项目下载为单个压缩的 **ZIP 档案**。
*   ✅ **智能搜索：** 即时**实时搜索**和专用的全页搜索。
*   ✅ **拖放**文件和文件夹操作。
*   ✅ 安全的**回收站**，具有恢复或永久删除的选项。

## 🛠️ 要求 (中文)

*   Web 服务器（带有 `mod_rewrite` 的 Apache，或 Nginx）。
*   PHP 8.0 或更高版本。
*   **必需的 PHP 扩展：**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ 快速设置 (中文)

1.  **下载：** 下载并解压最新版本。
2.  **上传：** 将解压后的文件放入 Web 服务器的公共目录中。
3.  **权限：** 授予 Web 服务器用户对项目根目录和 `/database` 文件夹的写入权限。
    *   *在 Linux 上：* `chmod -R 775 /path/to/nexus-drive` 和 `chown -R www-data:www-data /path/to/nexus-drive`。
4.  **访问：** 打开浏览器并导航到您的 URL。应用程序将自动创建主数据库（`database/database.sqlite`），然后将您重定向到登录页面。

**默认管理员账户：**
*   **用户名：** `admin`
*   **密码：** `admin`

## 📂 项目结构 (中文)

| 文件/文件夹 | 用途 |
| :--- | :--- |
| `index.php` | 单页应用程序 (SPA) 主视图和客户端逻辑。 |
| `api.php` | 所有后端操作的 REST API 网关（处理所有 CRUD/管理员逻辑）。 |
| `bootstrap.php` | 核心配置、辅助函数和**多数据库连接逻辑**。 |
| `database/` | **所有用户数据文件的存储。** |
| ├── `database.sqlite` | 主**管理员/系统**数据库（存储管理员数据、登录尝试）。 |
| └── `[user].sqlite` | **每个普通用户的隔离个人数据库。** |
| `users.php` | 基于文件的用户账户、密码、2FA 密钥和锁定状态存储。 |
| `app_config.json` | 存储全局应用程序设置（例如，`ALLOW_REGISTRATION`）。 |
| `setup_2fa.php` | 2FA 管理页面。 |
| `src/` | CSS、JS 库、字体和其他资产。 |

## 📜 许可证 (中文)

本项目根据 [MIT 许可证](LICENSE) 获得许可。

---

## 🚀 導入 (日本語)

**Nexus Drive** は、高性能、**マルチテナント**（マルチユーザー対応）、そして非常に軽量なセルフホスト型クラウドストレージソリューションです。最小限のスタック（PHP & SQLite）で構築されており、各ユーザーに隔離されたストレージを提供し、重厚なフレームワークの複雑さなしに、流動的でリアルタイムな体験を提供します。

これは、データに対する**完全な制御、プライバシー、およびユーザー管理**を求める小規模チーム、家族、またはパワーユーザーにとって理想的なプラットフォームであり、すべてがシンプルで移植性が高く、セットアップ不要のアーキテクチャ内に収まっています。

## ✨ Nexus Drive を選ぶ理由 (日本語)

*   🔐 **マルチユーザーアーキテクチャ:** 各ユーザーは独自の隔離された SQLite データベース内で操作し、**厳格なデータ分離とプライバシー**を保証します。
*   🛡️ **管理者コントロールパネル:** 専用の管理者アカウントは、**ユーザー管理（アカウントのロック/ロック解除/削除）** や、**他のユーザーのアカウントになりすまして（切り替えて）** 直接データ検査とサポートを行う機能を含む、強力なツールを獲得します。
*   ⚡ **超高速 SPA:** 真の**シングルページアプリケーション (SPA)** は、ネイティブアプリケーションのような感覚で瞬時のナビゲーションを提供します。
*   💎 **エレガントでモダンな UI:** 美しい**ライトモードとダークモード**、そして繊細な**アニメーショングラデーション背景**を備えた、見事で完全にレスポンシブな UI。
*   🚀 **ハイパフォーマンスなバックエンド:** **Gzip 圧縮**、大容量ファイルダウンロードのための**ストリーム応答**、そしてギガバイトサイズのファイルを確実に処理するための堅牢な**チャンクアップロード**。
*   📦 **絶対的な移植性:** アプリケーション全体が自己完結しています。各ユーザーのデータはシンプルな `.sqlite` ファイル（例：`user.sqlite`）に保存されるため、バックアップと移行は簡単です。

## 📋 機能リスト (日本語)

### 👑 新しい管理者およびマルチユーザー機能
*   ✅ **ユーザー管理:** すべてのユーザー、そのストレージ使用量、およびステータス（ロック/アクティブ）を表示します。
*   ✅ **なりすましモード:** 管理者は任意のユーザーのドライブに「切り替え」て、そのユーザーに代わってファイルを直接管理できます。
*   ✅ **アカウント制御:** ユーザーアカウントとその関連データベースファイルをロック、ロック解除、または永久に削除します。
*   ✅ **グローバル設定:** 管理者の設定パネルから**公開登録**を直接切り替えます。

### 🛡️ セキュリティと認証
*   ✅ **二要素認証 (2FA):** TOTPベースの 2FA（Google Authenticator、Authy）でログインを保護します。
*   ✅ **ブルートフォース攻撃からの保護:** ログイン試行の失敗が複数回続いた後、IPアドレスを自動的にブロックします。
*   ✅ **高度な共有制御:**
    *   🔒 **パスワード**を使用して共有リンクを保護します。
    *   ⏳ リンクの**有効期限**を設定します。
    *   🔽 読み取り専用共有のためにダウンロードを許可または**無効**にします。
*   ✅ **安全なパスワードハッシュ:** 業界標準の `bcrypt` アルゴリズムを使用します。

### 🗂️ ファイル管理とユーティリティ
*   ✅ **強力なユニバーサルファイルプレビューア:**
    *   📄 **ドキュメント:** PDF（ネイティブ）、`.docx`、および `.xlsx` はブラウザで直接レンダリングされます。
    *   💻 **コードエディタ:** 構文ハイライト機能を備えた組み込みの ACE エディタ。
    *   🖼️ **メディア:** 画像、動画、音声のブラウザ内再生。
*   ✅ **再開可能なチャンクアップロード:** ギガバイトサイズのファイルを確実にアップロードします。
*   ✅ 複数のアイテムを単一の圧縮された **ZIPアーカイブ**としてダウンロードします。
*   ✅ **インテリジェント検索:** 瞬時の**ライブ検索**と専用の全ページ検索。
*   ✅ **ドラッグ＆ドロップ**によるファイルおよびフォルダの操作。
*   ✅ 復元または永久削除のオプションを備えた安全な**ごみ箱**。

## 🛠️ 要件 (日本語)

*   Webサーバー（`mod_rewrite`を搭載したApache、またはNginx）。
*   PHP 8.0以降。
*   **必須の PHP 拡張機能:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ クイックセットアップ (日本語)

1.  **ダウンロード:** 最新リリースをダウンロードして解凍します。
2.  **アップロード:** 展開されたファイルをWebサーバーの公開ディレクトリに配置します。
3.  **権限:** プロジェクトのルートディレクトリと `/database` フォルダに対して、Webサーバーユーザーに書き込み権限を付与します。
    *   *Linuxの場合:* `chmod -R 775 /path/to/nexus-drive` および `chown -R www-data:www-data /path/to/nexus-drive`。
4.  **アクセス:** ブラウザを開き、URLにアクセスします。アプリケーションは自動的にメインデータベース（`database/database.sqlite`）を作成し、ログインページにリダイレクトします。

**デフォルト管理者アカウント:**
*   **ユーザー名:** `admin`
*   **パスワード:** `admin`

## 📂 プロジェクト構造 (日本語)

| ファイル/フォルダ | 目的 |
| :--- | :--- |
| `index.php` | シングルページアプリケーション (SPA) のメインビューとクライアント側のロジック。 |
| `api.php` | すべてのバックエンドアクションのための REST API ゲートウェイ（すべての CRUD/管理者ロジックを処理）。 |
| `bootstrap.php` | コア構成、ヘルパー関数、および**マルチ DB 接続ロジック**。 |
| `database/` | **すべてのユーザーデータファイルのストレージ。** |
| ├── `database.sqlite` | メインの**管理者/システム**データベース（管理者データ、ログイン試行を保存）。 |
| └── `[user].sqlite` | **各一般ユーザーのための隔離された個人データベース。** |
| `users.php` | ユーザーアカウント、パスワード、2FA シークレット、およびロック状態のためのファイルベースのストレージ。 |
| `app_config.json` | グローバルアプリケーション設定（例：`ALLOW_REGISTRATION`）を保存します。 |
| `setup_2fa.php` | 2FA 管理ページ。 |
| `src/` | CSS、JS ライブラリ、フォント、およびその他のアセット。 |

## 📜 ライセンス (日本語)

このプロジェクトは [MIT ライセンス](LICENSE) の下でライセンスされています。

---

# Nexus Drive: 멀티 사용자 셀프 호스팅 클라우드

## 🚀 소개 (한국어)

**Nexus Drive**는 이제 고성능, **멀티 테넌트**(멀티 사용자)이며 매우 가벼운 셀프 호스팅 클라우드 스토리지 솔루션입니다. 최소한의 스택(PHP & SQLite)으로 구축되어 각 사용자에게 격리된 스토리지를 제공하며, 무거운 프레임워크의 복잡성 없이 유동적인 실시간 경험을 제공합니다.

이는 단순하고 휴대성이 뛰어나며 제로 설정 아키텍처 내에서 데이터에 대한 **완전한 제어, 개인 정보 보호 및 사용자 관리**를 요구하는 소규모 팀, 가족 또는 고급 사용자에게 이상적인 플랫폼입니다.

## ✨ Nexus Drive를 선택해야 하는 이유 (한국어)

*   🔐 **멀티 사용자 아키텍처:** 각 사용자는 자체 격리된 SQLite 데이터베이스 내에서 작동하여 **엄격한 데이터 분리 및 개인 정보 보호**를 보장합니다.
*   🛡️ **관리자 제어판:** 전용 관리자 계정은 **사용자 관리(계정 잠금/잠금 해제/삭제)** 및 직접적인 데이터 검사 및 지원을 위해 **다른 사용자 계정으로 가장(전환)** 할 수 있는 기능을 포함하여 강력한 도구를 얻습니다.
*   ⚡ **놀랍도록 빠른 SPA:** 진정한 **단일 페이지 애플리케이션(SPA)**은 기본 애플리케이션 느낌에 필적하는 즉각적인 탐색 기능을 제공합니다.
*   💎 **우아하고 현대적인 UI:** 아름다운 **라이트 및 다크 모드**와 미묘한 **애니메이션 그라데이션 배경**이 있는 놀랍고 완벽하게 반응하는 UI.
*   🚀 **고성능 백엔드:** **Gzip 압축**, 대용량 파일 다운로드를 위한 **스트리밍 응답**, 그리고 기가바이트 크기의 파일을 안정적으로 처리하기 위한 강력한 **청크 업로드** 기능.
*   📦 **절대적인 휴대성:** 전체 애플리케이션이 자체 포함되어 있습니다. 각 사용자의 데이터는 간단한 `.sqlite` 파일(예: `user.sqlite`)에 저장되므로 백업 및 마이그레이션이 간단합니다.

## 📋 기능 목록 (한국어)

### 👑 새로운 관리자 및 멀티 사용자 기능
*   ✅ **사용자 관리:** 모든 사용자, 해당 스토리지 사용량 및 상태(잠금/활성)를 봅니다.
*   ✅ **가장 모드:** 관리자는 파일 관리를 위해 모든 사용자의 드라이브로 "전환"하여 해당 사용자를 대신하여 직접 파일을 관리할 수 있습니다.
*   ✅ **계정 제어:** 사용자 계정과 관련 데이터베이스 파일을 잠그거나, 잠금 해제하거나, 영구적으로 삭제합니다.
*   ✅ **전역 설정:** 관리자 설정 패널에서 **공개 등록**을 직접 토글합니다.

### 🛡️ 보안 및 인증
*   ✅ **2단계 인증(2FA):** TOTP 기반 2FA(Google Authenticator, Authy)로 로그인을 보호합니다.
*   ✅ **무차별 대입 공격 방지:** 로그인 실패 시도 후 IP 주소를 자동으로 차단합니다.
*   ✅ **고급 공유 제어:**
    *   🔒 **암호**로 공유 링크를 보호합니다.
    *   ⏳ 링크 **만료 날짜**를 설정합니다.
    *   🔽 보기 전용 공유를 위해 다운로드를 허용하거나 **비활성화**합니다.
*   ✅ **보안 암호 해싱:** 업계 표준 `bcrypt` 알고리즘을 사용합니다.

### 🗂️ 파일 관리 및 유틸리티
*   ✅ **강력한 범용 파일 미리보기:**
    *   📄 **문서:** PDF(네이티브), `.docx` 및 `.xlsx`는 브라우저에서 직접 렌더링됩니다.
    *   💻 **코드 편집기:** 구문 강조 표시 기능이 내장된 ACE 편집기.
    *   🖼️ **미디어:** 이미지, 비디오 및 오디오의 브라우저 내 재생.
*   ✅ **재개 가능한 청크 업로드:** 기가바이트 크기의 파일을 안정적으로 업로드합니다.
*   ✅ 여러 항목을 단일 압축된 **ZIP 아카이브**로 다운로드합니다.
*   ✅ **지능형 검색:** 즉각적인 **라이브 검색** 및 전용 전체 페이지 검색.
*   ✅ **드래그 앤 드롭** 파일 및 폴더 조작.
*   ✅ 복원 또는 영구 삭제 옵션이 있는 보안 **휴지통**.

## 🛠️ 요구 사항 (한국어)

*   웹 서버(`mod_rewrite`가 있는 Apache 또는 Nginx).
*   PHP 8.0 이상.
*   **필수 PHP 확장:**
    *   `pdo_sqlite`
    *   `zip`

## ⚙️ 빠른 설정 (한국어)

1.  **다운로드:** 최신 릴리스를 다운로드하고 압축을 풉니다.
2.  **업로드:** 압축을 푼 파일을 웹 서버의 공용 디렉토리에 배치합니다.
3.  **권한:** 프로젝트의 루트 디렉토리와 `/database` 폴더에 웹 서버 사용자에게 쓰기 권한을 부여합니다.
    *   *Linux의 경우:* `chmod -R 775 /path/to/nexus-drive` 및 `chown -R www-data:www-data /path/to/nexus-drive`.
4.  **접근:** 브라우저를 열고 URL로 이동합니다. 애플리케이션은 기본 데이터베이스(`database/database.sqlite`)를 자동 생성한 다음 로그인 페이지로 리디렉션합니다.

**기본 관리자 계정:**
*   **사용자 이름:** `admin`
*   **암호:** `admin`

## 📂 프로젝트 구조 (한국어)

| 파일/폴더 | 목적 |
| :--- | :--- |
| `index.php` | 단일 페이지 애플리케이션(SPA)의 기본 보기 및 클라이언트 측 논리. |
| `api.php` | 모든 백엔드 작업의 REST API 게이트웨이(모든 CRUD/관리자 논리 처리). |
| `bootstrap.php` | 핵심 구성, 도우미 함수 및 **멀티 DB 연결 논리**. |
| `database/` | **모든 사용자 데이터 파일의 저장소입니다.** |
| ├── `database.sqlite` | 기본 **관리자/시스템** 데이터베이스(관리자 데이터, 로그인 시도 저장). |
| └── `[user].sqlite` | **각 일반 사용자를 위한 격리된 개인 데이터베이스입니다.** |
| `users.php` | 사용자 계정, 암호, 2FA 비밀 및 잠금 상태를 위한 파일 기반 저장소입니다. |
| `app_config.json` | 전역 애플리케이션 설정(예: `ALLOW_REGISTRATION`)을 저장합니다. |
| `setup_2fa.php` | 2FA 관리 페이지. |
| `src/` | CSS, JS 라이브러리, 글꼴 및 기타 자산. |

## 📜 라이선스 (한국어)

이 프로젝트는 [MIT 라이선스](LICENSE)에 따라 라이선스가 부여됩니다.

---
