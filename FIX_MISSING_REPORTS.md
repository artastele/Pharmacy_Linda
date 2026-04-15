# 🔧 Fix: Missing Reports in View Inventory Reports

## Problem
The report created by "linda" (Report #8) is not showing in the View Inventory Reports page even though it exists in the database.

## Root Cause
The report has a NULL or empty `status` field in the database. The query was looking for reports with status IN ('submitted', 'approved', 'denied'), so reports with NULL/empty status were excluded.

## Solution Applied

### 1. Updated Query to Handle NULL Status
Modified `view_inventory_report.php` to include reports with NULL or empty status:

```sql
WHERE r.status IN ('submitted', 'approved', 'denied') 
   OR r.status IS NULL 
   OR r.status = ''
```

### 2. Default Status Handling
Added code to treat NULL/empty status as 'submitted':

```php
$status = $r['status'] ?: 'submitted';
```

### 3. Created Fix Script
Created `fix_report_status.php` to automatically fix reports with NULL/empty status.

## How to Fix

### Option 1: Run PHP Fix Script (EASIEST)
1. Open: `http://localhost/Pharmacy_Linda/fix_report_status.php`
2. The script will:
   - Find all reports with NULL/empty status
   - Set their status to 'submitted'
   - Show you the updated reports
3. Done! Go to View Inventory Reports page

### Option 2: Run SQL Script
1. Open phpMyAdmin
2. Select database: `pharmacy_internship`
3. Click "SQL" tab
4. Copy and paste from `fix_report_status.sql`:
   ```sql
   UPDATE p1014_inventory_reports 
   SET status = 'submitted' 
   WHERE status IS NULL OR status = '';
   ```
5. Click "Go"

### Option 3: Manual Fix in phpMyAdmin
1. Open phpMyAdmin
2. Browse `p1014_inventory_reports` table
3. Find Report #8 (ward: Linda)
4. Edit the row
5. Set `status` to `'submitted'`
6. Save

## After Fixing

Once the status is fixed, the report will appear in View Inventory Reports page:

1. **In "All Inventory Reports" table**
   - Shows with yellow "SUBMITTED" badge
   - Has "View" button

2. **Can be approved**
   - Click "View" to see details
   - Click "Approve Report" button
   - Report moves to "Approved Reports" section

3. **Can create requisition**
   - After approval, "Create Request" button appears
   - Click to go to requisition form

## Prevention

To prevent this issue in the future, the `create_inventory_report.php` already sets status to 'submitted' when creating new reports:

```php
INSERT INTO p1014_inventory_reports 
(report_date, ward, created_by, remarks, status) 
VALUES ('...', '...', '...', '...', 'submitted')
```

## Verification Steps

After running the fix:

1. ✅ Open `http://localhost/Pharmacy_Linda/view_inventory_report.php`
2. ✅ You should see Report #8 in the table
3. ✅ Status should show "SUBMITTED" (yellow badge)
4. ✅ "View" button should be visible
5. ✅ Click "View" to see report details
6. ✅ Click "Approve Report" to approve it
7. ✅ Report should appear in "Approved Reports" section at top
8. ✅ "Create Request" button should be visible

## Files Modified

- ✅ `view_inventory_report.php` - Updated query to handle NULL status
- ✅ `fix_report_status.php` - Created fix script
- ✅ `fix_report_status.sql` - Created SQL fix script

## Summary

**Problem:** Report not showing because status was NULL/empty  
**Solution:** Updated query + fix script to set status to 'submitted'  
**Action:** Run `fix_report_status.php` to fix existing reports  
**Result:** All reports now visible and can be approved/processed  

🎯 **Quick Fix:** Just open `http://localhost/Pharmacy_Linda/fix_report_status.php` and it's done!
