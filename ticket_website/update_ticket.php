<?php
session_start();
include 'db.php';
include 'security_xss.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticket_id = $_POST['ticket_id'];
    $title = sanitize_output($_POST['title']);
    $description = sanitize_output($_POST['description']);
    $status = sanitize_output($_POST['status']);
    $category = sanitize_output($_POST['category']);
    $priority = sanitize_output($_POST['priority']);
    $area_id = $_POST['area_id']; // إضافة المنطقة
    $assigned_to = sanitize_output($_POST['assigned_to']); // إضافة تعيين الفني
    $current_assigned_to = sanitize_output($_POST['current_assigned_to']); // إضافة المتغير لمعرف الفني الحالي
    $user_id = $_SESSION['user_id'];  // الحصول على معرّف المستخدم الحالي (الذي يقوم بالتحديث)

    // تحديث بيانات التذكرة في جدول Tickets
    $sql = "UPDATE Tickets SET title = ?, description = ?, status = ?, category = ?, priority = ?, area_id = ?, assigned_to = ? WHERE ticket_id = ?";
    $params = array($title,$description,$status,$category, $priority, $area_id, $assigned_to, $ticket_id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // التحقق من تغيير الفني
    if ($assigned_to != $current_assigned_to) {
        // إدخال سجل جديد في جدول ticketassignments عند تغيير الفني
        $sql_assignment = "INSERT INTO ticketassignments (ticket_id, technician_id, completion_date) VALUES (?, ?, GETDATE())";
        $params_assignment = array($ticket_id, $assigned_to);
        $stmt_assignment = sqlsrv_query($conn, $sql_assignment, $params_assignment);

        if ($stmt_assignment === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // إضافة سجل في ActivityLog لتوثيق التغيير
        $change_description = "Assigned ticket to technician ID: $assigned_to";  // وصف التغيير
        $sql_log = "INSERT INTO ActivityLog (ticket_id, updated_by, change_description) VALUES (?, ?, ?)";
        $params_log = array($ticket_id, $user_id, $change_description);
        $stmt_log = sqlsrv_query($conn, $sql_log, $params_log);

        if ($stmt_log === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }

    // إعادة التوجيه إلى الصفحة الرئيسية بعد التحديث
    header("Location: index.php");

    // تحرير الاستعلامات عند الانتهاء
    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt($stmt_assignment);
    sqlsrv_free_stmt($stmt_log);
    sqlsrv_close($conn);
}

if (isset($_GET['id'])) {
    $ticket_id = $_GET['id'];
    $sql = "SELECT * FROM Tickets WHERE ticket_id = ?";
    $params = array($ticket_id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $ticket = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
} else {
    header("Location: index.php");
}

// جلب الفنيين والمناطق فقط إذا كان المستخدم Admin أو Technician
if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Technician' || $_SESSION['role'] == 'super') {
    $sql_technicians = "SELECT technician_id, name FROM Technicians";  // تعديل للاستعلام إلى Technicians
    $stmt_technicians = sqlsrv_query($conn, $sql_technicians);
    $sql_areas = "SELECT area_id, area_name FROM Areas";
    $stmt_areas = sqlsrv_query($conn, $sql_areas);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Ticket</title>
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
        <h2 class="mb-4" style="margin-top:20px;">Update Ticket Status</h2>
        <form method="POST" action="update_ticket.php">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
            <input type="hidden" name="current_assigned_to" value="<?php echo $ticket['assigned_to']; ?>">

            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo $ticket['title'] ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" class="form-control" rows="4" required><?php echo $ticket['description'] ?></textarea>
            </div>

            
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="Open" <?php if ($ticket['status'] == 'Open')
                        echo 'selected'; ?>>Open</option>
                    <option value="In Progress" <?php if ($ticket['status'] == 'In Progress')
                        echo 'selected'; ?>>In
                        Progress</option>
                    <option value="Closed" <?php if ($ticket['status'] == 'Closed')
                        echo 'selected'; ?>>Closed</option>
                </select>
            </div>
            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="Routine Maintenance" <?php if ($ticket['category'] == 'Routine Maintenance')
                        echo 'selected'; ?>>Routine Maintenance</option>
                    <option value="Emergency" <?php if ($ticket['category'] == 'Emergency')
                        echo 'selected'; ?>>Emergency</option>
                    <option value="Power Outage" <?php if ($ticket['category'] == 'Power Outage')
                        echo 'selected'; ?>>Power Outage</option>
                    <option value="Equipment Repair" <?php if ($ticket['category'] == 'Equipment Repair')
                        echo 'selected'; ?>>Equipment Repair</option>
                    <option value="Customer Complaints" <?php if ($ticket['category'] == 'Customer Complaints')
                        echo 'selected'; ?>>Customer Complaints</option>
                    <option value="Network Upgrades" <?php if ($ticket['category'] == 'Network Upgrades')
                        echo 'selected'; ?>>Network Upgrades</option>
                    <option value="Billing Issues" <?php if ($ticket['category'] == 'Billing Issues')
                        echo 'selected'; ?>>Billing Issues</option>
                    <option value="Safety" <?php if ($ticket['category'] == 'Safety')
                        echo 'selected'; ?>>Safety</option>
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Priority:</label>
                <select id="priority" name="priority" class="form-control" required>
                    <option value="Low" <?php if ($ticket['priority'] == 'Low')
                        echo 'selected'; ?>>Low</option>
                    <option value="Medium" <?php if ($ticket['priority'] == 'Medium')
                        echo 'selected'; ?>>Medium</option>
                    <option value="High" <?php if ($ticket['priority'] == 'High')
                        echo 'selected'; ?>>High</option>
                </select>
            </div>
            <div class="form-group">
                <label for="assigned_to">Assign to Technician:</label>
                <select id="assigned_to" name="assigned_to" class="form-control" required>
                    <?php while ($technician = sqlsrv_fetch_array($stmt_technicians, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?php echo $technician['technician_id']; ?>"
                            <?php echo ($ticket['assigned_to'] == $technician['technician_id']) ? 'selected' : ''; ?>>
                            <?php echo $technician['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="area_id">Assign to Area:</label>
                <select id="area_id" name="area_id" class="form-control" required>
                    <?php while ($area = sqlsrv_fetch_array($stmt_areas, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?php echo $area['area_id']; ?>"
                            <?php echo ($ticket['area_id'] == $area['area_id']) ? 'selected' : ''; ?>>
                            <?php echo $area['area_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Ticket</button>
        </form>
    </main>
</body>

</html>

<?php
// تحرير الاستعلامات عند الانتهاء
sqlsrv_free_stmt($stmt_technicians);
sqlsrv_free_stmt($stmt_areas);
sqlsrv_close($conn);
?>