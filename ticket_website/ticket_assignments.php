<?php
include 'db.php';
session_start();

// تحقق من أن المستخدم هو Admin
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] !== 'super') {
    header("Location: index.php"); // إعادة التوجيه إذا لم يكن Admin
    exit();
}

// جلب بيانات الـ ticketassignments
$sql = "SELECT ta.ticket_id, t.title, ta.technician_id, te.name AS technician_name, ta.assigned_at, ta.completion_date
        FROM ticketassignments ta
        JOIN Tickets t ON ta.ticket_id = t.ticket_id
        JOIN Technicians te ON ta.technician_id = te.technician_id";
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
    <title>Ticket Assignments</title>
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
        <h2 class="mb-4" style="margin-top:20px;">Assigned Tickets</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Ticket Title</th>
                    <th>Technician</th>
                    <th>Assigned At</th>
                    <th>Assigned Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($assignment = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $assignment['ticket_id']; ?></td>
                        <td><?php echo $assignment['title']; ?></td>
                        <td><?php echo $assignment['technician_name']; ?></td>
                        <td><?php echo $assignment['assigned_at']->format('Y-m-d H:i:s'); ?></td>
                        <td><?php if($assignment['completion_date'] != null){echo $assignment['completion_date']->format('Y-m-d H:i:s');}else{ echo "NULL"; } ?></td>
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