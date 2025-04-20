<?php
session_start();
require 'db.php';

// Get user info (optional, based on session)
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Admin';

// Fetch recent activities
$activityResult = mysqli_query($conn, "
  SELECT description, created_at 
  FROM user_activity 
  ORDER BY created_at DESC 
  LIMIT 10
");

// Live Issue Counts
$countQuery = "
  SELECT 
    COUNT(*) AS total_issues,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_issues,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_issues
  FROM issues
";
$countResult = mysqli_query($conn, $countQuery);
$counts = mysqli_fetch_assoc($countResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>StreetFix Admin Dashboard</title>
  <link rel="stylesheet" href="dashboard.css" />
</head>
<body>
  <div class="sidebar">
    <h2>STREETFIX</h2>
    <ul>
        <li class="active"><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="progress_tracking.php">🚲 Progress Tracking</a></li>
        <li><a href="announcement.php">🔔 Announcements</a></li>
        <li><a href="reports_analytics.php">📈 Reports & Analytics</a></li>
    </ul>
  </div>

  <div class="main-content">
    <header>
      <div class="user-dropdown" onclick="toggleDropdown()">
        <div class="user-info">
          <span class="user-name">👤 Admin</span>
          <span class="dropdown">▾</span>
        </div>
        <div id="userDropdown" class="dropdown-content">
          <a href="logout.php">🚪 Logout</a>
        </div>
      </div>
    </header>

    <section class="dashboard">
      <h2>Dashboard Overview</h2>
      <div class="overview">
        <div class="box blue">Total Issues<br><strong><?= $counts['total_issues'] ?? 0 ?></strong></div>
        <div class="box green">Resolved Issues<br><strong><?= $counts['resolved_issues'] ?? 0 ?></strong></div>
        <div class="box yellow">Pending Issues<br><strong><?= $counts['pending_issues'] ?? 0 ?></strong></div>
      </div>

      <div class="reports">
        <h3>Recent Reports</h3>
        <table>
          <thead>
            <tr>
              <th>Issue Code</th>
              <th>Type of Issue</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>ISSUE-247</td><td>Pothole</td><td><span class="status pending">Pending</span></td></tr>
            <tr><td>ISSUE-238</td><td>Broken Streetlight</td><td><span class="status progress">In Progress</span></td></tr>
            <tr><td>ISSUE-235</td><td>Pothole</td><td><span class="status resolved">Resolved</span></td></tr>
            <tr><td>ISSUE-228</td><td>Road Obstruction</td><td><span class="status resolved">Resolved</span></td></tr>
            <tr><td>ISSUE-246</td><td>Drainage Blockage</td><td><span class="status pending">Pending</span></td></tr>
          </tbody>
        </table>
      </div>

      <div class="activities">
        <h3>Recent Activities</h3>
        <ul>
          <?php while ($row = mysqli_fetch_assoc($activityResult)): ?>
            <?php
              $datetime = new DateTime($row['created_at'], new DateTimeZone('UTC'));
              $datetime->setTimezone(new DateTimeZone('Asia/Manila'));
            ?>
            <li><?= $datetime->format("h:i A") ?> - <?= htmlspecialchars($row['description']) ?></li>
          <?php endwhile; ?>
        </ul>
      </div>
    </section>
  </div>

  <script>
    function toggleDropdown() {
      document.getElementById("userDropdown").classList.toggle("show");
    }

    window.addEventListener("click", function(event) {
      if (!event.target.matches('.user-dropdown') && !event.target.closest('.user-dropdown')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
          if (dropdowns[i].classList.contains('show')) {
            dropdowns[i].classList.remove('show');
          }
        }
      }
    });
  </script>
</body>
</html>
