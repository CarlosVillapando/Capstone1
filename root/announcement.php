<?php
session_start();
require 'db.php'; // your db.php should have $conn = new mysqli(...)

// Handle creation of new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && !isset($_POST['edit_id'])) {
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

// Handle editing announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $stmt = $conn->prepare("UPDATE announcements SET title = ?, date = ?, category = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $date, $category, $description, $edit_id);
    $stmt->execute();
    $stmt->close();

    header("Location: announcement.php");
    exit();
}

// Handle archive request
if (isset($_POST['archive_id'])) {
    $archive_id = (int)$_POST['archive_id'];
    mysqli_query($conn, "UPDATE announcements SET archived = 1 WHERE id = $archive_id");
    header("Location: announcement.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM announcements WHERE archived = 0 ORDER BY date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StreetFix Announcements</title>
  <link rel="stylesheet" href="announcement.css">
</head>
<body>
<div class="sidebar">
  <h2>STREETFIX</h2>
  <ul>
    <li><a href="dashboard.php">🏠 Dashboard</a></li>
    <li><a href="progress_tracking.php">🛠 Progress Tracking</a></li>
    <li><a href="announcement.php" class="active">🔔 Announcements</a></li>
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
    <div class="announcement-card" data-id="<?= $row['id'] ?>">
      <div class="announcement-header">
        <span class="badge <?= htmlspecialchars($row['category']) ?>">
          <?= ucfirst(str_replace('_', ' ', $row['category'])) ?>
        </span>
        <h3><?= htmlspecialchars($row['title']) ?></h3>
      </div>
      <p><strong>Date:</strong> <?= $row['date'] ?></p>
      <p><?= htmlspecialchars($row['description']) ?></p>
      <div class="buttons">
        <form method="POST" style="display:inline;">
          <input type="hidden" name="archive_id" value="<?= $row['id'] ?>">
          <button type="submit" class="archive">Archive</button>
        </form>
        <button class="edit" 
          data-id="<?= $row['id'] ?>" 
          data-title="<?= htmlspecialchars($row['title']) ?>" 
          data-date="<?= $row['date'] ?>" 
          data-category="<?= $row['category'] ?>" 
          data-description="<?= htmlspecialchars($row['description']) ?>">
          Edit
        </button>
      </div>
    </div>
    <?php endwhile; ?>
  </section>
</div>

<!-- Modal Form -->
<div id="announcementModal" class="modal">
  <div class="modal-content">
    <h2>Create / Edit Announcement</h2>
    <form id="announcementForm" method="POST">
      <input type="hidden" id="edit_id" name="edit_id">
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
        <button type="submit">Save</button>
        <button type="button" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
  const modal = document.getElementById("announcementModal");
  const form = document.getElementById("announcementForm");

  document.querySelector(".create-btn").onclick = () => {
    form.reset();
    document.getElementById("edit_id").value = "";
    modal.style.display = "block";
  };

  function closeModal() {
    modal.style.display = "none";
  }

  window.onclick = (e) => {
    if (e.target === modal) closeModal();
  };

  document.querySelectorAll(".edit").forEach((btn) => {
    btn.onclick = () => {
      modal.style.display = "block";
      document.getElementById("edit_id").value = btn.dataset.id;
      document.getElementById("title").value = btn.dataset.title;
      document.getElementById("date").value = btn.dataset.date;
      document.getElementById("category").value = btn.dataset.category;
      document.getElementById("description").value = btn.dataset.description;
    };
  });
</script>
</body>
</html>
