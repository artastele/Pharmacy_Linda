# ⚠️ IMPORTANT: Run Database Migration First!

## You're seeing errors because the database hasn't been updated yet.

The new columns (sold, new_stock, old_stock, balance_stock) don't exist in your database yet. You need to run the SQL migration script.

## ✅ How to Fix:

### Step 1: Open phpMyAdmin
1. Go to `http://localhost/phpmyadmin`
2. Login with your MySQL credentials

### Step 2: Select Database
1. Click on `pharmacy_internship` database in the left sidebar

### Step 3: Run Migration Script
1. Click on the **SQL** tab at the top
2. Open the file: `update_inventory_report_schema.sql`
3. Copy ALL the SQL code from that file
4. Paste it into the SQL query box in phpMyAdmin
5. Click **Go** button to execute

### Step 4: Verify Changes
After running the script, you should see:
```
✓ 4 columns added successfully
✓ Table structure updated
```

Check the table structure:
```sql
DESCRIBE p1014_inventory_report_items;
```

You should now see these columns:
- `sold` (int)
- `new_stock` (int)
- `old_stock` (int)
- `balance_stock` (int, computed)

### Step 5: Test the System
1. Go to intern dashboard
2. Create a new inventory report
3. Fill in the new columns: Sold, New Stock, Old Stock
4. Submit the report
5. Go to technician dashboard
6. View the report - all columns should display correctly

## 🔧 Alternative: Run via Command Line

If you prefer command line:

```bash
# Navigate to your project directory
cd C:\xampp\htdocs\Pharmacy_Linda

# Run the migration
mysql -u root -p pharmacy_internship < update_inventory_report_schema.sql
```

## ⚠️ Current Status

**Before Migration:**
- ❌ Errors when viewing reports
- ❌ New columns show as 0
- ❌ "Undefined array key" warnings

**After Migration:**
- ✅ No errors
- ✅ All columns display correctly
- ✅ Balance stock auto-calculates
- ✅ Full system functionality

## 📝 What the Migration Does

The migration script will:
1. Add `sold` column to track items used
2. Add `new_stock` column to track items received
3. Add `old_stock` column to track previous inventory
4. Add `balance_stock` column (auto-calculated: old + new - sold)
5. Keep existing data intact (backward compatible)

## 🛡️ Safety

- ✅ Safe to run - only adds new columns
- ✅ Doesn't delete any existing data
- ✅ Backward compatible with old reports
- ✅ Can be rolled back if needed

## ❓ Need Help?

If you encounter any issues:
1. Check that you're connected to the correct database
2. Make sure you have permission to ALTER tables
3. Verify the table name is `p1014_inventory_report_items`
4. Check MySQL error logs for details

## 📋 Quick Checklist

- [ ] Opened phpMyAdmin
- [ ] Selected `pharmacy_internship` database
- [ ] Opened SQL tab
- [ ] Copied content from `update_inventory_report_schema.sql`
- [ ] Pasted into SQL query box
- [ ] Clicked "Go" button
- [ ] Verified columns were added
- [ ] Tested creating a new report
- [ ] Tested viewing reports (no errors!)

---

**After running the migration, all errors will be gone and the system will work perfectly!** 🚀
