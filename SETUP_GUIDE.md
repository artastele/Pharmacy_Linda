# 🚀 Pharmacy Inventory System - Setup Guide

## Prerequisites

### Required Software:
1. **XAMPP** (includes Apache, MySQL, PHP)
   - Download: https://www.apachefriends.org/
   - Install to: `C:\xampp`

2. **Web Browser**
   - Chrome, Firefox, or Edge

---

## Step-by-Step Setup

### 1. Install XAMPP
1. Download and install XAMPP
2. Start **Apache** and **MySQL** from XAMPP Control Panel

### 2. Create Database
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click "New" to create database
3. Database name: `pharmacy_internship`
4. Collation: `utf8mb4_general_ci`
5. Click "Create"

### 3. Import Database Schema

**Option A: Using SQL Scripts (Recommended)**

Run these SQL scripts in order in phpMyAdmin:

1. **Create Tables** (if not already created)
   - You should have the main tables:
     - `product_inventory`
     - `p1014_inventory_reports`
     - `p1014_inventory_report_items`
     - `p1014_requisition_requests`
     - `p1014_requisition_items`
     - `users` (for authentication)

2. **Add Tracking Columns**
   - File: `add_tracking_columns.sql`
   - Adds: `sold`, `new_stock` to product_inventory
   - Adds: `denial_remarks` to p1014_inventory_reports

3. **Add Sample Products**
   - File: `add_sample_products.sql`
   - Adds 10+ sample products with quantities

**How to run SQL scripts:**
1. Open phpMyAdmin
2. Select database: `pharmacy_internship`
3. Click "SQL" tab
4. Copy and paste script content
5. Click "Go"

### 4. Copy Project Files
1. Copy the entire `Pharmacy_Linda` folder to: `C:\xampp\htdocs\`
2. Final path should be: `C:\xampp\htdocs\Pharmacy_Linda\`

### 5. Configure Database Connection
1. Open file: `config.php`
2. Verify settings:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'pharmacy_internship');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Empty for default XAMPP
   ```

### 6. Test the Installation
1. Open browser
2. Go to: `http://localhost/Pharmacy_Linda/`
3. You should see the login page

---

## Initial Setup & Testing

### 1. Create Test Users (if needed)
Run this SQL in phpMyAdmin:

```sql
-- Create test users (adjust table structure as needed)
INSERT INTO users (username, password, full_name, role) VALUES
('intern', 'password123', 'Test Intern', 'Intern'),
('tech', 'password123', 'Test Technician', 'Pharmacy Technician'),
('pharm', 'password123', 'Test Pharmacist', 'Pharmacist');
```

### 2. Fix Report Status Issues
If reports don't show as approved, run:

**Option 1: Use the fix page**
```
http://localhost/Pharmacy_Linda/approve_reports_now.php
```
- Click "Approve Now" for each report
- Status updates immediately

**Option 2: Run SQL directly**
```sql
UPDATE p1014_inventory_reports 
SET status = 'approved' 
WHERE status = 'submitted' OR status IS NULL OR status = '';
```

### 3. Verify Everything Works

**As Intern:**
1. Login as Intern
2. Go to "Manage Products" - Add/Edit products
3. Go to "Create Inventory Report" - Should see products
4. Fill out report and submit

**As Technician:**
1. Login as Technician
2. Go to "View Inventory Reports"
3. Click "View" on a report
4. Click "Approve Report"
5. Should see green "Approved Reports" section
6. Click "Create Request" button
7. Fill out requisition form

**As Pharmacist:**
1. Login as Pharmacist
2. View requisition requests
3. Approve/Reject requests

---

## Common Issues & Solutions

### Issue 1: "No products showing in Create Inventory Report"

**Cause:** Missing columns or no products in database

**Solution:**
1. Run: `http://localhost/Pharmacy_Linda/check_products.php`
2. If columns missing: Run `add_tracking_columns.sql`
3. If no products: Run `add_sample_products.sql`

### Issue 2: "Reports not showing as approved"

**Cause:** Status not updating or browser cache

