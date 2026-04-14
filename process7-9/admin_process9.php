<?php
require_once "includes/common.php";
require_role("intern");
redirect_to_role_home($_GET["message"] ?? null);
?>

