<?php
require 'db.php'; // Connects to Railway MySQL

// Admin account details
$first_name = 'Admin';
$last_name = 'User';
$street_address = 'Barangay Hall';
$city = 'City';
$province = 'Province';
$zip = '0000';
$phone = '09123456789';
$email = 'admin@streetfix.com';
$role = 'admin';
$proof = 'admin.jpg'; // optional placeholder file name
$password = 'Admin123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone_number = ?");
$check->bind_param("ss", $email, $phone);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "✅ Admin account already exists.";
} else {
    // Insert new admin user
    $stmt = $conn->prepare("INSERT INTO users (
        first_name, last_name, street_address, city, state_province, postal_zip_code,
        phone_number, email, role, proof_of_residency, password_hash
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "sssssssssss",
        $first_name, $last_name, $street_address, $city, $province, $zip,
        $phone, $email, $role, $proof, $password_hash
    );

    if ($stmt->execute()) {
        echo "✅ Admin account created successfully.<br>Email: $email<br>Password: $password";
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}
$check->close();
$conn->close();
?>
