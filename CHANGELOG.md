# Changelog

All notable changes to the SPOS Professional Point of Sale System are documented in this file.

---

## [1.1.0] - 2026-02-05

### Pakistan Market Localization & Supplier Ledger Integration

This release introduces comprehensive support for the Pakistani market, including localized data validation, bilingual product naming, and a debt management system for supplier transactions.

### Added
- **Supplier Ledger (Khata System)**: Implementation of a comprehensive debt tracking system for product purchases.
- **Payment Tracking**: Added support for recording partial and full payments during the purchase process.
- **Payment Status Management**: Automatic classification of purchase orders as Paid, Partial, or Unpaid.
- **Supplier Ledger Report**: A new reporting interface providing real-time oversight of all outstanding liabilities across suppliers.
- **CNIC Validation**: Added National Identity Card (CNIC) field for customers with regional format validation (xxxxx-xxxxxxx-x) to ensure FBR compliance.
- **Credit Limit Control**: Implemented configurable credit limits for customers to manage outstanding balances.
- **Bilingual Product Support**: Added Urdu Name field for products with RTL (Right-to-Left) language support for localized receipts and invoices.
- **Compliance Fields**: Added HS Code (Harmonized System) field for products to support import and customs requirements.
- **Automated Schema Migration**: Integrated a force-migration engine that automatically updates the database schema on application startup.

### Changed
- **Regional Configuration**: Updated the default application timezone to Asia/Karachi.
- **Validation Protocols**: Standardized mobile number validation to follow the Pakistani format (03XXXXXXXXX).
- **Purchase Interface**: Updated the React-based purchase module to include paid amount inputs and real-time balance calculations.
- **Build Sanitization**: Enhanced build scripts to automatically purge developer logs, active sessions, and temporary caches prior to distribution.

### Technical
- **Schema Management**: Implemented automatic database migrations in the application core to eliminate manual client-side intervention during updates.
- **Data Integrity**: Developed and integrated automated feature tests for customer validation and supplier ledger calculations.
- **Deployment Safety**: Automated the reset of activation and license configurations for production builds.

---

### Installation and Updating
- **New Installations**: Execute SPOS-Setup-1.1.0.exe and follow the installation wizard.
- **Updates**: Install version 1.1.0 directly over the previous installation. All existing data is preserved, and the database schema will be updated automatically on the first launch.

**Copyright © 2026 SINYX. All Rights Reserved.**
