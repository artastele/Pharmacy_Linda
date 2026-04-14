<?php
require_once "includes/common.php";
require_login();
redirect_to_role_home($_GET["message"] ?? null);
