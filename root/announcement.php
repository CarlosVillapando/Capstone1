<?php
session_start();
require 'db.php'; // your db.php should have $conn = new mysqli(...)

$result = mysqli_query($conn, "SELECT * FROM announcements ORDER BY date DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
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
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>STREETFIX</h2>
    <ul>
      <li><a href="dashboard.php">🏠 Dashboard</a></li>
      <li><a href="progress_tracking.php">🛠 Progress Tracking</a></li>
      <li><a href="announcement.html">🔔 Announcements</a></li>
      <li><a href="reports_analytics.php">📈 Reports & Analytics</a></li>
    </ul>
  </div>

  <!-- Main Content Wrapper -->
  <div class="main-content">

    <!-- Header (Admin Profile) -->
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

    <!-- Announcements Section -->
    <section class="announcements">
      <div class="top-bar">
        <h2>Announcements</h2>
        <div class="actions">
          <button class="create-btn">Create New Announcement</button>
          <select>
            <option>All Announcements</option>
            <option>Infrastructure Updates</option>
            <option>Emergency Alerts</option>
            <option>Community Updates</option>
          </select>
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
          <button class="archive">Archive</button>
          <button class="edit">Edit</button>
        </div>
      </div>
    <?php endwhile; ?>  

      <div class="pagination">
        <span>Page 1</span>
        <button disabled>Next</button>
      </div>
    </section>
  </div> <!-- End of .main-content -->

  <!-- Modal Form for Creating Announcement -->
  <div id="announcementModal" class="modal">
    <div class="modal-content">
      <h2>Create New Announcement</h2>
      <form id="announcementForm" method="POST" action="announcement.php">
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

  <!-- Scripts -->
  <script>
    function toggleDropdown() {
      document.getElementById("userDropdown").classList.toggle("show");
    }

    // Close dropdown on outside click
    window.addEventListener("click", function (event) {
      if (!event.target.matches('.user-dropdown') && !event.target.closest('.user-dropdown')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
          var openDropdown = dropdowns[i];
          if (openDropdown.classList.contains('show')) {
            openDropdown.classList.remove('show');
          }
        }
      }
    });

    const modal = document.getElementById("announcementModal");
    const createBtn = document.querySelector(".create-btn");

    // Show modal
    createBtn.onclick = () => {
      clearForm();
      editingCard = null;
      modal.style.display = "block";
    };

    function closeModal() {
      modal.style.display = "none";
    }

    // Close modal on background click
    window.onclick = (e) => {
      if (e.target === modal) closeModal();
    };

    // Clear modal form
    function clearForm() {
      document.getElementById("title").value = "";
      document.getElementById("date").value = "";
      document.getElementById("category").value = "infrastructure";
      document.getElementById("description").value = "";
    }

    let editingCard = null;

    document.querySelectorAll(".edit").forEach((btn) => {
      btn.addEventListener("click", function () {
        const card = this.closest(".announcement-card");
        editingCard = card;

        const title = card.querySelector("h3").innerText;
        const dateText = card.querySelector("p strong")?.nextSibling?.textContent.trim();
        const description = card.querySelectorAll("p")[1].innerText;
        const category = card.querySelector(".badge")?.innerText;

        document.getElementById("title").value = title;
        document.getElementById("date").value = dateText || "";
        document.getElementById("description").value = description;

        if (category.includes("Infrastructure")) {
          document.getElementById("category").value = "infrastructure";
        } else if (category.includes("Emergency")) {
          document.getElementById("category").value = "emergency";
        } else {
          document.getElementById("category").value = "community";
        }

        modal.style.display = "block";
      });
    });

    // Archive button hides the card
    document.querySelectorAll(".archive").forEach((btn) => {
      btn.addEventListener("click", function () {
        const card = this.closest(".announcement-card");
        card.style.display = "none";
      });
    });

    document.getElementById("announcementForm").addEventListener("submit", function (e) {

      const title = document.getElementById("title").value;
      const date = document.getElementById("date").value;
      const category = document.getElementById("category").value;
      const description = document.getElementById("description").value;

      const categoryLabel = {
        infrastructure: "Infrastructure Updates",
        emergency: "Emergency Alerts",
        community: "Community Updates"
      };

      if (editingCard) {
        editingCard.querySelector("h3").innerText = title;
        editingCard.querySelectorAll("p")[0].innerHTML = `<strong>Date:</strong> ${date}`;
        editingCard.querySelectorAll("p")[1].innerText = description;

        const badge = editingCard.querySelector(".badge");
        if (badge) {
          badge.innerText = categoryLabel[category];
          badge.className = `badge ${category}`;
        }
      }

      editingCard = null;
      closeModal();
    });
  </script>
</body>
</html>
