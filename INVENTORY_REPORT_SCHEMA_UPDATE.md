# Inventory Report Schema Update - Complete

## Overview
The inventory report system has been updated to include more detailed tracking columns that help technicians calculate requisition needs more accurately.

## Changes Made

### Database Schema Changes
**New Columns Added to `p1014_inventory_report_items`:**
- `sold` - Items used/sold during the period
- `new_stock` - New items received during the period  
- `old_stock` - Previous inventory level (defaults to current_inventory from product_inventory)
- `balance_stock` - Auto-calculated field: `old_stock + new_stock - sold`

**Removed Column:**
- `stock_at_hims` - No longer needed (replaced by the new tracking columns)

### Files Updated

#### 1. `create_inventory_report.php` ✅
- Updated form to include new columns: Sold, New Stock, Old Stock, Balance Stock
- Old Stock defaults to current_inventory value from product_inventory
- Added JavaScript to auto-calculate Balance Stock as: Old Stock + New Stock - Sold
- Balance Stock is color-coded (red for negative, yellow for zero, green for positive)
- Form submission saves all new fields to database

#### 2. `audit_form.php` ✅
- Updated table display to show: Sold, New Stock, Old Stock, Balance Stock, Physical Count
- Removed old "HIMS Count" and "Variance" columns
- Discrepancy detection now compares Balance Stock vs Physical Count
- Updated discrepancy notes message

#### 3. `stock_report_dashboard.php` ✅
- Updated table to display all new columns
- Shows: Sold, New Stock, Old Stock, Balance Stock, Physical Count
- Balance Stock is color-coded for easy identification
- Removed old "HIMS Count" column

#### 4. `requisition_form.php` ✅
- Updated items table to show all new columns when creating requisitions
- Technicians can now see the full breakdown: Sold, New Stock, Old Stock, Balance Stock, Physical Count
- Helps technicians make informed decisions about quantities to request
- Updated column count in footer (colspan adjusted)

#### 5. `update_inventory_report_schema.sql` ✅
- Complete SQL migration script with detailed comments
- Adds all new columns with proper data types and comments
- Includes optional step to drop old `stock_at_hims` column
- Includes verification query

## How It Works

### For Interns (Creating Reports)
1. Open "Create Inventory Report"
2. For each product, enter:
   - **Sold**: How many items were used/sold
   - **New Stock**: How many new items were received
   - **Old Stock**: Previous inventory (auto-filled from current inventory)
   - **Balance Stock**: Auto-calculated (Old + New - Sold)
3. Submit report to technician

### For Technicians (Auditing & Requesting)
1. **Audit Form**: Review the report with full breakdown of stock movements
2. **Requisition Form**: See detailed stock information to determine how much to request
   - If Balance Stock is low or negative, request more
   - Physical Count shows actual counted inventory
   - Status badges highlight critical items

### For Pharmacists (Approving)
1. View requisition requests with full context
2. Approve/deny based on stock levels and justification
3. Mark as received to update product_inventory

## Database Migration Required

**IMPORTANT**: You must run the SQL migration script before using the updated system.

### Steps to Migrate:
1. Open phpMyAdmin or MySQL command line
2. Select the `pharmacy_internship` database
3. Run the script: `update_inventory_report_schema.sql`
4. Verify columns were added successfully

### Migration Script Location:
```
update_inventory_report_schema.sql
```

### What the Migration Does:
- Adds 4 new columns to `p1014_inventory_report_items`
- Sets up `balance_stock` as a computed/generated column
- Optionally removes old `stock_at_hims` column (commented out by default)

## Testing Checklist

After running the migration, test the complete flow:

- [ ] **Create Report** (Intern)
  - Enter Sold, New Stock, Old Stock values
  - Verify Balance Stock calculates correctly
  - Submit report

- [ ] **Audit Report** (Technician)
  - View submitted report with all new columns
  - Verify data displays correctly
  - Submit audit

- [ ] **Create Requisition** (Technician)
  - Select audited report
  - View items with full stock breakdown
  - Create requisition request

- [ ] **Approve Requisition** (Pharmacist)
  - View requisition details
  - Approve request
  - Mark as received

- [ ] **Verify Inventory Update** (All)
  - Check that product_inventory updated correctly
  - Verify intern sees updated stock levels

## Benefits

1. **Better Tracking**: See exactly what was sold, received, and remaining
2. **Informed Decisions**: Technicians can calculate exact needs based on usage patterns
3. **Transparency**: Full audit trail of stock movements
4. **Accuracy**: Auto-calculated balance reduces manual errors
5. **Visual Feedback**: Color-coded balance stock for quick assessment

## Formula

```
Balance Stock = Old Stock + New Stock - Sold
```

**Example:**
- Old Stock: 50 (what we had before)
- New Stock: 20 (what we received)
- Sold: 30 (what we used)
- Balance Stock: 50 + 20 - 30 = **40**

## Notes

- The `balance_stock` column is a **generated/computed column** - it automatically updates when sold, new_stock, or old_stock changes
- Old Stock should typically match the current_inventory from product_inventory at the time of report creation
- Physical Count (stock_on_hand) is what was actually counted during inventory
- Any difference between Balance Stock and Physical Count indicates a discrepancy that needs investigation

## Status: ✅ COMPLETE

All files have been updated and are ready for use after database migration.
