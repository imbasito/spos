# Mithai POS - Professional Sweet Shop Management System

![Mithai POS Logo](public/sinyx-logo-full.png)

A robust, high-performance desktop Point of Sale (POS) system designed specifically for sweet shops. Built with **Laravel 10**, **React**, and **Electron**, it combines the power of modern web technologies with the reliability of a native desktop application.

## 🚀 Key Features

- **Blazing Fast POS Interface**: Optimized React frontend for quick billing and searching.
- **Offline First**: Internal MySQL and PHP servers ensure 100% uptime even without an internet connection.
- **Inventory Management**: Real-time stock tracking with automated verification during checkout.
- **Dynamic Branding**: Easily customizable logo and branding via settings.
- **Smart Printing**: Professional receipt generation with barcode support.
- **System Resilience**: Robust error handling and automatic database connectivity management.

## 🛠️ Technical Stack

- **Frontend**: React, Vite, TailwindCSS (for system UI).
- **Backend**: Laravel 10 (PHP 8.2+).
- **Database**: MySQL 8.0.
- **Desktop Wrapper**: Electron.
- **Installer**: NSIS (via electron-builder).

## 📦 Installation for Clients

1. Download the latest `Mithai-POS-Setup.exe` from the [Releases](https://github.com/imbasito/mithai-pos/releases) page.
2. Run the installer and follow the on-screen instructions.
3. The application will request **Administrator Privileges** to manage local services.
4. Once installed, launch "Mithai POS" from your desktop.

## 💻 Developer Setup

### Prerequisites
- [Node.js](https://nodejs.org/)
- [PHP 8.2+](https://www.php.net/)

### Initial Configuration
1. Clone the repository:
   ```bash
   git clone https://github.com/imbasito/mithai-pos.git
   cd mithai-pos
   ```
2. Setup environment variables:
   ```bash
   cp .env.example .env
   # Update your DB_PORT and APP_KEY
   ```
3. Install dependencies:
   ```bash
   npm install
   composer install
   ```

### Running in Development
```bash
npm run dev    # Start Vite
npm run start  # Launch Electron
```

## 📜 Credits & Licensing

- **Original Application**: Developed by [Original Developer qtecsolutions](https://github.com/qtecsolution/qpos.git) - *This project is a refined and enhanced version of the original QPOS system.*
- **Refinement & Enhancements**: Maintained by Abdul Basit Khan (SINYX).
- **License**: UNLICENSED (All Rights Reserved).

---
*Built with ❤️ for professional retail environments.*
