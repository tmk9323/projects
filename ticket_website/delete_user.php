<?php
session_start();
include 'db.php';

// التحقق من وجود 'id' في الرابط ومن أنه قيمة رقمية صالحة
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];

    // تحضير الاستعلام بشكل آمن باستخدام Prepared Statements
    $query = "DELETE FROM Users WHERE user_id = ?";
    $stmt_user_delete = sqlsrv_prepare($conn, $query, array(&$user_id));

    if ($stmt_user_delete === false) {
        die(print_r(sqlsrv_errors(), true)); // إذا كان هناك خطأ في التحضير
    } else {
        // تنفيذ الاستعلام
        if (sqlsrv_execute($stmt_user_delete)) {
            // تحرير الاستعلامات وإغلاق الاتصال قبل إعادة التوجيه
            sqlsrv_free_stmt($stmt_user_delete);
            sqlsrv_close($conn);

            // إعادة استجابة نجاح
            echo "success";
            exit(); // تأكد من عدم وجود إعادة توجيه هنا
        } else {
            die(print_r(sqlsrv_errors(), true));
        }
    }
} else {
    die("Invalid request.");
}
?>
