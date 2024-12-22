<?php
session_start();
include 'db.php';
include 'security_xss.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];

    // إذا كان الطلب POST، قم بتحديث الصلاحية
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $role = sanitize_output($_POST['role']);

        // استعلام تحديث الصلاحيات
        $update_query = "UPDATE Users SET role = ? WHERE user_id = ?";
        $params = array($role, $user_id);
        $stmt = sqlsrv_query($conn, $update_query, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        } else {
            header("Location: employee_management.php");


        }
        sqlsrv_free_stmt($stmt);



    }

    // جلب بيانات المستخدم لعرضها في النموذج
    $query = "SELECT * FROM Users WHERE user_id = ?";
    $params = array($user_id);
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    // التحقق من وجود المستخدم
    if (!$user) {
        die("User not found.");
    }

    sqlsrv_free_stmt($stmt);
} else {
    die("Invalid user ID.");
}

sqlsrv_close($conn);
?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- إضافة مكتبة AOS الخاصة بالأنيميشن عند التمرير -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <title>Update Role</title>
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
        <h2 class="mb-4" style="margin-top:20px;">Update Role for <?php echo htmlspecialchars($user['username']); ?>
        </h2>
        <form method="POST">
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="customer" <?php if ($user['role'] == 'customer')
                        echo 'selected'; ?>>Customer</option>
                    <option value="Technician" <?php if ($user['role'] == 'Technician')
                        echo 'selected'; ?>>Technician
                    </option>
                    <option value="Admin" <?php if ($user['role'] == 'Admin')
                        echo 'selected'; ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Role</button>
        </form>
    </main>
    <script>
        AOS.init();
    </script>
</body>

</html>