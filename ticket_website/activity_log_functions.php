<?php
function ActivityUser($conn, $user_id, $action)
{
    // الحصول على اسم المستخدم بناءً على user_id
    $sql_user = "SELECT username, email FROM Users WHERE user_id = ?";
    $params_user = array($user_id);
    $stmt_user = sqlsrv_query($conn, $sql_user, $params_user);

    if ($stmt_user === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $user = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);
    $username = $user['username'];
    $user_email = $user['email'];

    // الحصول على عنوان الـ IP
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // الحصول على معلومات الجهاز (تحديد المتصفح)
    $device_info = $_SERVER['HTTP_USER_AGENT'];

    // إدخال النشاط في الجدول
    $sql = "INSERT INTO ActivityUser (user_id, username, action, ip_address, device_info, user_email) VALUES (?, ?, ?, ?, ?,?)";
    $params = array($user_id, $username, $action, $ip_address, $device_info, $user_email);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt($stmt_user);
}
?>