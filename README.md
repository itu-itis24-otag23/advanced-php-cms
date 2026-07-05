# 🚀 Advanced PHP & MySQL CMS Project

This project is a feature-rich, fully custom **Content Management System (CMS)** developed from scratch using native (core) PHP and MySQL, without relying on any external frameworks.

While the administrative dashboard (Admin Panel) allows complete dynamic management of posts, categories, tags, and comment moderation, the client-facing side (Frontend) provides a clean, SEO-friendly, and filterable blog experience for visitors.

---

## 🛠️ Core Technical Features

* **Security Layer (PDO):** All database interactions are built using `PDO (PHP Data Objects)` combined with `Prepared Statements`, completely eliminating the risk of **SQL Injection** attacks.
* **Session & Authentication Security:** Unauthorized access to the admin dashboard is restricted via a secure session-based firewall. User passwords are encrypted using the robust `password_hash()` (bcrypt) algorithm.
* **Relational Database Architecture:** * A **One-to-Many** relationship is established between Categories and Posts.
  * A **Many-to-Many** relationship between Tags and Posts is successfully managed using a junction/pivot table (`post_tags`).
* **Secure File Uploads:** Uploaded blog cover images undergo strict extension validation, size limitations (max 2MB), and are renamed using `uniqid()` to prevent server-side naming conflicts and security breaches.
* **Data Integrity (Transactions):** Multi-table insertion workflows (e.g., saving a post and its associated tags simultaneously) are wrapped within `PDO::beginTransaction()`, `commit()`, and `rollBack()` blocks to ensure atomicity.
* **SEO-Friendly Clean URLs (Slug Structure):** Implemented a custom `createSlug()` utility function that automatically converts dynamic string inputs into URL-safe formats.
* **Dynamic Content Filtering:** Visitors can seamlessly filter blog posts by clicking on specific categories in the navigation bar or using the interactive Tag Cloud widget in the sidebar.
* **Comment Moderation Queue:** User comments are not published instantly; they are stored with a default `pending` status and only appear on the frontend once explicitly approved via the admin panel.

---

## 📂 Directory Structure

```text
advanced-cms/
│
├── config/
│   ├── db.php.example  # Database configuration template
│   └── db.php          # Active database connection (Excluded from Git)
│
├── admin/              # Administrative Dashboard Modules
│   ├── index.php       # Panel Homepage & System Statistics
│   ├── login.php       # Secure Admin Authentication Form
│   ├── logout.php      # Secure Session Destruction
│   ├── posts.php       # Post Management (Listing & Deletion)
│   ├── add-post.php    # Rich Post Creation with Image Upload & Multi-Tag Selection
│   ├── categories.php  # Category Management (CRUD)
│   └── tags.php        # Tag Management (CRUD)
│
├── includes/           # Reusable Theme Components
│   ├── header.php      # Global Navigation, Database Init & Core Layout Styles
│   └── footer.php      # Sidebar Widgets (About, Tag Cloud) & Global Footer
│
├── uploads/            # Target Directory for Uploaded Blog Images
├── index.php           # Visitor Homepage & Dynamic Filtering Engine
└── post.php            # Detailed Single Post View & Comment Submission Form
