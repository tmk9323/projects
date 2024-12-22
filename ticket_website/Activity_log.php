<?php
session_start();
include 'db.php';

// التحقق من أن المستخدم لديه صلاحية Admin
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'super') {
    // إذا لم يكن المستخدم Admin، يتم إعادة توجيهه إلى الصفحة الرئيسية أو صفحة أخرى.
    header("Location: index.php");
    exit();
}

// استعلام لجلب السجلات من جدول ActivityLog
$sql = "SELECT al.log_id, al.ticket_id, t.title, al.updated_by, u.username, al.change_description, al.timestamp 
        FROM ActivityLog al
        JOIN Tickets t ON al.ticket_id = t.ticket_id
        JOIN Users u ON al.updated_by = u.user_id
        ORDER BY al.timestamp DESC";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Header Section -->
    <header class="bg-primary text-white py-3">
        <div class="container d-flex justify-content-between align-items-center" style="flex-direction: column;">
            <h1 class="mb-0" style="margin-bottom: 10px !important;">Ticketing System for Electric Grid</h1>

            <div class="d-flex align-items-center">
                <!-- User's Name -->
                <span class="mr-3">Welcome, <?php echo $_SESSION['username']; ?></span>

                <!-- Admin Activity Log link -->
                <?php if ($_SESSION['role'] == 'super'): ?>
                    <a href="employee_management.php" class="btn btn-light btn-sm">Employee Management</a>
                <?php endif; ?>

                <!-- Admin Activity Log link -->
                <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'super'): ?>
                    <a href="ticket_assignments.php" class="btn btn-light btn-sm">Ticket Assignments</a>
                <?php endif; ?>

                <!-- Admin Activity Log link -->
                <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'super'): ?>
                    <a href="activity_log.php" class="btn btn-light btn-sm mr-3">Activity Log</a>
                <?php endif; ?>

                <!-- Logout link -->
                <a href="index.php" class="btn btn-success btn-sm">Home</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <h2 class="mb-4" style="margin-top:20px;">Activity Log for Tickets</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Ticket ID</th>
                    <th>Ticket Title</th>
                    <th>Updated By</th>
                    <th>Change Description</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $log['log_id']; ?></td>
                        <td><?php echo $log['ticket_id']; ?></td>
                        <td><?php echo $log['title']; ?></td>
                        <td><?php echo $log['username']; ?></td>
                        <td><?php echo $log['change_description']; ?></td>
                        <td><?php echo $log['timestamp']->format('Y-m-d H:i:s'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
    <footer>
        <p class="text-center">Copyright &copy; 2024. All rights reserved by TAHA-MONER.</p>
    </footer>
</body>

</html>

<?php
// تحرير الاستعلامات عند الانتهاء
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>