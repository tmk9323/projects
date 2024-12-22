<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'security_xss.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $title = sanitize_output($_POST['title']);
    $description = sanitize_output($_POST['description']);
    $status = sanitize_output($_POST['status']);
    $category = sanitize_output($_POST['category']);
    $priority = sanitize_output($_POST['priority']);
    $assigned_to = ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Technician' || $_SESSION['role'] == 'super') ? sanitize_output($_POST['assigned_to']) : null;
    $area_id = sanitize_output($_POST['area_id']);

    // إدخال التذكرة في جدول Tickets
    $sql = "INSERT INTO Tickets (user_id, title, description, status,category, priority, assigned_to, area_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $params = array($user_id, $title, $description, $status, $category, $priority, $assigned_to, $area_id);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die("Error: Unable to insert ticket. " . print_r(sqlsrv_errors(), true));
    }

    // استرجاع ticket_id
    $sql_get_id = "SELECT MAX(ticket_id) AS ticket_id FROM Tickets WHERE user_id = ?";
    $params_get_id = array($user_id);
    $stmt_get_id = sqlsrv_query($conn, $sql_get_id, $params_get_id);

    if ($stmt_get_id === false) {
        die("Error: Unable to retrieve ticket_id. " . print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($stmt_get_id, SQLSRV_FETCH_ASSOC);

    if (!$row || !isset($row['ticket_id'])) {
        die("Error: Unable to retrieve ticket_id.");
    }

    $ticket_id = $row['ticket_id'];

    // إضافة سجل إلى جدول TicketAssignments إذا تم تعيين فني
    if ($assigned_to) {
        $sql_assignment = "INSERT INTO TicketAssignments (ticket_id, technician_id)
                           VALUES (?, ?)";
        $params_assignment = array($ticket_id, $assigned_to);
        $stmt_assignment = sqlsrv_query($conn, $sql_assignment, $params_assignment);

        if ($stmt_assignment === false) {
            die("Error: Unable to insert into TicketAssignments. " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmt_assignment);
    }

    header("Location: index.php");
    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt($stmt_get_id);
    sqlsrv_close($conn);
}

// جلب الفنيين والمناطق فقط إذا كان المستخدم Admin أو Technician
if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Technician' || $_SESSION['role'] == 'super') {
    $sql_technicians = "SELECT technician_id, name FROM Technicians";
    $stmt_technicians = sqlsrv_query($conn, $sql_technicians);
}
$sql_areas = "SELECT area_id, area_name FROM Areas";
$stmt_areas = sqlsrv_query($conn, $sql_areas);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ticket</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- إضافة مكتبة AOS الخاصة بالأنيميشن عند التمرير -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
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
        <h2 class="mb-4" style="margin-top:20px;">Add new ticket</h2>
        <form method="POST" action="add_ticket.php">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>

            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" class="form-control">
                    <option value="Routine Maintenance">Routine Maintenance</option>
                    <option value="Emergency">Emergency</option>
                    <option value="Power Outage">Power Outage</option>
                    <option value="Equipment Repair">Equipment Repair</option>
                    <option value="Customer Complaints">Customer Complaints</option>
                    <option value="Network Upgrades">Network Upgrades</option>
                    <option value="Billing Issues">Billing Issues</option>
                    <option value="Safety">Safety</option>
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Priority:</label>
                <select id="priority" name="priority" class="form-control" required>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

            <!-- إظهار خيار Assign to Technician فقط إذا كان المستخدم Admin أو Technician -->
            <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Technician' || $_SESSION['role'] == 'super'): ?>
                <div class="form-group">
                    <label for="assigned_to">Assign to Technician:</label>
                    <select id="assigned_to" name="assigned_to" class="form-control" required>
                        <?php while ($technician = sqlsrv_fetch_array($stmt_technicians, SQLSRV_FETCH_ASSOC)): ?>
                            <option value="<?php echo $technician['technician_id']; ?>"><?php echo $technician['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="area_id">Assign to Area:</label>
                <select id="area_id" name="area_id" class="form-control" required>
                    <?php while ($area = sqlsrv_fetch_array($stmt_areas, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?php echo $area['area_id']; ?>"><?php echo $area['area_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Create Ticket</button>
        </form>

    </main>

    <script>
        AOS.init();
    </script>

</body>

</html>

<?php
// تحرير الاستعلامات
if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Technician' || $_SESSION['role'] == 'super') {
    sqlsrv_free_stmt($stmt_technicians);
}
sqlsrv_free_stmt($stmt_areas);
sqlsrv_close($conn);
?>