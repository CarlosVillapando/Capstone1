<?php
session_start();
require 'db.php';

// Pagination setup
$limit = 3; // cards per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

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

// Handle EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $stmt = $conn->prepare("UPDATE announcements SET title = ?, date = ?, category = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $date, $category, $description, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: announcement.php");
    exit();
}

// Fetch total count for pagination
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM announcements WHERE archived = 0");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalAnnouncements = $totalRow['total'];
$totalPages = ceil($totalAnnouncements / $limit);

// Fetch paginated announcements
$result = mysqli_query($conn, "SELECT * FROM announcements WHERE archived = 0 ORDER BY date DESC LIMIT $limit OFFSET $offset");
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
          <form method="POST" action="announcement.php" style="display:inline">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input type="hidden" name="title" value="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>">
            <input type="hidden" name="date" value="<?= $row['date'] ?>">
            <input type="hidden" name="category" value="<?= $row['category'] ?>">
            <input type="hidden" name="description" value="<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>">
            <button type="submit" class="edit">Edit</button>
          </form>
          <form method="POST" style="display:inline">
            <input type="hidden" name="archive_id" value="<?= $row['id'] ?>">
            <button type="submit" class="archive">Archive</button>
          </form>
        </div>
      </div>
      <?php endwhile; ?>

      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>">&laquo; Prev</a>
        <?php endif; ?>
        <span>Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <div id="announcementModal" class="modal">
    <div class="modal-content">
      <h2 id="modalTitle">Create New Announcement</h2>
      <form id="announcementForm" method="POST" action="announcement.php">
        <input type="hidden" name="action" value="create" id="formAction">
        <input type="hidden" name="id" id="editId">
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
          <button type="submit" class="submit-btn">Save</button>
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
    const form = document.getElementById("announcementForm");

    createBtn.onclick = () => {
      document.getElementById("modalTitle").innerText = "Create New Announcement";
      document.getElementById("formAction").value = "create";
      document.getElementById("editId").value = "";
      form.reset();
      modal.style.display = "block";
    };

    function closeModal() {
      modal.style.display = "none";
    }

    window.onclick = (e) => {
      if (e.target === modal) closeModal();
    };

    document.querySelectorAll(".edit").forEach((btn) => {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const form = this.closest("form");
        document.getElementById("modalTitle").innerText = "Edit Announcement";
        document.getElementById("formAction").value = "edit";
        document.getElementById("editId").value = form.querySelector("input[name='id']").value;
        document.getElementById("title").value = form.querySelector("input[name='title']").value;
        document.getElementById("date").value = form.querySelector("input[name='date']").value;
        document.getElementById("category").value = form.querySelector("input[name='category']").value;
        document.getElementById("description").value = form.querySelector("input[name='description']").value;
        modal.style.display = "block";
      });
    });
  </script>
</body>
</html>
