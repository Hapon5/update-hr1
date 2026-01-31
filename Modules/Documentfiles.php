<?php
session_start();
if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] !== '1') {
  header("Location: login.php");
  exit();
}
$admin_email = $_SESSION['Email'];
echo " ";

echo ' ';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HR1</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
  <link rel="stylesheet" href="../css/Documentfiles.css">



</head>

<body>
  <nav class="sidebar">
    <h2 id="h2">HR1</h2>
    <div class="menu-content">
      <ul class="menu-items">
        <div class="menu-title">
          <i class="fa-solid fa-chevron-left"></i>
          Back
        </div>
        <div class="menu-title"><small>HR DASHBOARD</small></div>
        <li class="item">
          <span class="icon"><i class='bx bxs-dashboard'></i></span>
          <a class="nav_link">Dashboard</a>
        </li>
        <li class="item">
          <span class="icon"><i class='bx bx-group'></i></span>
          <div class="submenu-item">
            <span>HR1</span>
            <i class="fa-solid fa-chevron-right"></i>
          </div>
          <ul class="menu-items submenu">
            <div class="menu-title">
              <i class='bx bx-arrow-back'></i>Back
            </div>
            <li class="item1">
              <a>EMPLOYEE MANAGEMENT</a>
              <div class="dropdown-content">
                <a href="employee_database.php"><small>EMPLOYEE DATABASE</small></a>
                <a href="performance_and_appraisals.php"><small>PERFORMANCE & APPRAISALS</small></a>
              </div>
            </li>
            <li class="item1">
              <a>RECRUITMENT</a>
              <div class="dropdown-content">
                <a href="job_posting.php"><small>Create Job Posting</small></a>
                <a href="candidate_sourcing_&_tracking.php"><small>CANDIDATE SOURCING & TRACKING</small></a>
                <a href="interview_scheduling.php"><small>INTERVIEW SCHEDULING</small></a>
                <a href="assessment_&_screening.php"><small>ASSESSMENT & SCREENING</small></a>
              </div>
            </li>
            <li class="item1">
              <a>APPLICANT MANAGEMENT</a>
              <div class="dropdown-content">
                <a href="#"><small>RESUME PARSING & STORAGE</small></a>
                <a href="#"><small>COMMUNICATION & NOTIFICATIONS</small></a>
                <a href="#"><small>DOCUMENT MANAGEMENT</small></a>
              </div>
            </li>
            <li class="item1">
              <a>NEW HIRED ONBOARDING SYSTEM</a>
              <div class="dropdown-content">
                <a href="#"><small>DIGITAL ONBOARDING PROCESS</small></a>
                <a href="#"><small>WELCOME KIT & ORIENTATION</small></a>
                <a href="#"><small>USER ACCOUNT & SETUP</small></a>
              </div>
            </li>
            <li class="item1">
              <a>RECRUITING ANALYTIC & REPORTING</a>
              <div class="dropdown-content">
                <a href="#"><small>HIRING METRICS DASHBOARD</small></a>
                <a href="#"><small>RECRUITMENT FUNNEL & ANALYSIS</small></a>
                <a href="#"><small>RECRUITER PERFORMANCE TRACKING</small></a>
                <a href="#"><small>DIVERSITY & COMPLIANCE REPORT</small></a>
                <a href="#"><small>COST & BUDGET ANALYSIS</small></a>
              </div>
            </li>
          </ul>
        </li>
        <li class="item">
          <span class="icon"><i class="fa-solid fa-gear"></i></span>
          <a href="aboutus.html" class="nav_link">About Us</a>
        </li>
        <li class="item" id="logout-link">
          <span class="icon"><i class='bx bx-log-out'></i></span>
          <a href="#">Logout</a>
        </li>
      </ul>
    </div>
  </nav>
  <main class="main">
    <?php include '../Components/header_admin.php'; ?>
    <!-- Title OUTSIDE the container -->
    <h1 class="page-title">Applicant Documents</h1>

    <div class="container">
      <div class="top-bar">
        <button id="openModalBtn" class="btn">Applicant Information</button>
        <div class="search-wrapper">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Search Employee..." class="search-bar">
        </div>
      </div>
      <!-- Table -->
      <table id="jobsTable" border="1" cellspacing="0" cellpadding="10">
        <thead>
          <tr>
            <th>Name</th>
            <th>Position</th>
            <th>Location</th>
            <th>Requirements</th>
            <th>Contact</th>
            <th>Platform</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Data appears here -->
        </tbody>
      </table>

      <!-- Modal for Add/Edit -->
      <div id="modal" class="modal">
        <div class="modal-content">
          <span id="closeModal" class="close">&times;</span>
          <h2 id="modalTitle">Applicant Information</h2>
          <form id="jobForm">
            <input type="hidden" id="editRowIndex" value="">

            <div class="form-row">
              <input type="text" id="Name" placeholder="Name" required>
              <input type="text" id="position" placeholder="Position" required>
            </div>

            <div class="form-row">
              <input type="text" id="location" placeholder="Location" required>
              <input type="text" id="contact" placeholder="Contact" required>
            </div>

            <div class="form-row">
              <input type="text" id="platform" placeholder="Platform" required>
              <input type="date" id="date" required>
            </div>

            <div class="form-row">
              <textarea id="requirements" placeholder="Requirements" required></textarea>
            </div>

            <button type="submit" class="save-btn" id="saveBtn">Save</button>
          </form>
        </div>
      </div>

    </div>
  </main>

  <script src="Dashboard.js"></script>
  <script>
    // Open Modal
    document.getElementById("openModalBtn").onclick = function () {
      openModal();
    };

    // Close Modal
    document.getElementById("closeModal").onclick = function () {
      closeModal();
    };

    // Save Data (Add or Edit)
    document.getElementById("jobForm").addEventListener("submit", function (e) {
      e.preventDefault();

      const title = document.getElementById("title").value;
      const position = document.getElementById("position").value;
      const location = document.getElementById("location").value;
      const requirements = document.getElementById("requirements").value;
      const contact = document.getElementById("contact").value;
      const platform = document.getElementById("platform").value;
      const date = document.getElementById("date").value;
      const editIndex = document.getElementById("editRowIndex").value;

      if (editIndex === "") {
        // Add New Row
        const table = document.getElementById("jobsTable").getElementsByTagName('tbody')[0];
        const newRow = table.insertRow();
        newRow.innerHTML = `
            <td>${title}</td>
            <td>${position}</td>
            <td>${location}</td>
            <td>${requirements}</td>
            <td>${contact}</td>
            <td>${platform}</td>
            <td>${date}</td>
            <td>
                <span class="action-btn edit-btn" onclick="editRow(this)">Edit</span>
                <span class="action-btn delete-btn" onclick="deleteRow(this)">Delete</span>
            </td>
        `;
      } else {
        // Edit Existing Row
        const table = document.getElementById("jobsTable").getElementsByTagName('tbody')[0];
        const row = table.rows[editIndex];
        row.cells[0].innerText = title;
        row.cells[1].innerText = position;
        row.cells[2].innerText = location;
        row.cells[3].innerText = requirements;
        row.cells[4].innerText = contact;
        row.cells[5].innerText = platform;
        row.cells[6].innerText = date;
      }

      closeModal();
      document.getElementById("jobForm").reset();
    });

    // Functions
    function openModal(edit = false) {
      document.getElementById("modal").style.display = "block";
      if (!edit) {
        document.getElementById("modalTitle").innerText = "Applicant Information";
        document.getElementById("editRowIndex").value = "";
      }
    }

    function closeModal() {
      document.getElementById("modal").style.display = "none";
    }

    // Edit Row
    function editRow(element) {
      const row = element.parentNode.parentNode;
      const cells = row.getElementsByTagName('td');
      const table = document.getElementById("jobsTable").getElementsByTagName('tbody')[0];
      const index = Array.prototype.indexOf.call(table.rows, row);

      document.getElementById("title").value = cells[0].innerText;
      document.getElementById("position").value = cells[1].innerText;
      document.getElementById("location").value = cells[2].innerText;
      document.getElementById("requirements").value = cells[3].innerText;
      document.getElementById("contact").value = cells[4].innerText;
      document.getElementById("platform").value = cells[5].innerText;
      document.getElementById("date").value = cells[6].innerText;
      document.getElementById("editRowIndex").value = index;

      openModal(true);
    }

    // Delete Row
    function deleteRow(element) {
      if (confirm("Are you sure you want to delete this job posting?")) {
        const row = element.parentNode.parentNode;
        row.parentNode.removeChild(row);
      }
    }

    // Close Modal if click outside
    window.onclick = function (event) {
      if (event.target == document.getElementById("modal")) {
        closeModal();
      }
    };

    document.getElementById("searchInput").addEventListener("input", function () {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll("#jobsTable tbody tr");

      rows.forEach(row => {
        const cells = row.querySelectorAll("td");
        let match = false;

        cells.forEach(cell => {
          if (cell.innerText.toLowerCase().includes(filter)) {
            match = true;
          }
        });

        row.style.display = match ? "" : "none";
      });
    });

  </script>
</body>

</html>