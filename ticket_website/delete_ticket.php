<?php
session_start();
include 'db.php';

// التحقق من وجود ID للتذكرة
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ticket_id = $_GET['id'];

    // حذف السجلات المرتبطة في ActivityLog أولاً
    $sql_log_delete = "DELETE FROM ActivityLog WHERE ticket_id = ?";
    $params_log_delete = array($ticket_id);
    $stmt_log_delete = sqlsrv_query($conn, $sql_log_delete, $params_log_delete);

    if ($stmt_log_delete === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // حذف السجلات المرتبطة في ticketassignments
    $sql_assignment_delete = "DELETE FROM ticketassignments WHERE ticket_id = ?";
    $params_assignment_delete = array($ticket_id);
    $stmt_assignment_delete = sqlsrv_query($conn, $sql_assignment_delete, $params_assignment_delete);

    if ($stmt_assignment_delete === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // حذف التذكرة من جدول Tickets
    $sql_ticket_delete = "DELETE FROM Tickets WHERE ticket_id = ?";
    $params_ticket_delete = array($ticket_id);
    $stmt_ticket_delete = sqlsrv_query($conn, $sql_ticket_delete, $params_ticket_delete);

    if ($stmt_ticket_delete === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        echo "success"; // إرسال رسالة النجاح
    }

    // تحرير الاستعلامات
    sqlsrv_free_stmt($stmt_log_delete);
    sqlsrv_free_stmt($stmt_assignment_delete);
    sqlsrv_free_stmt($stmt_ticket_delete);
    sqlsrv_close($conn);
} else {
    // إذا لم يكن ID صحيح، إعادة التوجيه أو الخطأ
    echo "error";
    exit();
}
?>
