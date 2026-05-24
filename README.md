# Aplikasi Penandatanganan Digital

A comprehensive web-based platform for managing, reviewing, and digitally signing institutional documents. This application streamlines administrative workflows by digitalizing the signing process for different academic roles.

## 🚀 Features

- **Document Workflow Management:** Automated routing of documents between departments.
- **Digital Signatures:** Integrated PDF signing and stamping capabilities.
- **Role-Based Access Control:** Secure workflows tailored for:
  - **Tata Usaha (TU):** Responsible for document uploads and finalization.
  - **Kaprodi:** Review and paraf (initial) functionality.
  - **Kajur/Sekjur:** Final approval and signing.
- **Secure Authentication:** Support for Google OAuth login.
- **Real-time Notifications:** Automated workflow tracking via email.

## 🛠 Tech Stack

- **Framework:** [Laravel 12](https://laravel.com/)
- **PDF Processing:** `iLovePDF`, `FPDF`, `FPDI`
- **Document Handling:** `PHPWord`
- **Authentication:** `Laravel Sanctum`, `Laravel Socialite`
- **Database:** MySQL/SQLite (supports schema migrations)

## 📦 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone <your-repo-url>
   cd <your-project-folder>
