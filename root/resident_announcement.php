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

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 3;
$offset = ($page - 1) * $limit;
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM announcements");
$totalRows = mysqli_fetch_assoc($totalQuery)['total'];
$totalPages = ceil($totalRows / $limit);

$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY date DESC LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="resident_announcement.css" />
  <title>StreetFix Resident Announcements</title>
</head>
<body>
  <div class="sidebar">
    <h2>STREETFIX</h2>
    <ul>
      <li><a href="resident_dashboard.php">🏠 Dashboard</a></li>
      <li class="active"><a href="resident_announcement.php">🔔 Announcements</a></li>
      <li><a href="reports_analytics.php">📈 Report & Analytics</a></li>
    </ul>
  </div>

  <div class="main-content">
    <header>
      <div class="user-dropdown" onclick="toggleDropdown()">
        <div class="user-info">
          <span class="avatar">👤</span>
          <span><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
          <span class="dropdown">▾</span>
        </div>
        <div id="userDropdown" class="dropdown-content">
          <a href="#" id="viewProfileLink">👤 View Profile</a>
          <a href="logout.php">🚪 Logout</a>
        </div>
      </div>
    </header>

    <section class="announcements-container">
      <h2>Announcements</h2>
      <?php if (mysqli_num_rows($announcements) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($announcements)): ?>
          <div class="announcement">
            <div class="announcement-meta">
              <strong><?= ucfirst($row['category']) ?></strong>
            </div>
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <p><strong>Date:</strong> <?= htmlspecialchars($row['date']) ?></p>
            <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-announcements">No announcements available.</div>
      <?php endif; ?>

      <div class="pagination" style="text-align:center; padding-top: 10px;">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>">⬅ Prev</a>
        <?php endif; ?>
        <span style="margin: 0 10px;">Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>">Next ➡</a>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <div id="profileModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="modal.style.display = 'none'">&times;</span>
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
        <button class="close-btn" onclick="modal.style.display = 'none'">Close</button>
      </div>
    </div>
  </div>

  <script>
    function toggleDropdown() {
      document.getElementById("userDropdown").classList.toggle("show");
    }

    const modal = document.getElementById("profileModal");
    const viewProfileLink = document.getElementById("viewProfileLink");

    viewProfileLink.addEventListener("click", function(e) {
      e.preventDefault();
      modal.style.display = "block";
      document.getElementById("userDropdown").classList.remove("show");
    });

    window.addEventListener("click", function(event) {
      if (event.target === modal) {
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
