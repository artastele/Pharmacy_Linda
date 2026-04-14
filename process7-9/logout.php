<?php
require_once "includes/common.php";

ensure_session_started();
session_unset();
session_destroy();

header("Location: login.php?message=You have been logged out.");
exit;
?>
