<?php
require_once "config/database.php";
require_once "includes/common.php";
require_role("hr_personnel");
header("Location: admin_process7_8.php?message=Orientation module was removed.");
exit;
