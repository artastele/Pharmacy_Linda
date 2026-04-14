<?php
require_once "config/database.php";
require_once "includes/common.php";

ensure_session_started();
if (!empty($_SESSION["user_id"])) {
    redirect_to_role_home();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = sanitize_input($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user["password_hash"])) {
            $error = "Invalid email or password.";
        } else {
            $_SESSION["user_id"] = (int) $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            redirect_to_role_home("Welcome, " . $user["full_name"] . "!");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="card auth-card">
            <h1>Welcome Back</h1>
            <p>Use your registered account to continue.</p>
            <?php show_message(); ?>
            <?php if ($error !== ""): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post">
                <label>Email:</label>
                <input type="email" name="email" required>

                <label>Password:</label>
                <input type="password" name="password" required>

                <button type="submit">Log In</button>
            </form>
            <p>No account yet? <a href="register.php">Register here</a>.</p>
        </div>
    </div>
</body>
</html>
