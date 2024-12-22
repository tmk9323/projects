<?php
session_start();
include 'db.php';
include 'security_xss.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_output($_POST['name']);
    $email = sanitize_output($_POST['email']);
    $username = sanitize_output($_POST['username']);
    $password = sanitize_output($_POST['password']);
    $role = sanitize_output($_POST['role']);
    $phone = sanitize_output($_POST['phone']);
    $address = sanitize_output($_POST['address']);

    // تشفير كلمة المرور باستخدام SHA2_256 بنفس الطريقة المستخدمة في قاعدة البيانات
    $hashed_password = bin2hex(hash('sha256', $password, true));

    try {
        // استعلام التحقق من تكرار اسم المستخدم أو البريد الإلكتروني
        $check_sql = "SELECT * FROM Users WHERE username = ? OR email = ?";
        $check_params = array($username, $email);
        $check_stmt = sqlsrv_query($conn, $check_sql, $check_params);

        if ($check_stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        } else {
            if (sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC)) {
                // إذا كان اسم المستخدم أو البريد الإلكتروني موجود بالفعل
                $_SESSION['error'] = "Username or email already exists.";
            } else {
                // استعلام الإضافة
                $sql = "INSERT INTO Users (name, email, username, password, role, phone, address) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = array($name, $email, $username, $hashed_password, $role, $phone, $address);
                $stmt = sqlsrv_query($conn, $sql, $params);

                if ($stmt === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                } else {
                    $_SESSION['success'] = "User added successfully.";
                    header("Location: employee_management.php");
                    exit(); // تأكد من إيقاف تنفيذ السكربت بعد التوجيه
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    } finally {
        if (isset($check_stmt) && is_resource($check_stmt)) {
            sqlsrv_free_stmt($check_stmt);
        }
        if (isset($stmt) && is_resource($stmt)) {
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Users</title>
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
        <h2 class="mb-4" style="margin-top:20px;">Add User</h2>
        <p>Please fill out the form below to add a new user.</p>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-danger'>" . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="customer">Customer</option>
                    <option value="Technician">Technician</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Add User</button>
        </form>
    </main>
    <script>
        AOS.init();
    </script>
</body>

</html>