<div align="center">

<img src="./public/assets/images/branding/sinyx-slogan.png" title="" alt="SPOS Logo" data-align="center">

## SINYX Point of Sale System

</div>

**Version:** 1.0.5  
**Copyright:** © 2026 SINYX. All Rights Reserved.  
**Support:** contact@sinyxcode.com  

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [System Requirements](#2-system-requirements)
3. [Installation Guide](#3-installation-guide)
4. [First Time Setup](#4-first-time-setup)
5. [User Roles & Permissions](#5-user-roles--permissions)
6. [Daily Operations](#6-daily-operations)
7. [Product Management](#7-product-management)
8. [Sales & Point of Sale](#8-sales--point-of-sale)
9. [Purchase & Restocking](#9-purchase--restocking)
10. [Customer Management](#10-customer-management)
11. [Returns & Refunds](#11-returns--refunds)
12. [Reports & Analytics](#12-reports--analytics)
13. [Backup & Restore](#13-backup--restore)
14. [Troubleshooting](#14-troubleshooting)
15. [Best Practices](#15-best-practices)

---

## 1. Introduction

SPOS (Professional Point of Sale System) is a comprehensive desktop application designed for retail businesses, especially sweet shops and small to medium enterprises. It provides complete inventory management, sales tracking, customer management, and financial reporting in a user-friendly interface.

### Key Features

- **Multi-User System** with role-based access control (Admin, Cashier, Sales Associate)
- **Real-Time Inventory Management** with automatic stock updates
- **Point of Sale** with barcode scanning support
- **Purchase Management** for restocking products
- **Customer Database** with transaction history
- **Returns & Refunds** processing
- **Backup & Restore** for data safety
- **Offline Operation** - no internet required
- **Receipt Printing** support
- **Comprehensive Reports** for business insights

---

## 2. System Requirements

### Minimum Requirements

- **Operating System:** Windows 10 or later (64-bit)
- **Processor:** Intel Core i3 or equivalent
- **RAM:** 4 GB
- **Storage:** 2 GB available space
- **Display:** 1280x800 resolution
- **Additional:** Visual C++ 2015-2022 Redistributable (included with installer)

### Recommended Requirements

- **Operating System:** Windows 11 (64-bit)
- **Processor:** Intel Core i5 or better
- **RAM:** 8 GB or more
- **Storage:** 5 GB available space (for database growth)
- **Display:** 1920x1080 resolution or higher
- **Peripherals:** Barcode scanner, receipt printer (optional but recommended)

### Network Requirements

- **Internet:** NOT required for daily operations
- **Local Network:** Optional for shared printer access

---

## 3. Installation Guide

### Step 1: Download Installer

1. Obtain the `SPOS Setup 1.0.5.exe` installer file
2. Verify file integrity (check file size: ~300-400 MB)

### Step 2: Run Installer

1. **Right-click** on `SPOS Setup 1.0.5.exe`
2. Select **"Run as Administrator"**
3. If Windows SmartScreen appears, click **"More info"** → **"Run anyway"**
4. Follow installation wizard:
   - Accept license agreement
   - Choose installation location (Default: `C:\Program Files\SPOS`)
   - Select **"Create Desktop Shortcut"**
   - Click **"Install"**

### Step 3: First Launch

1. After installation completes, click **"Finish"**
2. Launch SPOS from desktop shortcut or Start Menu
3. **Wait 30-60 seconds** on first launch while system initializes:
   - MySQL database installation
   - System tables creation
   - Default data seeding
4. Splash screen will show progress messages

### Step 4: Activation (If Required)

If activation screen appears:

1. Enter provided **License Key**
2. Enter **Licensed To** name (your business name)
3. Click **"Activate"**
4. System will redirect to login screen

---

## 4. First Time Setup

### Default Login Credentials

After installation, three user accounts are automatically created:

| Role                | Email            | Password   |
| ------------------- | ---------------- | ---------- |
| **Administrator**   | admin@spos.com   | admin123   |
| **Cashier**         | cashier@spos.com | cashier123 |
| **Sales Associate** | sales@spos.com   | sales123   |

⚠️ **IMPORTANT:** Change all default passwords immediately after first login!

### Initial Configuration Steps

#### 4.1 Change Admin Password

1. Login as `admin@spos.com`
2. Navigate to **Settings** → **Profile**
3. Click **"Change Password"**
4. Enter current password: `admin123`
5. Enter new strong password
6. Click **"Update"**

#### 4.2 Configure Business Information

1. Go to **Settings** → **System Settings**
2. Update:
   - **Business Name**
   - **Address**
   - **Phone Number**
   - **Email**
   - **Tax Registration Number** (if applicable)
   - **Currency** (default: USD)
3. Click **"Save Changes"**

#### 4.3 Setup Units of Measurement

Pre-configured units:

- Piece (pc)
- Kilogram (kg)
- Gram (g)
- Liter (l)
- Milliliter (ml)
- Box
- Dozen

To add custom units:

1. Go to **Products** → **Units**
2. Click **"Add New Unit"**
3. Enter unit name and abbreviation
4. Click **"Save"**

#### 4.4 Create Product Categories

1. Navigate to **Products** → **Categories**
2. Click **"Add Category"**
3. Enter category details:
   - Category Name
   - Description (optional)
4. Click **"Save"**

Example categories for sweet shop:

- Sweets
- Bakery Items
- Beverages
- Dry Fruits
- Gift Packs

#### 4.5 Add Brands (Optional)

1. Go to **Products** → **Brands**
2. Click **"Add Brand"**
3. Enter brand name
4. Click **"Save"**

#### 4.6 Add Suppliers

1. Navigate to **Purchase** → **Suppliers**
2. Click **"Add Supplier"**
3. Enter supplier details:
   - Name
   - Contact Person
   - Phone
   - Email
   - Address
4. Click **"Save"**

Note: "Own Supplier" is pre-created for internal production

---

## 5. User Roles & Permissions

### Administrator

**Full system access** with all permissions including:

- User management (create, edit, delete users)
- System settings configuration
- Complete product management
- Financial reports access
- Database backup and restore
- All POS operations
- Purchase management
- Returns and refunds

**Default Access:** All modules

### Cashier

**POS-focused operations** including:

- Point of Sale transactions
- Product search and sales
- Customer management (view and create)
- Receipt printing
- View product inventory
- Process returns and refunds (if authorized)
- View daily sales reports

**Restricted Access:**

- Cannot access system settings
- Cannot manage users
- Cannot delete products
- Cannot access financial reports
- Cannot perform database operations

### Sales Associate

**Limited sales support** including:

- View dashboard
- View product list and prices
- View customer information
- Create sales orders (with approval)
- View basic reports

**Restricted Access:**

- Cannot access POS directly
- Cannot modify prices
- Cannot process refunds
- Cannot access purchase management
- Cannot access system settings

### Creating New Users

1. Login as **Administrator**
2. Go to **Settings** → **Users**
3. Click **"Add New User"**
4. Fill in user details:
   - Full Name
   - Email (will be username)
   - Password
   - Select Role (Admin/Cashier/Sales Associate)
   - Phone Number (optional)
5. Click **"Create User"**

---

## 6. Daily Operations

### 6.1 Opening the System

1. Launch SPOS application
2. Login with your credentials
3. Dashboard will display:
   - Today's sales summary
   - Low stock alerts
   - Recent transactions
   - Quick action buttons

### 6.2 Starting a Sale (POS)

1. Navigate to **POS** tab (or click from dashboard)
2. Select/scan products to add to cart:
   - **Barcode Scanner:** Scan product barcode
   - **Manual Search:** Type product name or barcode in search box
   - **Browse Products:** Click on product from displayed list
3. Verify cart items and quantities
4. Click **"Checkout"** when ready

### 6.3 Processing Payment

1. Select customer (or use "Walking Customer" for anonymous sales)
2. Review order total
3. Enter **Amount Received** from customer
4. System automatically calculates change
5. Select payment method:
   - Cash
   - Card
   - Mixed Payment
6. Click **"Complete Sale"**
7. Print receipt (if printer configured)

### 6.4 Handling Cart Operations

**Adjust Quantity:**

- Click quantity field and enter new value
- Or use **+** / **-** buttons

**Override Price:**

- Click on price field
- Enter custom price (Admin permission required)

**Remove Item:**

- Click **trash icon** next to product

**Clear Entire Cart:**

- Click **"Clear Cart"** button
- Confirm action

**Save Cart for Later:**

- System auto-saves cart every 30 seconds
- If interrupted, cart will restore on next login

### 6.5 End of Day Procedures

1. **Daily Closing Report:**
   
   - Go to **Reports** → **Daily Closing**
   - Review total sales, cash collected, transactions
   - Print/Export report

2. **Cash Reconciliation:**
   
   - Count physical cash in register
   - Compare with system total
   - Note any discrepancies

3. **Stock Check:**
   
   - Review low stock alerts
   - Plan next day's restocking

4. **Database Backup:**
   
   - Go to **Settings** → **Backup**
   - Click **"Create Backup"**
   - Save backup file to external drive/cloud

---

## 7. Product Management

### 7.1 Adding New Products

1. Go to **Products** → **All Products**
2. Click **"Add New Product"**
3. Fill in product details:

**Basic Information:**

- **Product Name*** (required)
- **SKU/Barcode*** (unique identifier)
- **Category*** 
- **Brand** (optional)
- **Unit*** (pc, kg, g, etc.)

**Pricing:**

- **Purchase Price*** (buying cost)
- **Selling Price*** (retail price)
- **Tax Rate** (percentage)
- **Discount** (optional)

**Inventory:**

- **Initial Stock*** (opening quantity)
- **Alert Quantity** (low stock threshold)

**Additional Details:**

- **Description**

- **Product Image** (optional)

- **Status** (Active/Inactive)
4. Click **"Save Product"**

### 7.2 Barcode Generation

System automatically generates barcodes:

- Uses SKU/Barcode field as base
- Generates scannable barcode format
- Can be printed on labels

**Custom Barcodes:**

1. Edit product
2. Enter your custom barcode in **SKU/Barcode** field
3. Save product

### 7.3 Bulk Product Import

1. Go to **Products** → **Import**
2. Download CSV template
3. Fill template with product data:
   - Name, SKU, Category, Price, Stock, etc.
4. Upload completed CSV file
5. Review import preview
6. Click **"Import Products"**

**CSV Format:**

```csv
name,sku,category,brand,unit,purchase_price,selling_price,tax,stock,alert_quantity
"Gulab Jamun",GJ001,Sweets,House Brand,kg,150,250,5,50,10
"Rasgulla",RS001,Sweets,House Brand,kg,120,200,5,30,10
```

### 7.4 Managing Stock Levels

**View Current Stock:**

- Navigate to **Products** → **All Products**
- Stock column shows current quantity
- Red highlight indicates low stock

**Adjust Stock Manually:**

1. Find product in list
2. Click **"Edit"** icon
3. Modify **Current Stock** field
4. Add note explaining adjustment
5. Save changes

**Stock History:**

- Click on product name
- View **"Stock History"** tab
- Shows all stock movements (sales, purchases, adjustments)

### 7.5 Product Categories & Organization

**Create Subcategories:**

1. Go to **Products** → **Categories**
2. Click **"Add Category"**
3. Select **Parent Category**
4. Enter subcategory name
5. Save

**Reorganize Categories:**

- Drag and drop categories to reorder
- Click **"Edit"** to modify category details
- Delete unused categories (if no products assigned)

---

## 8. Sales & Point of Sale

### 8.1 POS Interface Overview

**Main Sections:**

- **Product Search Bar** (top) - Search by name/barcode
- **Product Display Area** (left) - Browse available products
- **Shopping Cart** (right) - Current sale items
- **Customer Selection** (top right) - Choose customer
- **Total Display** (bottom right) - Shows subtotal, tax, discount, grand total
- **Action Buttons** (bottom) - Clear Cart, Hold, Checkout

### 8.2 Product Selection Methods

**Method 1: Barcode Scanner**

1. Focus on search bar
2. Scan product barcode
3. Product automatically adds to cart
4. Quantity defaults to 1

**Method 2: Manual Search**

1. Type product name or SKU in search bar
2. Select from autocomplete suggestions
3. Click to add to cart

**Method 3: Browse & Click**

1. Scroll through product display
2. Click on product image/name
3. Product adds to cart

### 8.3 Customer Selection

**Using Walking Customer:**

- Default for anonymous sales
- No customer information recorded
- Use for quick cash sales

**Selecting Registered Customer:**

1. Click customer dropdown
2. Search by name or phone
3. Select customer
4. Customer's purchase history is recorded

**Adding New Customer During Sale:**

1. Click **"+ New Customer"** button
2. Enter customer details (name, phone, email)
3. Save and automatically select for current sale

### 8.4 Applying Discounts

**Product-Level Discount:**

1. Click on product in cart
2. Enter discount percentage or fixed amount
3. Price updates automatically

**Order-Level Discount:**

1. Click **"Apply Discount"** button
2. Enter discount percentage or amount
3. Applies to entire order total

### 8.5 Handling Returns During Sale

If customer wants to return items during new purchase:

1. Complete return process first (see Returns section)
2. Then proceed with new sale
3. Or apply return credit to current sale

### 8.6 Partial Payments

1. Add all products to cart
2. Click **"Checkout"**
3. Enter partial amount in **"Amount Received"**
4. System records as credit/pending payment
5. Track pending payments in **Reports** → **Credit Sales**

### 8.7 Receipt Printing

**Configure Printer:**

1. Go to **Settings** → **Receipt Printer**
2. Select printer from dropdown
3. Configure:
   - Receipt width (58mm or 80mm)
   - Header text (business name, address)
   - Footer text (thank you message)
   - Logo (optional)
4. Click **"Test Print"** to verify
5. Save settings

**Print Receipt:**

- Automatically prompts after sale completion
- Or go to **Sales** → **Order History**
- Click **"Print"** icon next to order

---

## 9. Purchase & Restocking

### 9.1 Creating Purchase Order

1. Navigate to **Purchase** → **New Purchase**
2. Select **Supplier** from dropdown
3. Select **Purchase Date** (defaults to today)
4. Add products to purchase:
   - Search product by name/barcode
   - Click to add to purchase list
5. For each product:
   - Set **Purchase Price** (can differ from default)
   - Set **Quantity** to purchase
   - View **Current Stock** level
   - Subtotal calculates automatically

### 9.2 Managing Purchase Items

**Adjust Quantity:**

- Click on quantity field
- Enter new value
- Subtotal updates automatically

**Modify Purchase Price:**

- Click on purchase price field
- Enter actual buying price
- System remembers for future reference

**Remove Item:**

- Click **trash icon** next to product
- Item removed from purchase list

### 9.3 Completing Purchase

1. Review all items and quantities
2. Check total amount
3. Click **"Save Purchase"**
4. Confirm action
5. System automatically:
   - Updates product stock levels
   - Records purchase in history
   - Updates product average cost

### 9.4 Purchase History & Tracking

**View All Purchases:**

1. Go to **Purchase** → **Purchase History**
2. View list with:
   - Purchase ID
   - Date
   - Supplier
   - Total Amount
   - Status

**View Purchase Details:**

1. Click on purchase ID
2. View complete details:
   - All items purchased
   - Quantities and prices
   - Total calculation
   - Supplier information

**Edit Purchase:**

1. Click **"Edit"** icon
2. Modify items/quantities
3. Save changes
4. Stock levels adjust accordingly

**Delete Purchase:**

1. Click **"Delete"** icon
2. Confirm deletion
3. Stock quantities reverse to previous state

⚠️ **Note:** Only recent purchases can be deleted (within 24 hours)

### 9.5 Supplier Management

**Add New Supplier:**

1. Go to **Purchase** → **Suppliers**
2. Click **"Add Supplier"**
3. Enter details:
   - Supplier Name*
   - Contact Person
   - Phone Number*
   - Email
   - Address
   - Credit Terms (days)
   - Notes
4. Save supplier

**Track Supplier Performance:**

- View total purchases from each supplier
- Track payment history
- Monitor delivery reliability (manual notes)

---

## 10. Customer Management

### 10.1 Adding Customers

**From Customer Module:**

1. Navigate to **Customers** → **All Customers**
2. Click **"Add Customer"**
3. Enter customer details:
   - **Full Name*** (required)
   - **Phone Number*** (required, unique)
   - **Email** (optional)
   - **Address** (optional)
   - **Tax Number** (for businesses)
   - **Credit Limit** (optional)
4. Click **"Save Customer"**

**During Sale (Quick Add):**

1. In POS, click **"+ New Customer"**
2. Enter name and phone
3. Save and automatically select

### 10.2 Customer Information

**View Customer Profile:**

1. Go to **Customers** → **All Customers**
2. Click on customer name
3. View tabs:
   - **Profile** - Basic information
   - **Purchase History** - All orders
   - **Returns** - Returned items
   - **Credit/Payments** - Outstanding balance

**Edit Customer:**

1. Click **"Edit"** icon
2. Update information
3. Save changes

**Delete Customer:**

- Only if no purchase history
- Click **"Delete"** icon
- Confirm action

### 10.3 Customer Loyalty & Credits

**Track Customer Spending:**

- View total purchase amount
- View number of orders
- Last purchase date

**Credit Management:**

1. Set credit limit in customer profile
2. Track outstanding balance
3. Record partial payments
4. View payment history

---

## 11. Returns & Refunds

### 11.1 Processing Returns

1. Navigate to **Sales** → **Returns**
2. Click **"New Return"**
3. Search and select original order:
   - By Order ID
   - By Customer name
   - By Date range
4. Select items to return:
   - Check items being returned
   - Enter return quantity (can be partial)
   - Add reason for return
5. System shows:
   - Return value calculation
   - Stock that will be added back
6. Click **"Process Return"**
7. Select refund method:
   - **Cash Refund**
   - **Credit Note** (for future purchases)
   - **Exchange** (swap with other products)

### 11.2 Return Conditions

**Valid Returns:**

- Within return policy period (configure in settings)
- Products in resaleable condition
- Original receipt/order ID available

**Partial Returns:**

- Can return some items from an order
- Can return partial quantities
- Refund calculated proportionally

### 11.3 Return Reports

1. Go to **Reports** → **Returns**
2. View:
   - Total returns by period
   - Return reasons analysis
   - Products most frequently returned
   - Return rate percentage

---

## 12. Reports & Analytics

### 12.1 Sales Reports

**Daily Sales Report:**

1. Go to **Reports** → **Sales** → **Daily Report**
2. Select date
3. View:
   - Total sales amount
   - Number of transactions
   - Payment methods breakdown
   - Top selling products
   - Hourly sales distribution

**Sales Summary:**

- Navigate to **Reports** → **Sales Summary**
- Select date range (daily, weekly, monthly, custom)
- View trends and comparisons

**Product Sales Report:**

- Shows sales by product
- Quantities sold
- Revenue generated
- Profit margins

### 12.2 Inventory Reports

**Current Stock Report:**

1. Go to **Reports** → **Inventory** → **Current Stock**
2. View all products with:
   - Current quantity
   - Stock value
   - Low stock alerts
3. Export to Excel/PDF

**Stock Movement Report:**

- Shows all stock changes
- Purchases, sales, adjustments
- Date range filtering

**Dead Stock Report:**

- Products not sold in X days
- Helps identify slow-moving items
- Make clearance decisions

### 12.3 Financial Reports

**Profit & Loss:**

1. Go to **Reports** → **Financial** → **P&L**
2. Select period
3. View:
   - Total Revenue
   - Cost of Goods Sold
   - Gross Profit
   - Net Profit

**Cash Flow:**

- Total cash in (sales)
- Total cash out (purchases)
- Net cash flow
- Daily/monthly breakdown

**Tax Report:**

- Sales tax collected
- Purchase tax paid
- Net tax liability
- Export for tax filing

### 12.4 Customer Reports

**Customer Purchase History:**

- Total spent by each customer
- Frequency of visits
- Average transaction value
- Last purchase date

**Top Customers:**

- Highest spenders
- Most frequent buyers
- Helps identify VIP customers

### 12.5 Exporting Reports

1. Open any report
2. Click **"Export"** button
3. Select format:
   - **PDF** - For printing/sharing
   - **Excel** - For further analysis
   - **CSV** - For importing to other systems
4. Save file to desired location

---

## 13. Backup & Restore

### 13.1 Creating Backups

**Manual Backup:**

1. Go to **Settings** → **Backup & Restore**
2. Click **"Create Backup Now"**
3. System creates backup file:
   - Format: `backup_YYYYMMDD_HHMMSS.sql`
   - Includes all data (products, sales, customers, etc.)
4. Save backup file to:
   - External USB drive (recommended)
   - Cloud storage (Dropbox, Google Drive)
   - Network location

**Automatic Backups:**

1. Enable in **Settings** → **Backup Settings**
2. Configure:
   - Backup frequency (daily, weekly)
   - Backup time (e.g., 2:00 AM)
   - Backup location
   - Number of backups to keep (e.g., last 30 days)

### 13.2 Restoring from Backup

⚠️ **IMPORTANT:** Restoring will replace current data with backup data

1. Go to **Settings** → **Backup & Restore**
2. Click **"Restore from Backup"**
3. Select backup file from your storage
4. System shows:
   - Backup date and time
   - Data included
5. Confirm restoration
6. Wait for process to complete (may take 1-5 minutes)
7. Application will reload with restored data

### 13.3 Backup Best Practices

**Frequency:**

- **Daily backups** for active businesses
- **Before major changes** (system updates, bulk imports)
- **After month-end closing**

**Storage:**

- Keep **at least 3 copies**:
  - One on external drive
  - One in cloud storage
  - One on different physical location
- **Never rely on single backup**

**Verification:**

- Test restore process monthly
- Verify backup file integrity
- Ensure backup files are not corrupted

**Retention:**

- Keep daily backups for 30 days
- Keep monthly backups for 12 months
- Keep year-end backups permanently

### 13.4 Data Recovery Scenarios

**Scenario 1: Accidental Data Deletion**

1. Stop using system immediately
2. Restore from most recent backup
3. Re-enter any data created after backup time

**Scenario 2: System Crash/Corruption**

1. Reinstall SPOS application
2. Restore from backup
3. Verify data integrity

**Scenario 3: Hardware Failure**

1. Install SPOS on new computer
2. Copy backup file from external storage
3. Restore backup
4. Reconfigure printer and hardware settings

---

## 14. Troubleshooting

### 14.1 Common Issues & Solutions

#### Application Won't Start

**Symptom:** SPOS icon clicked but nothing happens

**Solutions:**

1. Check if already running (look in system tray)
2. Restart computer
3. Run as Administrator
4. Reinstall Visual C++ Redistributable:
   - Go to installation folder
   - Run `vc_redist.x64.exe`
5. Check Windows Event Viewer for errors
6. Reinstall SPOS

#### Database Connection Error

**Symptom:** "MySQL connection failed" or "Database error"

**Solutions:**

1. Wait 60 seconds - database may be starting
2. Check if MySQL process is running:
   - Open Task Manager
   - Look for `mysqld.exe`
3. If not running, restart application
4. If persists, check MySQL error log:
   - Location: `C:\Program Files\SPOS\resources\mysql\data\*.err`
5. Restore from backup if database corrupted

#### Application Freezes/Slow Performance

**Symptoms:** Slow response, frequent freezing

**Solutions:**

1. Close other applications (free up RAM)
2. Clear application cache:
   - Settings → Maintenance → Clear Cache
3. Check database size:
   - Large databases (>500MB) may slow down
   - Archive old data or backup and start fresh
4. Check disk space (need at least 1GB free)
5. Restart application and computer

#### Printer Not Working

**Symptom:** Receipt doesn't print or prints incorrectly

**Solutions:**

1. Verify printer is ON and connected
2. Check printer in Windows:
   - Settings → Devices → Printers
   - Ensure printer is set as default
   - Print test page from Windows
3. Reconfigure in SPOS:
   - Settings → Receipt Printer
   - Select correct printer
   - Test print
4. Check paper loaded correctly
5. Reinstall printer drivers

#### Barcode Scanner Not Working

**Symptom:** Scanning doesn't add products

**Solutions:**

1. Check scanner connected via USB
2. Test scanner in Notepad:
   - Open Notepad
   - Scan a barcode
   - Should type the barcode number
3. If works in Notepad but not SPOS:
   - Focus on search bar in POS
   - Try scanning again
4. Check barcode format:
   - Scanner must send "Enter" key after scan
   - Configure scanner if needed
5. Verify product has correct barcode in database

#### Login Issues

**Symptom:** "Invalid credentials" or can't login

**Solutions:**

1. Verify Caps Lock is OFF
2. Try default credentials:
   - admin@spos.com / admin123
3. Reset password:
   - Login as admin
   - Settings → Users
   - Reset user password
4. If admin locked out:
   - Contact support for database password reset

#### Missing Products/Data

**Symptom:** Products disappeared or data lost

**Solutions:**

1. Check filters/search:
   - Clear all filters
   - Check "Show Inactive" products
2. Verify correct date range in reports
3. If truly missing:
   - Restore from most recent backup
   - Re-enter data if no backup available

### 14.2 Error Messages Explained

**"Migration failed"**

- Database schema update failed
- Solution: Restore from backup, reinstall application

**"Access denied"**

- Permission issue with files/folders
- Solution: Run as Administrator

**"Port already in use"**

- Another instance of SPOS running
- Solution: Close all SPOS windows, restart

**"Low disk space"**

- Less than 1GB space available
- Solution: Free up disk space, delete old files

**"License expired/invalid"**

- License key issue
- Solution: Contact support for new license key

### 14.3 Performance Optimization

**Speed Up Application:**

1. Close unused browser tabs and applications
2. Disable startup programs
3. Run disk cleanup:
   - Windows → Settings → Storage → Free up space
4. Defragment hard drive (if using HDD)
5. Increase RAM if consistently low

**Database Optimization:**

1. Go to Settings → Maintenance
2. Click **"Optimize Database"**
3. Runs monthly automatically
4. Removes orphaned records and rebuilds indexes

### 14.4 Getting Support

**Before Contacting Support:**

1. Check this manual
2. Note exact error message
3. Note steps to reproduce issue
4. Check system requirements met

**Contact Information:**

- Email: think.code.sync
- Include:
  - SPOS version (1.0.5)
  - Windows version
  - Error message/screenshot
  - What you were trying to do

---

## 15. Best Practices

### 15.1 Daily Operations

✅ **DO:**

- Backup data daily (end of day)
- Verify cash against system total
- Check low stock alerts
- Review sales reports
- Test receipt printer before opening
- Update product prices as needed

❌ **DON'T:**

- Shutdown PC without closing SPOS properly
- Delete products with sales history
- Share admin password
- Skip daily backups
- Ignore low stock warnings

### 15.2 Inventory Management

✅ **DO:**

- Conduct physical stock counts monthly
- Update products immediately after purchase
- Set accurate alert quantities
- Use consistent units (kg vs g)
- Archive old/discontinued products (don't delete)
- Take photos of products for easy identification

❌ **DON'T:**

- Manually adjust stock without reason/notes
- Create duplicate products
- Use generic product names
- Leave purchase prices blank
- Ignore dead stock

### 15.3 Customer Service

✅ **DO:**

- Collect customer information (phone at minimum)
- Process returns promptly
- Print receipts for all sales
- Verify prices before checkout
- Thank customers after purchase
- Handle complaints professionally

❌ **DON'T:**

- Process returns without original order verification
- Modify prices without authorization
- Share customer data
- Rush checkout causing errors

### 15.4 Security

✅ **DO:**

- Change default passwords immediately
- Use strong passwords (8+ characters, mixed case, numbers)
- Lock computer when away (Win+L)
- Limit admin access
- Review user activity logs monthly
- Keep software updated

❌ **DON'T:**

- Share login credentials
- Write passwords on sticky notes
- Leave computer unlocked
- Give cashiers admin access
- Ignore suspicious activity

### 15.5 Data Management

✅ **DO:**

- Backup before major changes
- Store backups in multiple locations
- Test restore process quarterly
- Archive old data annually
- Export reports for tax filing
- Document any manual adjustments

❌ **DON'T:**

- Rely on single backup
- Store backups only on same computer
- Delete old backups prematurely
- Make bulk changes without testing
- Skip database maintenance

### 15.6 Financial Practices

✅ **DO:**

- Reconcile cash daily
- Track all expenses
- Review profit margins monthly
- Monitor credit sales
- Record all returns properly
- Keep physical receipts for purchases

❌ **DON'T:**

- Accept payments outside the system
- Delay recording transactions
- Mix personal and business funds
- Ignore discrepancies
- Process refunds without documentation

### 15.7 Training

✅ **DO:**

- Train all staff on basic operations
- Create role-specific training plans
- Document custom procedures
- Have backup staff trained on all tasks
- Review this manual with new employees
- Conduct refresher training quarterly

❌ **DON'T:**

- Assume staff knows everything
- Skip training for "temporary" staff
- Give access before training
- Overlook mistakes repeatedly

---

## Appendix A: Keyboard Shortcuts

| Action           | Shortcut        |
| ---------------- | --------------- |
| Focus Search Bar | `Ctrl + F`      |
| Quick Checkout   | `F9`            |
| Clear Cart       | `Ctrl + Delete` |
| New Product      | `Ctrl + N`      |
| Save             | `Ctrl + S`      |
| Print Receipt    | `Ctrl + P`      |
| Logout           | `Ctrl + L`      |

---

## Appendix B: Default Data Reference

### Default Users

- admin@spos.com (Admin)
- cashier@spos.com (Cashier)
- sales@spos.com (Sales Associate)

### Default Customer

- Walking Customer (ID: 1)

### Default Supplier

- Own Supplier (ID: 1)

### Default Units

- Piece (pc), Kilogram (kg), Gram (g), Liter (l), Milliliter (ml), Box, Dozen

### Default Currency

- USD ($)

---

## Appendix C: Database Backup Schedule

| Frequency | Retention | When                |
| --------- | --------- | ------------------- |
| Daily     | 30 days   | End of business day |
| Weekly    | 3 months  | Sunday night        |
| Monthly   | 12 months | Last day of month   |
| Yearly    | Permanent | December 31         |

---

## Appendix D: System File Locations

**Installation Directory:**

```
C:\Program Files\SPOS\
```

**Database Files:**

```
C:\Program Files\SPOS\resources\mysql\data\
```

**Backup Files:**

```
C:\Users\[YourName]\Documents\SPOS Backups\
```

**Log Files:**

```
C:\Program Files\SPOS\resources\storage\logs\
```

**Receipt Templates:**

```
C:\Program Files\SPOS\resources\storage\receipts\
```

---

## Appendix E: Glossary

**SKU** - Stock Keeping Unit, unique product identifier

**POS** - Point of Sale, the transaction interface

**Barcode** - Machine-readable product identifier

**Walking Customer** - Anonymous customer for quick sales

**Credit Sale** - Sale with pending payment

**Dead Stock** - Products not sold for extended period

**Alert Quantity** - Minimum stock level before warning

**Purchase Price** - Cost to buy product from supplier

**Selling Price** - Price charged to customer

**Gross Profit** - Revenue minus cost of goods

**Net Profit** - Gross profit minus expenses

**Reconciliation** - Matching physical cash to system records

**Backup** - Copy of database for disaster recovery

**Restore** - Loading data from backup file

**Migration** - Database schema update

---

## Document Information

**Manual Version:** 1.0  
**Application Version:** 1.0.5  
**Last Updated:** February 1, 2026  
**Document Status:** Official Release  

**Prepared By:** SINYX Development Team  
**Copyright:** © 2026 SINYX. All Rights Reserved.  

---

**End of Manual**

For technical support, contact: contact@sinyxcode.com
