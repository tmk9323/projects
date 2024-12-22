<?php
include 'session_config.php';
session_start();
include 'db.php';
include 'security_xss.php';
$error = "";
include 'activity_log_functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_output($_POST['username']);
    $password = sanitize_output($_POST['password']);

    // تشفير كلمة المرور بنفس طريقة HASHBYTES في SQL Server
    $hashed_password = bin2hex(hash('sha256', $password, true));

    // استعلام SQL للتحقق من المستخدم وكلمة المرور فقط
    $sql = "SELECT * FROM Users WHERE username = ? AND password = ? ";

    $params = array($username, $hashed_password);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($user) {
        if ($user['is_active'] == 0) {
            // التحقق من حالة الحساب بعد الحصول على بيانات المستخدم
            $error = "<p style='background-color: #ff5656;color: white;text-align: center;padding: 10px;margin-top:15px;border-radius: .25rem;'>Account is disabled.</p>";
        } else {
            // تخزين بيانات المستخدم في الجلسة
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // تسجيل عملية الدخول في سجل النشاطات
            ActivityUser($conn, $user['user_id'], 'Logged in');

            // إعادة التوجيه إلى الصفحة الرئيسية
            header("Location: index.php");
            exit(); // تأكد من إضافة exit() بعد التوجيه لتجنب تنفيذ كود إضافي

            // // توليد رمز التحقق وإرساله عبر البريد الإلكتروني
            // $verification_code = rand(100000, 999999);
            // $_SESSION['verification_code'] = $verification_code;

            // // إرسال البريد الإلكتروني
            // $to = $user['email'];
            // $subject = "Verification Code";
            // $message = "Your verification code is: $verification_code";
            // $headers = "From: no-reply@yourdomain.com\r\n";
            // $headers .= "Reply-To: no-reply@yourdomain.com\r\n";
            // $headers .= "Content-Type: text/plain; charset=UTF-8\r\n"; // تحديد نوع المحتوى كـ text/plain

            // // إرسال البريد الإلكتروني باستخدام mail()
            // if (mail($to, $subject, $message, $headers)) {
            //     // إعادة التوجيه إلى صفحة التحقق
            //     header("Location: verify.php");
            // } else {
            //     $error = "<p style='background-color: #ff5656;color: white;text-align: center;padding: 10px;margin-top:15px;border-radius: .25rem;'>Error sending verification email.</p>";
            // }

        }
    } else {
        $error = "<p style='background-color: #ff5656;color: white;text-align: center;padding: 10px;margin-top:15px;border-radius: .25rem;'>Invalid username or password.</p>";
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <header class="bg-primary text-white text-center py-5 mb-4">
        <h1>Login</h1>
    </header>
    <main class="container">
        <h2 class="mb-4">Login</h2>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <?php echo $error; ?>
    </main>
</body>

</html>