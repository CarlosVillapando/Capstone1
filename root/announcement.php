<?php
session_start();
require 'db.php';

// Handle CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $stmt = $conn->prepare("INSERT INTO announcements (title, date, category, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $date, $category, $description);
    $stmt->execute();
    $stmt->close();

    header("Location: announcement.php");
    exit();
}

// Fetch announcements
$result = mysqli_query($conn, "SELECT * FROM announcements WHERE archived = 0 ORDER BY date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>StreetFix Announcements</title>
  <link rel="stylesheet" href="announcement.css" />
</head>
<body>
  <div class="sidebar">
    <h2>STREETFIX</h2>
    <ul>
      <li><a href="dashboard.php">🏠 Dashboard</a></li>
      <li><a href="progress_tracking.php">🛠 Progress Tracking</a></li>
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

    <section class="announcements">
      <div class="top-bar">
        <h2>Announcements</h2>
        <div class="actions">
          <button class="create-btn">Create New Announcement</button>
        </div>
      </div>

      <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="announcement-card">
        <div class="announcement-header">
          <span class="badge <?= htmlspecialchars($row['category']) ?>">
            <?= ucfirst(str_replace('_', ' ', $row['category'])) ?>
          </span>
          <h3><?= htmlspecialchars($row['title']) ?></h3>
        </div>
        <p><strong>Date:</strong> <?= $row['date'] ?></p>
        <p><?= htmlspecialchars($row['description']) ?></p>
        <div class="buttons">
          <form method="POST" style="display:inline">
            <input type="hidden" name="archive_id" value="<?= $row['id'] ?>">
            <button type="submit" class="archive">Archive</button>
          </form>
        </div>
      </div>
      <?php endwhile; ?>
    </section>
  </div>

  <div id="announcementModal" class="modal">
    <div class="modal-content">
      <h2>Create New Announcement</h2>
      <form id="announcementForm" method="POST" action="announcement.php">
        <input type="hidden" name="action" value="create">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>

        <label for="date">Date</label>
        <input type="date" id="date" name="date" required>

        <label for="category">Category</label>
        <select id="category" name="category" required>
          <option value="infrastructure">Infrastructure Updates</option>
          <option value="emergency">Emergency Alerts</option>
          <option value="community">Community Updates</option>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" required></textarea>

        <div class="modal-actions">
          <button type="submit" class="submit-btn">Submit</button>
          <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleDropdown() {
      document.getElementById("userDropdown").classList.toggle("show");
    }

    const modal = document.getElementById("announcementModal");
    const createBtn = document.querySelector(".create-btn");
    createBtn.onclick = () => {
      modal.style.display = "block";
    };
    function closeModal() {
      modal.style.display = "none";
    }
    window.onclick = (e) => {
      if (e.target === modal) closeModal();
    };
  </script>
</body>
</html>
