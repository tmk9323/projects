<?php
session_start();
include 'db.php';
$error = "";
include 'security_xss.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_code = sanitize_output($_POST['verification_code']);
    if ($input_code == $_SESSION['verification_code']) {
        // مسح رمز التحقق من الجلسة
        unset($_SESSION['verification_code']);
        header("Location: index.php");
    } else {
        $error = "<p style='background-color: #ff5656;color: white;text-align: center;padding: 10px;margin-top:15px;border-radius: .25rem;'>Invalid verification code.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <header class="bg-primary text-white text-center py-5 mb-4">
        <h1>Two-Factor Authentication</h1>
    </header>
    <main class="container">
        <h2 class="mb-4">Enter Verification Code</h2>
        <form method="POST" action="verify.php">
            <div class="form-group">
                <label for="verification_code">Verification Code:</label>
                <input type="text" id="verification_code" name="verification_code" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Verify</button>
        </form>
        <?php echo $error; ?>
    </main>
</body>

</html>