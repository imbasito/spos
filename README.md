<div align="center">

# SPOS - Professional Point of Sale System

![SPOS Logo](public/assets/images/branding/sinyx-slogan.png)

![Version](https://img.shields.io/badge/version-1.0.5-blue.svg)
![License](https://img.shields.io/badge/license-All%20Rights%20Reserved-red.svg)
![Platform](https://img.shields.io/badge/platform-Windows-lightgrey.svg)
![Tech](https://img.shields.io/badge/Laravel-10.x-FF2D20?logo=laravel)
![Tech](https://img.shields.io/badge/React-18.3-61DAFB?logo=react)
![Tech](https://img.shields.io/badge/Electron-37.3-47848F?logo=electron)

**A robust, high-performance desktop Point of Sale system designed for sweet shops and retail environments. Built with modern web technologies, it delivers the power and flexibility of a web application with the reliability of native desktop software.**

[Features](#-features) • [Installation](#-installation) • [Tech Stack](#-tech-stack) • [Documentation](#-documentation) • [Support](#-support)

</div>

---

## ✨ Features

### 🛒 Point of Sale Operations
- **Lightning-fast checkout interface** with barcode scanning support
- **Multi-item cart management** with quantity adjustments and item removal
- **Flexible payment processing** with cash, card, and mixed payments
- **Real-time discount application** (percentage or fixed amount)
- **Professional receipt printing** with customizable templates
- **Hold and retrieve sales** for interrupted transactions
- **Stock verification during checkout** to prevent overselling

### 📦 Inventory Management
- **Complete product CRUD operations** with image upload
- **Category and unit management** for organized inventory
- **Barcode generation and printing** for products
- **Low stock alerts** with configurable thresholds
- **Bulk product import** via CSV for quick setup
- **Product search with filters** (category, stock status, barcode)
- **Purchase/restocking module** with supplier tracking

### 📊 Sales & Analytics
- **Comprehensive sales reports** with date range filtering
- **Product-wise performance tracking** with quantity and revenue
- **Customer purchase history** and loyalty tracking
- **Return and refund management** with detailed logs
- **Profit margin analysis** based on cost and selling price
- **Daily, weekly, and monthly summaries** for business insights

### 👥 Customer Management
- **Customer database** with contact information and credit tracking
- **Purchase history tracking** for personalized service
- **Customer-specific discounts** and loyalty rewards
- **Credit sales management** with outstanding balance tracking
- **Customer search and filtering** for quick access

### 🔄 Returns & Refunds
- **Easy return processing** with original sale lookup
- **Full or partial refunds** with stock restoration
- **Return reason tracking** for quality control
- **Refund history** with comprehensive logs

### 🛡️ Security & User Management
- **Role-based access control** (Admin, Cashier, Sales Associate)
- **Granular permission system** for feature-level access
- **User activity logging** for audit trails
- **Secure authentication** with encrypted passwords
- **Session management** with auto-logout on inactivity

### 💾 Backup & Data Management
- **Automatic database backups** on application exit
- **Manual backup creation** with custom naming
- **One-click restore functionality** from backup files
- **Database optimization** to maintain performance
- **Export capabilities** for sales and product data

### 🎨 Customization
- **Dynamic branding** with logo upload and system-wide application
- **Receipt template customization** with business details
- **Configurable tax rates** and currency settings
- **Theme customization** (planned for v1.1.0)

---

## 📋 System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **OS** | Windows 10 (64-bit) | Windows 10/11 (64-bit) |
| **Processor** | Intel Core i3 or equivalent | Intel Core i5 or better |
| **RAM** | 4 GB | 8 GB or more |
| **Storage** | 500 MB free space | 1 GB free space |
| **Display** | 1366x768 | 1920x1080 or higher |
| **Network** | Not required (offline-first) | Optional for updates |

---

## 📦 Installation

### For End Users (Recommended)

1. **Download the Installer**
   - Visit the [Releases](https://github.com/imbasito/spos/releases) page
   - Download the latest `SPOS-Setup-1.0.5.exe`

2. **Run the Installer**
   - Double-click the downloaded file
   - Follow the on-screen installation wizard
   - Accept the license agreement
   - Choose installation directory (default: `C:\Program Files\SPOS`)

3. **Launch SPOS**
   - Use the desktop shortcut or Start Menu entry
   - First launch will initialize the database (takes 1-2 minutes)
   - Login with default credentials (see [Default Users](#-default-users))

4. **Configure Your Business**
   - Navigate to Settings → System Settings
   - Upload your logo and set business details
   - Configure receipt printer (if applicable)

### For Developers

#### Prerequisites
- **Node.js** 18.x or higher ([Download](https://nodejs.org/))
- **PHP** 8.2 or higher ([Download](https://www.php.net/))
- **Composer** 2.x ([Download](https://getcomposer.org/))
- **Git** ([Download](https://git-scm.com/))

#### Setup Steps

1. **Clone the Repository**
   ```powershell
   git clone https://github.com/imbasito/spos.git
   cd spos
   ```

2. **Configure Environment**
   ```powershell
   # Copy environment template
   Copy-Item .env.example .env
   
   # Edit .env file:
   # - Set DB_PORT (default: 3306)
   # - Set APP_KEY (generate with: php artisan key:generate)
   # - Configure other Laravel settings as needed
   ```

3. **Install Dependencies**
   ```powershell
   # Install PHP dependencies
   composer install
   
   # Install Node.js dependencies
   npm install
   ```

4. **Initialize Database**
   ```powershell
   # Run migrations and seeders
   php artisan migrate --seed
   ```

5. **Run Development Mode**
   ```powershell
   # Terminal 1: Start Vite dev server
   npm run dev
   
   # Terminal 2: Launch Electron app
   npm run start
   ```

---

## 🛠️ Tech Stack

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Frontend** | React | 18.3.1 | UI components and state management |
| **Build Tool** | Vite | 4.5.14 | Fast development server and bundling |
| **Backend** | Laravel | 10.x | API, business logic, and database ORM |
| **Runtime** | PHP | 8.2+ | Server-side scripting |
| **Database** | MySQL | 8.0 | Data persistence |
| **Desktop** | Electron | 37.3.1 | Native desktop application wrapper |
| **Packaging** | electron-builder | 24.13.3 | Windows installer creation |
| **Auto-Update** | electron-updater | 6.7.3 | Automatic application updates |
| **UI Styling** | TailwindCSS | 3.x | Utility-first CSS framework |
| **HTTP Client** | Axios | 1.x | API communication |
| **Routing** | React Router | 6.x | Client-side navigation |

---

## 📁 Project Structure

```
SPOS/
├── .archive/              # Archived documentation and samples
├── .build-scripts/        # Build automation scripts
├── .dev-tools/            # Development utilities and debug scripts
├── app/                   # Laravel application code
│   ├── Http/Controllers/  # API controllers
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic services
│   └── ...
├── config/                # Laravel configuration files
├── database/
│   ├── migrations/        # Database schema migrations
│   └── seeders/           # Database seeders
├── dist_production/       # Built application (after build)
│   └── win-unpacked/      # Unpacked Windows app
├── public/                # Public assets
├── resources/
│   └── js/
│       └── components/    # React components
├── routes/                # Laravel API routes
├── storage/               # Application storage
│   └── app/backups/       # Database backups
├── vendor/                # PHP dependencies
├── node_modules/          # Node.js dependencies
├── mysql/                 # Bundled MySQL (dev)
├── php/                   # Bundled PHP (dev)
├── nodejs/                # Bundled Node.js (dev)
├── main.cjs               # Electron main process
├── preload.cjs            # Electron preload script
├── package.json           # Node.js configuration
├── composer.json          # PHP dependencies
├── vite.config.js         # Vite configuration
└── SPOS_USER_MANUAL.md    # Comprehensive user documentation
```

---

## 🔧 Build Commands

```powershell
# Development
npm run dev              # Start Vite dev server
npm run start            # Launch Electron in dev mode

# Production Build
npm run dist             # Build unpacked app (win-unpacked/)
npm run dist:installer   # Build NSIS installer (SPOS-Setup.exe)
npm run pack             # Create app package without installer

# Utilities
npm run build            # Build frontend only (Vite)
php artisan migrate      # Run database migrations
php artisan db:seed      # Seed database with default data
php artisan optimize     # Optimize Laravel caches
```

---

## 🔄 Auto-Update System

SPOS includes automatic update functionality via GitHub Releases.

### For Administrators Publishing Updates:

1. **Bump Version**
   - Update version in `package.json`
   - Update `VERSION.md` with changelog

2. **Build Installer**
   ```powershell
   npm run dist:installer
   ```

3. **Create GitHub Release**
   - Go to repository → Releases → Create new release
   - Tag: `v1.1.0` (match package.json version)
   - Upload `SPOS-Setup-1.1.0.exe` from `dist_production/`
   - Publish release

4. **Automatic Distribution**
   - Installed SPOS instances will check for updates on launch
   - Users will be notified of available updates
   - Updates download in background and install on restart

### Update Configuration

Updates are configured in `package.json`:
```json
"build": {
  "publish": {
    "provider": "github",
    "owner": "imbasito",
    "repo": "spos"
  }
}
```

For private repositories, set `GH_TOKEN` environment variable before building.

---

## 🔒 Security Features

- ✅ Role-based access control (RBAC)
- ✅ Encrypted password storage (bcrypt)
- ✅ Session-based authentication
- ✅ Permission-level feature gating
- ✅ Activity logging for audit trails
- ✅ Automatic session timeout
- ✅ Database backup encryption (planned for v1.1.0)

---

## 📖 Documentation

- **[SPOS_USER_MANUAL.md](SPOS_USER_MANUAL.md)** - Comprehensive user guide covering all features, operations, and troubleshooting
- **[APPLICATION_ASSESSMENT_REPORT.md](APPLICATION_ASSESSMENT_REPORT.md)** - Technical assessment and architecture details
- **[MIGRATION_TO_DOTNET.md](MIGRATION_TO_DOTNET.md)** - Future migration roadmap
- **[INSTALLER_BUILD_GUIDE.md](INSTALLER_BUILD_GUIDE.md)** - Detailed instructions for building installers

---

## ⚠️ Known Limitations

- Windows-only support (macOS/Linux planned for v2.0)
- Single-user concurrent access (network version planned)
- English-only interface (multi-language support in v1.2.0)
- Thermal printer support limited to ESC/POS compatible printers

---

## 🗺️ Roadmap

### Version 1.1.0 (Q2 2025)
- [ ] Theme customization (dark mode, custom colors)
- [ ] Enhanced reporting with charts and graphs
- [ ] Email receipt delivery
- [ ] Barcode scanning via webcam
- [ ] Database backup encryption

### Version 1.2.0 (Q3 2025)
- [ ] Multi-language support (Urdu, Arabic, Hindi)
- [ ] Network mode for multi-terminal setup
- [ ] Cloud sync for backup and reporting
- [ ] Mobile app for inventory management
- [ ] Advanced analytics and forecasting

---

## 💬 Support

- **Issues**: [GitHub Issues](https://github.com/imbasito/spos/issues)
- **Developer**: Abdul Basit Khan (SINYX)
- **Email**: Contact via GitHub profile
- **Documentation**: See [SPOS_USER_MANUAL.md](SPOS_USER_MANUAL.md)

---

## 📜 Credits & License

### Original Development
- **Original Project**: [QPOS by qtecsolutions](https://github.com/qtecsolution/qpos.git)
- **Original Developer**: qtecsolutions

### Refinement & Enhancements
- **Maintained by**: Abdul Basit Khan (SINYX)
- **Enhancements**: Bug fixes, performance optimization, feature additions, professional packaging

### License
**UNLICENSED** - All Rights Reserved

This software is proprietary and confidential. Unauthorized copying, modification, distribution, or use is strictly prohibited without explicit permission from the copyright holder.

### Technologies
- Laravel 10 (MIT License)
- React 18 (MIT License)
- Electron 37 (MIT License)
- MySQL 8.0 (GPL License)
- electron-builder (MIT License)

---

<div align="center">

**Built with ❤️ for professional retail environments**

⭐ Star this repo if you find it useful! ⭐

</div>