**Solution:**
1. Run: `http://localhost/Pharmacy_Linda/approve_reports_now.php`
2. Click "Approve Now" for each report
3. Clear browser cache: `Ctrl + Shift + Delete`
4. Hard refresh: `Ctrl + F5`

### Issue 3: "No approved reports in Requisition Form"

**Cause:** Reports not actually approved in database

**Solution:**
1. Run: `http://localhost/Pharmacy_Linda/debug_requisition.php`
2. It will show and fix the issue automatically
3. Clear browser cache
4. Refresh requisition form

### Issue 4: "Deleted reports still showing"

**Cause:** Browser cache

**Solution:**
1. Press `Ctrl + F5` (hard refresh)
2. Or clear cache: `Ctrl + Shift + Delete`
3. Or use incognito mode: `Ctrl + Shift + N`

---

## Diagnostic Tools

We created several diagnostic tools to help debug issues:

### 1. Quick Check
```
http://localhost/Pharmacy_Linda/quick_check.php
```
- Fast overview of database status
- Shows product count, report count
- Tests main queries

### 2. Full Diagnostic
```
http://localhost/Pharmacy_Linda/diagnostic_full.php
```
- Complete system analysis
- Shows all columns, tests all queries
- Provides specific recommendations

### 3. Check Products
```
http://localhost/Pharmacy_Linda/check_products.php
```
- Shows all products in database
- Displays sold and new_stock values
- Verifies column existence

### 4. Check Reports
```
http://localhost/Pharmacy_Linda/check_reports.php
```
- Shows all reports with delete buttons
- Real-time data (no cache)
- Verify reports are actually deleted

### 5. Approve Reports Now
```
http://localhost/Pharmacy_Linda/approve_reports_now.php
```
- One-click approval for reports
- Immediate status update
- No caching issues

### 6. Debug Requisition
```
http://localhost/Pharmacy_Linda/debug_requisition.php
```
- Tests requisition form query
- Shows why reports don't appear
- Auto-fixes status issues

### 7. Diagnostic Index
```
http://localhost/Pharmacy_Linda/diagnostic_index.php
```
- Central hub for all diagnostic tools
- Links to all troubleshooting pages

---

## File Structure

```
Pharmacy_Linda/
├── index.php                          # Login page
├── config.php                         # Database configuration
├── common.php                         # Common functions
├── db.php                            # Database connection
│
├── dashboard_intern.php              # Intern dashboard
├── dashboard_technician.php          # Technician dashboard
├── dashboard_pharmacist.php          # Pharmacist dashboard
│
├── manage_product_inventory.php      # Product CRUD (Intern)
├── create_inventory_report.php       # Create report (Intern)
├── view_inventory_report.php         # View/Approve reports (Technician)
├── requisition_form.php              # Create requisition (Technician)
├── requisition_approval.php          # Approve requisition (Pharmacist)
│
├── add_tracking_columns.sql          # Migration: Add columns
├── add_sample_products.sql           # Sample data
├── remove_audit_column.sql           # Remove audit references
│
├── Diagnostic Tools/
│   ├── diagnostic_index.php          # Main diagnostic hub
│   ├── quick_check.php               # Fast check
│   ├── diagnostic_full.php           # Complete diagnostic
│   ├── check_products.php            # Product verification
│   ├── check_reports.php             # Report verification
│   ├── approve_reports_now.php       # One-click approve
│   └── debug_requisition.php         # Requisition debug
│
└── Documentation/
    ├── SETUP_GUIDE.md                # This file
    ├── TROUBLESHOOTING_GUIDE.md      # Detailed troubleshooting
    ├── AUDIT_REMOVAL_SUMMARY.md      # Audit removal details
    └── APPROVAL_WORKFLOW_GUIDE.md    # Approval workflow
```

---

## Database Schema

### Key Tables:

**product_inventory**
- `product_id` (INT, Primary Key)
- `drug_name` (VARCHAR)
- `manufacturer` (VARCHAR)
- `current_inventory` (INT)
- `sold` (INT) - Items sold/used
- `new_stock` (INT) - New items received

