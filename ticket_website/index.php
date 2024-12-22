<?php
session_start();

// تعطيل الجلسة بعد فترة من عدم النشاط
$inactive = 86400;
if (isset($_SESSION['timeout'])) {
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
}
$_SESSION['timeout'] = time();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'security_xss.php'; // Security XSS protection
// تعديل الاستعلام ليشمل الفنيين من جدول Technicians والمناطق من جدول Areas
$sql = "SELECT t.ticket_id, t.title, t.description, t.status, t.category, t.priority, 
               t.assigned_to, 
               ISNULL(te.name, 'Not Assigned') AS assigned_technician,  
               a.area_name
        FROM Tickets t
        LEFT JOIN Technicians te ON t.assigned_to = te.technician_id  
        LEFT JOIN Areas a ON t.area_id = a.area_id  
        WHERE 1=1";

$params = array();

if (isset($_GET['search']) && $_GET['search'] != '') {
    $search = $_GET['search'];
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR te.name LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
// الشروط الأخرى مثل تصفية الحالة والفئة والأولوية
if (isset($_GET['status']) && $_GET['status'] != '') {
    $status_filter = $_GET['status'];
    $sql .= " AND t.status = ?";
    $params[] = $status_filter;
}

if (isset($_GET['category']) && $_GET['category'] != '') {
    $category_filter = $_GET['category'];
    $sql .= " AND t.category = ?";
    $params[] = $category_filter;
}

if (isset($_GET['priority']) && $_GET['priority'] != '') {
    $priority_filter = $_GET['priority'];
    $sql .= " AND t.priority = ?";
    $params[] = $priority_filter;
}

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        .swal2-cancel,
        .swal2-confirm {
            outline: none !important;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header class="bg-primary text-white py-3">
        <div class="container d-flex justify-content-between align-items-center" style="flex-direction: column;">
            <h1 class="mb-0" style="margin-bottom: 10px !important;">Ticketing System for Electric Grid</h1>

            <div class="d-flex align-items-center">
                <!-- User's Name -->
                <span class="mr-3">Welcome, <?php echo $_SESSION['username']; ?></span>

                <!-- Display buttons only for super role -->
                <?php if ($_SESSION['role'] == 'super'): ?>
                    <a href="employee_management.php" class="btn btn-light btn-sm">Employee Management</a>
                    <a href="admin_users.php" class="btn btn-light btn-sm">Admin Users</a>
                    <a href="view_activity_log.php" class="btn btn-light btn-sm">View Activity Log</a>
                <?php endif; ?>

                <!-- Admin Ticket Assignments link -->
                <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'super'): ?>
                    <a href="ticket_assignments.php" class="btn btn-light btn-sm">Ticket Assignments</a>
                <?php endif; ?>

                <!-- Admin Activity Log link -->
                <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'super'): ?>
                    <a href="activity_log.php" class="btn btn-light btn-sm mr-3">Activity Log</a>
                <?php endif; ?>

                <!-- Logout link -->
                <a href="update_profile.php" class="btn btn-light btn-sm">Update Profile</a>
                <a href="index.php" class="btn btn-success btn-sm">Home</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <h2 class="mb-4" style="margin-top:20px;">Tickets</h2>
        <!-- Search Form -->
        <form method="GET" class="form-inline mb-4">
            <input type="text" name="search" class="form-control mr-2" placeholder="Search for tickets">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <form method="GET" class="form-inline mb-4" style="box-shadow: none; border: 1px solid #ddd;">
            <div class="form-group mr-2">
                <label for="status" class="mr-2">Status:</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Open" <?php if ($status_filter == 'Open')
                        echo 'selected'; ?>>Open</option>
                    <option value="In Progress" <?php if ($status_filter == 'In Progress')
                        echo 'selected'; ?>>In Progress
                    </option>
                    <option value="Closed" <?php if ($status_filter == 'Closed')
                        echo 'selected'; ?>>Closed</option>
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="category" class="mr-2">Category:</label>
                <select id="category" name="category" class="form-control">
                    <option value="">All</option>
                    <option value="Routine Maintenance" <?php if ($category_filter == 'Routine Maintenance')
                        echo 'selected'; ?>>Routine Maintenance
                    </option>
                    <option value="Emergency" <?php if ($category_filter == 'Emergency')
                        echo 'selected'; ?>>Emergency
                    </option>
                    <option value="Power Outage" <?php if ($category_filter == 'Power Outage')
                        echo 'selected'; ?>>Power
                        Outage
                    </option>
                    <option value="Equipment Repair" <?php if ($category_filter == 'Equipment Repair')
                        echo 'selected'; ?>>Equipment Repair
                    </option>
                    <option value="Customer Complaints" <?php if ($category_filter == 'Customer Complaints')
                        echo 'selected'; ?>>Customer Complaints
                    </option>
                    <option value="Network Upgrades" <?php if ($category_filter == 'Network Upgrades')
                        echo 'selected'; ?>>Network Upgrades
                    </option>
                    <option value="Billing Issues" <?php if ($category_filter == 'Billing Issues')
                        echo 'selected'; ?>>Billing Issues</option>
                    <option value="Safety" <?php if ($category_filter == 'Safety')
                        echo 'selected'; ?>>Safety</option>
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="priority" class="mr-2">Priority:</label>
                <select id="priority" name="priority" class="form-control">
                    <option value="">All</option>
                    <option value="Low" <?php if ($priority_filter == 'Low')
                        echo 'selected'; ?>>Low</option>
                    <option value="Medium" <?php if ($priority_filter == 'Medium')
                        echo 'selected'; ?>>Medium</option>
                    <option value="High" <?php if ($priority_filter == 'High')
                        echo 'selected'; ?>>High</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Assigned Technician</th>
                    <th>Area</th> <!-- إضافة عمود المنطقة -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php


                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . (isset($row['ticket_id']) ? $row['ticket_id'] : 'N/A') . "</td>";
                    echo "<td>" . (isset($row['title']) ? $row['title'] : 'N/A') . "</td>";
                    echo "<td>" . (isset($row['description']) ? $row['description'] : 'N/A') . "</td>";
                    echo "<td>" . (isset($row['status']) ? $row['status'] : 'N/A') . "</td>";
                    echo "<td>" . (isset($row['category']) ? $row['category'] : 'N/A') . "</td>";
                    echo "<td>" . (isset($row['priority']) ? $row['priority'] : 'N/A') . "</td>";
                    echo "<td>" . (isset($row['assigned_technician']) ? $row['assigned_technician'] : 'N/A') . "</td>";
                    echo "<td>" . (isset($row['area_name']) ? $row['area_name'] : 'N/A') . "</td>";
                    echo "<td>";
                    if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Technician' || $_SESSION['role'] == 'super') {
                        echo "<a href='update_ticket.php?id=" . $row['ticket_id'] . "' class='btn btn-warning'>Edit</a>";
                        if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'super') {
                            echo "<button class='btn-delete-ticket btn btn-danger' data-id='" . $row['ticket_id'] . "'>Delete</button>";
                        }
                    }
                    echo "</td>";
                    echo "</tr>";
                }


                ?>
            </tbody>
        </table>
        <?php
        // إحصائيات التذاكر
        $sql = "SELECT status, COUNT(*) as count FROM Tickets GROUP BY status";
        $stmt = sqlsrv_query($conn, $sql);
        $ticket_stats = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $ticket_stats[$row['status']] = $row['count'];
        }

        // عرض الإحصائيات في الواجهة
        ?>
        <div class="ticket-stats">
            <h3>Ticket Statistics</h3>
            <ul>
                <?php foreach ($ticket_stats as $status => $count): ?>
                    <li><?php echo sanitize_output($status) . ': ' . $count; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <a href="add_ticket.php" class="btn btn-primary">Create New Ticket</a>
    </main>
    <footer>
        <p class="text-center">Copyright &copy; 2024. All rights reserved by TAHA-MONER.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.querySelectorAll('.btn-delete-ticket').forEach(button => {
            button.addEventListener('click', function () {
                const ticketId = this.getAttribute('data-id');
                Swal.fire({
                    title: 'Are you sure you want to delete this ticket?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#ffc107',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // تنفيذ طلب الحذف باستخدام Fetch API
                        fetch(`delete_ticket.php?id=${ticketId}`, {
                            method: 'GET', // يمكن أيضًا استخدام POST إذا كان الأمر يتطلب
                        })
                            .then(response => response.text())  // استلام رد الخادم
                            .then(data => {
                                if (data.trim() === 'success') {
                                    // إذا تم الحذف بنجاح
                                    Swal.fire(
                                        'Deleted!',
                                        'The ticket has been deleted.',
                                        'success'
                                    ).then(() => {
                                        location.reload(); // إعادة تحميل الصفحة بعد الحذف
                                    });
                                } else {
                                    // إذا حدث خطأ في الحذف
                                    Swal.fire(
                                        'Error!',
                                        'There was a problem deleting the ticket.',
                                        'error'
                                    );
                                }
                            })
                            .catch(error => {
                                // في حال حدوث خطأ في الاتصال
                                Swal.fire(
                                    'Error!',
                                    'There was a problem with the request.',
                                    'error'
                                );
                            });
                    }
                });
            });
        });
    </script>
    <script>
        AOS.init();  
    </script>
</body>

</html>

<?php
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>