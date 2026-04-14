# Member 3 Module (Processes 7-9)

This project contains:

- Process 7: Company Orientation with a live Orientation Progress Tracker checklist
- Process 8: Internship Routine Task Management (Create, List, Edit, Delete, Update)
- Process 9: Product Inventory Initial Entry (Create, List, Edit, Delete)
- Login and Registration with role-based access

## Files

- `index.php` - role-based dashboard
- `login.php` - user login
- `register.php` - user registration
- `logout.php` - logout handler
- `orientation_tracker.php` - orientation checklist tracker for interns
- `tasks_add.php` - add task form (admin)
- `tasks_list.php` - task list + edit/delete
- `tasks_edit.php` - edit task
- `inventory_add.php` - add product form
- `inventory_list.php` - inventory list + edit/delete
- `inventory_edit.php` - edit product
- `config/database.php` - DB connection
- `sql/member3_schema.sql` - SQL schema for phpMyAdmin import

## Setup

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin.
3. Import `sql/member3_schema.sql`.
4. Put this folder in `htdocs` (or your web server root).
5. Open `http://localhost/clark/login.php` and create an account.

## DB Connection

If your MySQL credentials are different, edit:

- `config/database.php`

Default values used:

- host: `localhost`
- user: `root`
- password: *(empty)*
- database: `internship_system`
