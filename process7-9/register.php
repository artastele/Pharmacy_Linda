<?php
require_once "config/database.php";
require_once "includes/common.php";

ensure_session_started();
if (!empty($_SESSION["user_id"])) {
    redirect_to_role_home();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = sanitize_input($_POST["first_name"] ?? "");
    $middleName = sanitize_input($_POST["middle_name"] ?? "");
    $lastName = sanitize_input($_POST["last_name"] ?? "");
    $email = sanitize_input($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = sanitize_input($_POST["role"] ?? "");

    if ($firstName === "" || $lastName === "" || $email === "" || $password === "" || !is_valid_role($role)) {
        $error = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $fullName = trim($firstName . " " . ($middleName !== "" ? $middleName . " " : "") . $lastName);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        $alreadyExists = $checkStmt->num_rows > 0;
        $checkStmt->close();

        if ($alreadyExists) {
            $error = "Email is already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, full_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $firstName, $middleName, $lastName, $fullName, $email, $passwordHash, $role);
            $stmt->execute();
            $stmt->close();

            header("Location: login.php?message=Registration successful. Please log in.");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="card auth-card">
            <h1>Create Account</h1>
            <p>Create your account to access the internship system.</p>
            <?php show_message(); ?>
            <?php if ($error !== ""): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post">
                <label>First Name:</label>
                <input type="text" name="first_name" required>

                <label>Middle Name (Optional):</label>
                <input type="text" name="middle_name">

                <label>Last Name:</label>
                <input type="text" name="last_name" required>

                <label>Email:</label>
                <input type="email" name="email" required>

                <label>Password:</label>
                <input type="password" name="password" required minlength="6">

                <label>Choose Role:</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="hr_personnel">HR Personnel</option>
                    <option value="intern">Intern</option>
                </select>

                <button type="submit">Register</button>
            </form>
            <p>Already registered? <a href="login.php">Log in here</a>.</p>
        </div>
    </div>
</body>
</html>
