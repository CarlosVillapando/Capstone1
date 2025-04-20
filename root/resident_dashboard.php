<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT first_name, last_name, email, address, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch report summary (based on full table for now)
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT 
    COUNT(*) AS total_reports,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending
  FROM issues
"));
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="resident_dashboard.css" />
  <title>StreetFix Resident Dashboard</title>
</head>
<body>
  <div class="sidebar">
    <h2>STREETFIX</h2>
    <ul>
      <li class="active"><a href="#">🏠 Dashboard</a></li>
      <li><a href="resident_announcement.php">🔔 Announcements</a></li>
      <li><a href="#">📈 Report & Analytics</a></li>
    </ul>
  </div>

  <div class="main-content">
    <header>
      <div class="user-dropdown" onclick="toggleDropdown()">
        <div class="user-info">
          <span class="avatar">👤</span>
          <span><?php echo htmlspecialchars($user['first_name']); ?></span>
          <span class="dropdown">▾</span>
        </div>
        <div id="userDropdown" class="dropdown-content">
          <a href="#" id="viewProfileLink">👤 View Profile</a>
          <a href="logout.php">🚪 Logout</a>
        </div>
      </div>
    </header>

    <section class="dashboard-overview">
      <h2>Dashboard Overview</h2>
      <div class="overview-cards">
        <div class="card"><h3>Total Reports</h3><p><?= $summary['total_reports'] ?? 0 ?></p></div>
        <div class="card"><h3>Resolved</h3><p><?= $summary['resolved'] ?? 0 ?></p></div>
        <div class="card"><h3>Pending</h3><p><?= $summary['pending'] ?? 0 ?></p></div>

      </div>
    </section>

    <section class="recent-reports">
      <h2>Recent Reports</h2>
      <table>
        <thead>
          <tr>
            <th>Issue</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Pothole on 5th Ave</td>
            <td>Resolved</td>
            <td>Apr 2, 2025</td>
          </tr>
          <tr>
            <td>Clogged Drain</td>
            <td>Pending</td>
            <td>Apr 3, 2025</td>
          </tr>
          <tr>
            <td>Broken Street Light</td>
            <td>Resolved</td>
            <td>Apr 1, 2025</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>

  <div id="profileModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Your Profile</h2>
      <div class="profile-info">
        <div class="profile-avatar">👤</div>
        <div class="profile-details">
          <p><strong>Name:</strong> <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
          <?php if (!empty($user['address'])): ?>
          <p><strong>Address:</strong> <?= htmlspecialchars($user['address']) ?></p>
          <?php endif; ?>
          <p><strong>Member Since:</strong> <?= date("F Y", strtotime($user['created_at'])) ?></p>
        </div>
      </div>
      <div class="profile-actions">
        <button class="close-btn">Close</button>
      </div>
    </div>
  </div>

  <script>
    function toggleDropdown() {
      document.getElementById("userDropdown").classList.toggle("show");
    }

    const modal = document.getElementById("profileModal");
    const viewProfileLink = document.getElementById("viewProfileLink");
    const closeBtn = document.querySelector(".close");
    const closeModalBtn = document.querySelector(".close-btn");

    viewProfileLink.addEventListener("click", function(e) {
      e.preventDefault();
      modal.style.display = "block";
      document.getElementById("userDropdown").classList.remove("show");
    });

    closeBtn.addEventListener("click", function() {
      modal.style.display = "none";
    });

    closeModalBtn.addEventListener("click", function() {
      modal.style.display = "none";
    });

    window.addEventListener("click", function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
      if (!event.target.matches('.user-dropdown') && !event.target.closest('.user-dropdown')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
          if (dropdowns[i].classList.contains("show")) {
            dropdowns[i].classList.remove("show");
          }
        }
      }
    });
  </script>
</body>
</html>
