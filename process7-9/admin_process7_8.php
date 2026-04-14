<?php
require_once "includes/common.php";
require_role("hr_personnel");
redirect_to_role_home($_GET["message"] ?? null);
?>

