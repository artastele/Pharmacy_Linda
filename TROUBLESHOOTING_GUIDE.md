# 🔧 Troubleshooting Guide

## Issues You're Experiencing

1. **Sample products not showing in Create Inventory Report page**
2. **Deleted reports still showing despite being removed from database**

---

## Step-by-Step Diagnostic Process

### Step 1: Run the Full Diagnostic Tool

Open this URL in your browser:
```
http://localhost/Pharmacy_Linda/diagnostic_full.php
```

This will show you:
- ✅ Whether product_inventory table exists
- ✅ Whether required columns (sold, new_stock) exist
- ✅ How many products are in the database
- ✅ Whether reports table has denial_remarks column
- ✅ What reports exist in the database
- ✅ Test the exact query used by create_inventory_report.php

### Step 2: Follow the Recommendations

The diagnostic tool will tell you exactly what's wrong and what to do.

---

## Common Issues & Solutions

### Issue 1: Missing Columns (sold, new_stock, denial_remarks)

**Symptoms:**
- Error: "Unknown column 'sold' in 'field list'"
- Error: "Unknown column 'denial_remarks' in 'field list'"

**Solution:**
1. Open phpMyAdmin
2. Select database: `pharmacy_internship`
3. Click "SQL" tab
4. Copy and paste the contents of `add_tracking_columns.sql`
5. Click "Go"

### Issue 2: No Products in Database

**Symptoms:**
- Create Inventory Report page shows empty table
- No products appear in Manage Products page

**Solution:**
1. Open phpMyAdmin
2. Select database: `pharmacy_internship`
3. Click "SQL" tab
4. Copy and paste the contents of `add_sample_products.sql`
5. Click "Go"

### Issue 3: Deleted Reports Still Showing

**Symptoms:**
- Reports deleted from database still appear on view page

**Solution:**
This is a **browser caching issue**. Try these in order:

1. **Hard Refresh** (Try this first!)
   - Press `Ctrl + F5` (Windows)
   - Or `Ctrl + Shift + R` (Windows)
   - Or `Cmd + Shift + R` (Mac)

2. **Clear Browser Cache**
   - Press `Ctrl + Shift + Delete`
   - Select "Cached images and files"
   - Click "Clear data"

3. **Try Incognito/Private Mode**
   - Press `Ctrl + Shift + N` (Chrome)
   - Or `Ctrl + Shift + P` (Firefox)
   - Open the page in incognito mode

4. **Try a Different Browser**
   - If using Chrome, try Firefox or Edge
   - This confirms it's a caching issue

---

## Quick Verification Tools

### Check Products in Database
```
http://localhost/Pharmacy_Linda/check_products.php
```
Shows all products with their sold/new_stock values.

### Check Reports in Database
```
http://localhost/Pharmacy_Linda/check_reports.php
```
Shows all reports with delete buttons. Use this to verify reports are actually deleted.

### Check Column Status
```
http://localhost/Pharmacy_Linda/check_columns.php
```
Verifies if sold, new_stock, and denial_remarks columns exist.

---

## Expected Database Schema

### product_inventory table should have:
- `product_id` (INT, Primary Key)
- `drug_name` (VARCHAR)
- `manufacturer` (VARCHAR)
- `current_inventory` (INT)
- `sold` (INT) ← **Must exist**
- `new_stock` (INT) ← **Must exist**

### p1014_inventory_reports table should have:
- `report_id` (INT, Primary Key)
- `report_date` (DATE)
- `ward` (VARCHAR)
- `created_by` (VARCHAR)
- `remarks` (TEXT)
- `denial_remarks` (TEXT) ← **Must exist**
- `status` (VARCHAR)
- `created_at` (TIMESTAMP)

---

## Still Having Issues?

If the diagnostic tool shows everything is correct but products still don't appear:

1. Check browser console for JavaScript errors:
   - Press `F12` to open Developer Tools
   - Click "Console" tab
   - Look for red error messages

2. Check PHP errors:
   - Look at the top of the page for PHP warnings/errors
   - Check `C:\xampp\apache\logs\error.log`

3. Verify you're logged in as an Intern:
   - The Create Inventory Report page requires Intern role
   - Try logging out and logging back in

4. Check database connection:
   - Open `config.php`
   - Verify DB_NAME is `pharmacy_internship`
   - Verify DB_USER is `root`
   - Verify DB_PASS is empty (or your password)

---

## What We've Fixed

✅ Added cache prevention headers to:
- `create_inventory_report.php`
- `view_inventory_report.php`
- `check_reports.php`

✅ Created diagnostic tools:
- `diagnostic_full.php` - Complete system check
- `check_products.php` - Product verification
- `check_reports.php` - Report verification with delete
- `check_columns.php` - Column existence check

✅ Updated queries to handle missing columns gracefully using `COALESCE()`

---

## Next Steps

1. **Run diagnostic_full.php** - This will tell you exactly what's wrong
2. **Follow the recommendations** - The tool will guide you
3. **Clear browser cache** - If reports still show after deletion
4. **Report back** - Let me know what the diagnostic tool shows

The diagnostic tool will give you a clear picture of what's happening in your database!
