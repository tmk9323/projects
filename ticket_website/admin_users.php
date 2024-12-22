
<?php
session_start();
include 'db.php';
include 'activity_log_functions.php';
include 'security_xss.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'super') {
    header("Location: login.php");
    exit();
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $action = sanitize_output($_POST['action']);
    $status = ($action == 'enable') ? 1 : 0;

    $sql = "UPDATE Users SET is_active = ? WHERE user_id = ?";
    $params = array($status, $user_id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        ActivityUser($conn, $_SESSION['user_id'], ($action == 'enable') ? "Enabled user $user_id" : "Disabled user $user_id");
    }
    sqlsrv_free_stmt($stmt);
}

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
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <header class="bg-primary text-white text-center py-5 mb-4">
        <h1>Manage Users</h1>
    </header>
    <main class="container">
        <h2 class="mb-4">Users</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['is_active'] == 1 ? 'Enabled' : 'Disabled'; ?></td>
                        <td>
                            <form method="POST" action="admin_users.php">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <?php if ($user['is_active'] == 1) { ?>
                                    <button type="submit" name="action" value="disable" class="btn btn-danger">Disable</button>
                                <?php } else { ?>
                                    <button type="submit" name="action" value="enable" class="btn btn-success">Enable</button>
                                <?php } ?>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</body>

</html>
<?php sqlsrv_free_stmt($stmt); ?>