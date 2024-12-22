<?php
session_start();
if ($_SESSION['role'] != 'super') {
    header("Location: index.php");
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'db.php';

// Get all users from the database
$sql = "SELECT * FROM Users";
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
    <title>Ticket Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        .btn-d {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-d:hover {
            background-color: #c82333;
        }

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
        <h2 class="mb-4" style="margin-top:20px;">Tickets</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['role']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td><?php echo $row['address']; ?></td>
                        <td>

                            <?php if ($_SESSION['role'] == 'super'): ?>
                                <!-- زر التعديل يظهر لجميع الفنيين و Admin -->
                                <a href="update_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-warning">Edit</a>

                                <?php if ($_SESSION['role'] == 'super'): ?>
                                    <!-- زر الحذف يظهر فقط للمستخدمين الذين لديهم صلاحية Admin -->

                                    <button class="btn-d btn-danger btn" data-id="<?php echo $row['user_id']; ?>">Delete</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="add_user.php" class="btn btn-primary">Add New Employee</a>
    </main>
    <footer>
        <p class="text-center">Copyright &copy; 2024. All rights reserved by TAHA-MONER.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.querySelectorAll('.btn-d').forEach(button => {
            button.addEventListener('click', function () {
                const userId = this.getAttribute('data-id');
                Swal.fire({
                    title: 'Are you sure you want to delete the user?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#ffc107',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`delete_user.php?id=${userId}`, {
                            method: 'GET',
                        })
                            .then(response => response.text())
                            .then(data => {
                                if (data.trim() === 'success') {
                                    Swal.fire(
                                        'Deleted!',
                                        'The user has been deleted.',
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'There was a problem deleting the user.',
                                        'error'
                                    );
                                }
                            })
                            .catch(error => {
                                Swal.fire(
                                    'Error!',
                                    'There was a problem deleting the user.',
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