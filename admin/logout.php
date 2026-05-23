<?php
session_start();
session_destroy();
header("Location: /vannmarket/admin/login.php");
exit;
