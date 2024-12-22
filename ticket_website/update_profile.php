<?php
session_start();
include 'db.php';
$error = "";
include 'activity_log_functions.php';
include 'security_xss.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $username = sanitize_output($_POST['username']);
    $email = sanitize_output($_POST['email']);
    $password = sanitize_output($_POST['password']);
    $hashed_password = bin2hex(hash('sha256', $password, true));

    $sql = "UPDATE Users SET username = ?, email = ?, password = ? WHERE user_id = ?";
    $params = array($username, $email, $hashed_password, $user_id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        $success = "Profile updated successfully.";
        ActivityUser($conn, $user_id, 'Updated profile');
    }
    sqlsrv_free_stmt($stmt);
}

$sql = "SELECT * FROM Users WHERE user_id = ?";
$params = array($_SESSION['user_id']);
$stmt = sqlsrv_query($conn, $sql, $params);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <header class="bg-primary text-white text-center py-5 mb-4">
        <h1>Update Profile</h1>
    </header>
    <main class="container">
        <h2 class="mb-4">Update Profile</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control"
                    value="<?php echo $user['username']; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo $user['email']; ?>"
                    required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
        <?php
        if (isset($success)) {
            echo "<p class='text-success'>$success</p>";
        }
        if (isset($error)) {
            echo $error;
        }
        ?>
    </main>
</body>

</html>