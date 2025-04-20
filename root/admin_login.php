<?php
session_start();
require 'db.php'; // your DB connection

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['password'])) {
    // Track failed login attempts
    if (!isset($_SESSION['admin_login_attempts'])) {
        $_SESSION['admin_login_attempts'] = 0;
    }

    if ($_SESSION['admin_login_attempts'] >= 3) {
        $error = "⚠️ Too many failed login attempts. Please try again later.";
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        // Lookup user with role = 'admin'
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone_number, password_hash FROM users 
                                WHERE (email = ? OR phone_number = ?) AND role = 'admin' LIMIT 1");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if admin user exists
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin['password_hash'])) {
                // ✅ Login success
                $_SESSION['admin_username'] = $admin['first_name'] . ' ' . $admin['last_name'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_login_attempts'] = 0;

                // Optional: Log login to user_activity
                $desc = "Admin: {$admin['first_name']} {$admin['last_name']} logged in.";
                $log = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description, role) VALUES (?, 'login', ?, 'admin')");
                $log->bind_param("is", $admin['id'], $desc);
                $log->execute();
                $log->close();

                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['admin_login_attempts']++;
                $error = "❌ Incorrect password.";
            }
        } else {
            $_SESSION['admin_login_attempts']++;
            $error = "❌ Admin account not found.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <div class="login-container">
    <h2>🔐 Admin Login</h2>

    <?php if (!empty($error)) : ?>
      <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="admin_login.php" method="POST">
      <label for="username">Email / Phone Number</label>
      <input type="text" id="username" name="username" placeholder="Enter admin email or phone" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter password" required>

      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