**p1014_inventory_reports**
- `report_id` (INT, Primary Key)
- `report_date` (DATE)
- `ward` (VARCHAR)
- `created_by` (VARCHAR)
- `remarks` (TEXT)
- `denial_remarks` (TEXT) - Technician remarks when denying
- `status` (VARCHAR) - 'submitted', 'approved', 'denied'
- `created_at` (TIMESTAMP)

**p1014_inventory_report_items**
- `item_id` (INT, Primary Key)
- `report_id` (INT, Foreign Key)
- `product_id` (INT, Foreign Key)
- `sold` (INT)
- `new_stock` (INT)
- `old_stock` (INT)
- `stock_on_hand` (INT) - Physical count
- `expiration_date` (DATE)
- `lot_number` (VARCHAR)
- `remarks` (TEXT)

**p1014_requisition_requests**
- `requisition_id` (INT, Primary Key)
- `report_id` (INT, Foreign Key)
- `requisition_date` (DATE)
- `requested_by` (VARCHAR)
- `department` (VARCHAR)
- `suggested_vendor` (VARCHAR)
- `delivery_point` (VARCHAR)
- `delivery_date` (DATE)
- `finance_code` (VARCHAR)
- `justification` (TEXT)
- `status` (VARCHAR) - 'pending', 'approved', 'rejected'
- `total_amount` (DECIMAL)

---

## Workflow Summary

### Complete Process Flow:

```
1. INTERN
   ├─ Manage Products (Add/Edit/Delete)
   │  └─ Update sold/new_stock values
   │
   └─ Create Inventory Report
      ├─ Sold and New Stock auto-filled from products
      ├─ Old Stock defaults to current inventory
      └─ Balance Stock auto-calculated (Old + New - Sold)

2. TECHNICIAN
   ├─ View Inventory Reports
   │  ├─ See submitted reports
   │  └─ Click "View" to see details
   │
   ├─ Approve or Deny Report
   │  ├─ If Deny: Must provide remarks
   │  └─ If Approve: Report moves to "Approved Reports" section
   │
   └─ Create Requisition Request
      ├─ Select approved report
      ├─ Choose items to request
      ├─ Enter quantities and prices
      └─ Submit to Pharmacist

3. PHARMACIST
   └─ Review Requisition Requests
      ├─ View details
      └─ Approve or Reject
```

---

## Quick Start Checklist

- [ ] XAMPP installed and running (Apache + MySQL)
- [ ] Database `pharmacy_internship` created
- [ ] SQL scripts run:
  - [ ] `add_tracking_columns.sql`
  - [ ] `add_sample_products.sql`
- [ ] Project files in `C:\xampp\htdocs\Pharmacy_Linda\`
- [ ] `config.php` configured correctly
- [ ] Test users created
- [ ] Can login to system
- [ ] Products showing in Create Inventory Report
- [ ] Can create and submit report
- [ ] Can approve report as Technician
- [ ] Approved reports show in Requisition Form
- [ ] Can create requisition request

---

## Support & Debugging

If you encounter issues:

1. **Check diagnostic tools first:**
   - Start with `diagnostic_index.php`
   - Run `quick_check.php` for fast overview

2. **Common fixes:**
   - Clear browser cache: `Ctrl + Shift + Delete`
   - Hard refresh: `Ctrl + F5`
   - Use incognito mode: `Ctrl + Shift + N`

3. **Database issues:**
   - Run `approve_reports_now.php` to fix status
   - Run `debug_requisition.php` to test queries
   - Check phpMyAdmin for actual data

4. **Still stuck?**
   - Check `TROUBLESHOOTING_GUIDE.md`
   - Review error logs in `C:\xampp\apache\logs\error.log`

---

## Next Steps After Setup

1. **Test the complete workflow** with all three roles
2. **Customize** user interface if needed
3. **Add real users** to the system
4. **Configure** any additional settings
5. **Train users** on the workflow

Good luck with your setup! 🚀
