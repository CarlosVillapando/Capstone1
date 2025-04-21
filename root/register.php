<?php
session_start();
require 'db.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $street_address = trim($_POST['street_address']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $zip = trim($_POST['zip']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone_number = ?");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Email or phone number already registered!";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // File upload (proof of residency)
            $proof_file = null;
            if (!empty($_FILES['proof']['name'])) {
                $target_dir = "uploads/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
            
                $imageFileType = strtolower(pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION));
                $allowedTypes = ["jpg", "jpeg", "png", "gif"];
            
                if (!in_array($imageFileType, $allowedTypes)) {
                    $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                } elseif ($_FILES["proof"]["size"] > 500000) {
                    $error = "File is too large. Maximum 500KB allowed.";
                } elseif (getimagesize($_FILES["proof"]["tmp_name"]) === false) {
                    $error = "Uploaded file is not a valid image.";
                } else {
                    $unique_filename = time() . "_" . basename($_FILES["proof"]["name"]);
                    $proof_file = $target_dir . $unique_filename;
                    if (!move_uploaded_file($_FILES["proof"]["tmp_name"], $proof_file)) {
                        $error = "Error uploading the file.";
                    }
                }
            }


            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, street_address, city, state_province, postal_zip_code, phone_number, email, role, proof_of_residency, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $first_name, $last_name, $street_address, $city, $province, $zip, $phone, $email, $role, $proof_file, $password_hash);

            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['user_name'] = $first_name . " " . $last_name;

                // ✅ Log activity
                $desc = "$role: $first_name $last_name created an account.";
                $log = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description, role) VALUES (?, 'register', ?, ?)");
                $log->bind_param("iss", $new_user_id, $desc, $role);
                $log->execute();
                $log->close();

                // Redirect to login
                header("Location: login.php?success=registered");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>
    
<!-- Registration Form with PHP Error Handling -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="register-container">
        <h2>Register Form</h2>
        <p class="sub-title">Account Details:</p>
        
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>

        <form action="register.php" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="input-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="input-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>

            <div class="input-group full">
                <label>Street Address</label>
                <input type="text" name="street_address" required>
            </div>

            <div class="form-row">
                <div class="input-group">
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>
                <div class="input-group">
                    <label>State / Province</label>
                    <input type="text" name="province" required>
                </div>
            </div>

            <div class="input-group full">
                <label>Postal / Zip Code</label>
                <input type="text" name="zip" required>
            </div>

            <div class="form-row">
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
            </div>

            <div class="form-row">
                <div class="input-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="Resident">Resident</option>
                        <option value="Barangay Official">Barangay Official</option>
                        <option value="Maintenance Team">Maintenance Team</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Proof of Residency</label>
                    <input type="file" name="proof" required>
                </div>
            </div>

            <div class="form-row">
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
            </div>

            <div class="submit-group">
                <button type="submit">Register</button>
                <p>Already have an account? <a href="login.php">Login Here</a></p>
            </div>
        </form>
    </div>
</body>
</html>
