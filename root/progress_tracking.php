<?php
session_start();
require 'db.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_code'], $_POST['status'])) {
    $issue_code = mysqli_real_escape_string($conn, $_POST['issue_code']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update_query = "UPDATE issues SET status = '$status' WHERE issue_code = '$issue_code'";
    mysqli_query($conn, $update_query);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$filter = $_GET['filter'] ?? 'all';
$query = "SELECT * FROM issues";
if ($filter !== 'all') {
    $query .= " WHERE status = '" . mysqli_real_escape_string($conn, $filter) . "'";
}
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Progress Tracking - Admin</title>
  <link rel="stylesheet" href="progress_tracking.css" />
  <style>
    .issue-card {
      transition: all 0.3s ease;
    }
    .issue-card.removing {
      opacity: 0;
      transform: translateX(100%);
      height: 0;
      padding: 0;
      margin: 0;
      border: none;
      overflow: hidden;
    }
  </style>
</head>
<body>
<div class="sidebar">
  <h2>STREETFIX</h2>
  <ul>
    <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
      <a href="dashboard.php">🏠 Dashboard</a>
    </li>
    <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'progress_tracking.php' ? 'active' : '' ?>">
      <a href="progress_tracking.php">🛠 Progress Tracking</a>
    </li>
    <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'announcement.php' ? 'active' : '' ?>">
      <a href="announcement.php">🔔 Announcements</a>
    </li>
    <li class="nav-item">
      <a href="reports_analytics.php">📈 Reports & Analytics</a>
    </li>
  </ul>
</div>

<div class="main-content">
<header>
  <div class="user-dropdown" onclick="toggleDropdown()">
    <div class="user-info">
      <span class="user-name">👤 Admin</span>
      <span class="dropdown-arrow">▾</span>
    </div>
    <div id="userDropdown" class="dropdown-content">
      <a href="logout.php">🚪 Logout</a>
    </div>
  </div>
</header>

  <section class="progress-tracking">
    <div class="header-bar">
      <h2>Progress Tracking</h2>
      <form method="GET" class="sort-form">
        <select name="filter" onchange="this.form.submit()">
          <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Reports</option>
          <option value="Resolved" <?= $filter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
          <option value="In Progress" <?= $filter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
          <option value="Pending" <?= $filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
        </select>
      </form>
    </div>

    <div class="card-container">
      <?php while ($issue = mysqli_fetch_assoc($result)): ?>
      <div class="issue-card" id="issue-<?= $issue['issue_code'] ?>">
        <p><strong>Issue Code:</strong> <?= htmlspecialchars($issue['issue_code']) ?></p>
        <p><strong>Type of Issue:</strong> <?= htmlspecialchars($issue['type']) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($issue['location']) ?></p>
        <p><strong>Reported On:</strong> <?= htmlspecialchars($issue['date_reported']) ?></p>
        <p><strong>Image:</strong> <a href="<?= htmlspecialchars($issue['image_path']) ?>" target="_blank">View Image</a></p>
        <p><strong>Status:</strong> <span class="status-label <?= strtolower(str_replace(' ', '-', $issue['status'])) ?>"><?= $issue['status'] ?></span></p>
        <div class="progress-bar">
          <?php
            $levels = ["Pending" => 1, "Verified" => 2, "In Progress" => 3, "Resolved" => 5];
            $filled = $levels[$issue['status']] ?? 1;
            for ($i = 0; $i < 5; $i++):
          ?>
            <div class="<?= $i < $filled ? 'active' : '' ?>"></div>
          <?php endfor; ?>
        </div>
        <div class="view-btn">
          <button onclick='openIssueModal(<?= json_encode($issue) ?>)'>View</button>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

    <div class="pagination">
      <form method="post" action="download_reports.php">
        <button class="download-btn" type="submit">📥 Download</button>
      </form>
      <div><span>Page 1</span><button disabled>Next</button></div>
    </div>
  </section>
</div>

<div id="issueModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeIssueModal()">&times;</span>
    <h3>Issue Resolution to Maintenance Team</h3>
    <div class="issue-details">
      <p><strong>Issue Code:</strong> <span id="modalIssueCode"></span></p>
      <hr />
      <p><strong>Type of Issue:</strong> <span id="modalType"></span></p>
      <p><strong>Location:</strong> <span id="modalLocation"></span></p>
      <p><strong>Reported On:</strong> <span id="modalDate"></span></p>
      <p><strong>Image:</strong> <a id="modalImageLink" target="_blank">View Image</a></p>
      <p><strong>Status:</strong> <span id="modalStatus" class="status-label pending"></span></p>
      <div class="progress-bar" id="modalProgress"><div></div><div></div><div></div><div></div><div></div></div>
    </div>
    <div class="action-box">
      <h4>Actions</h4>
      <p>Report to Maintenance Team</p>
      <label for="statusUpdate">Update Status:</label>
      <select id="statusUpdate">
        <option value="Pending">Pending</option>
        <option value="Verified">Verified</option>
        <option value="In Progress">In Progress</option>
        <option value="Resolved">Resolved</option>
      </select>
      <br />
      <button onclick="submitStatusUpdate()">Submit Status Update</button>
    </div>
  </div>
</div>

<script>
function toggleDropdown() {
  document.getElementById("userDropdown").classList.toggle("show");
}

function openIssueModal(issue) {
  document.getElementById("modalIssueCode").textContent = issue.issue_code;
  document.getElementById("modalType").textContent = issue.type;
  document.getElementById("modalLocation").textContent = issue.location;
  document.getElementById("modalDate").textContent = issue.date_reported;
  document.getElementById("modalImageLink").href = issue.image_path;
  document.getElementById("modalStatus").textContent = issue.status;
  const statusEl = document.getElementById("modalStatus");
  statusEl.className = "status-label " + issue.status.toLowerCase().replaceAll(" ", "-");
  const progressDivs = document.getElementById("modalProgress").children;
  const levels = {"Pending": 1, "Verified": 2, "In Progress": 3, "Resolved": 5};
  const filled = levels[issue.status] || 1;
  for (let i = 0; i < progressDivs.length; i++) {
    progressDivs[i].className = i < filled ? "active" : "";
  }
  document.getElementById("statusUpdate").value = issue.status;
  document.getElementById("issueModal").style.display = "block";
}

function closeIssueModal() {
  document.getElementById("issueModal").style.display = "none";
}

function submitStatusUpdate() {
  const status = document.getElementById("statusUpdate").value;
  const issueCode = document.getElementById("modalIssueCode").textContent;
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '';
  const issueCodeInput = document.createElement('input');
  issueCodeInput.type = 'hidden';
  issueCodeInput.name = 'issue_code';
  issueCodeInput.value = issueCode;
  const statusInput = document.createElement('input');
  statusInput.type = 'hidden';
  statusInput.name = 'status';
  statusInput.value = status;
  form.appendChild(issueCodeInput);
  form.appendChild(statusInput);
  document.body.appendChild(form);
  form.submit();
}
</script>
</body>
</html>
