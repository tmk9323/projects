<?php
session_start();
session_destroy();
header("Location: login.php"); // إعادة التوجيه إلى صفحة تسجيل الدخول
exit();
?>