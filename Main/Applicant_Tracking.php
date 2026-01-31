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
  <title>Administrative</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    /* Sidebar Styles Removed - using component */
    .navbar,
    .main {
      left: 260px;
      width: calc(100% - 260px);
      transition: all 0.5s ease;
      z-index: 1000;
    }

    .sidebar.close~.navbar,
    .sidebar.close~.main {
      left: 0;
      width: 100%;
    }

    .navbar {
      position: fixed;
      color: #fff;
      padding: 15px 20px;
      font-size: 25px;
      background: #d37a15;
      cursor: pointer;
    }

    .navbar #sidebar-close {
      cursor: pointer;
    }

    .main {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      z-index: 100;
      background: #e7f2fd;
    }

    .main h1 {
      color: #11101d;
      font-size: 40px;
      text-align: center;
      margin-top: 20px;
    }

    .dropdown-content {
      background-color: #d37a15;
    }

    .main {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      display: grid;
      display: flex;
      justify-content: space-between;
      padding-top: 50px;
    }

    /* Modal and Table Styles */
    .container {
      padding: 20px;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      /* Dark overlay */
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #fefefe;
      padding: 30px 40px;
      border: 1px solid #888;
      width: 600px;
      max-width: 90%;
      border-radius: 10px;
      box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.3);
      text-align: center;
      position: relative;
      transform: translateY(0);
      /* Optional: remove unwanted transform */
      margin: 0 auto;
      margin-top: 100px;
    }

    .modal-content h2 {
      margin-bottom: 20px;
      /* Gap below "Add New Job Posting" */
      font-size: 28px;
      font-weight: 600;
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover,
    .close:focus {
      color: black;
    }

    input,
    textarea {
      width: 100%;
      margin: 10px 0;
      padding: 10px;
    }

    .btn {
      padding: 10px 20px;
      background-color: #d37a15;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 20px;
      /* Gap above button */

    }

    .btn:hover {
      background-color: #d37a15;
    }

    table {
      width: 100%;
      margin-top: 20px;
      background: white;
    }

    th,
    td {
      padding: 10px;
      text-align: center;
    }

    thead {
      background-color: #d37a15;
      color: white;
    }

    .save-btn {
      width: 100%;
      padding: 7px;
      border: none;
      border-radius: 10px;
      background-color: rgb(88, 181, 54);
      color: white;
      font-size: 16px;
      cursor: pointer;
    }

    .save-btn:hover {
      background-color: rgb(11, 66, 13);
    }

    .action-btn {
      margin: 0 5px;
      cursor: pointer;
    }

    .edit-btn {
      color: green;
    }

    .delete-btn {
      color: red;
    }

    .container {
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      margin: 50px auto;
      padding: 30px;
      max-width: 1200px;
      width: 90%;
      text-align: center;
      margin-bottom: 350px;
    }

    .page-title {
      font-size: 36px;
      font-weight: bold;
      color: #d37a15;
      text-align: center;
      margin-top: 30px;
      margin-bottom: 20px;
    }

    /* Top Bar Styles */
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 50px;
      padding: 8px 15px;
      width: 300px;
      height: 40px;
    }

    .search-wrapper i {
      color: #888;
      font-size: 18px;
      margin-right: 10px;
    }

    .search-bar {
      border: none;
      outline: none;
      width: 100%;
      font-size: 16px;
      background: transparent;
    }

    .form-row {
      display: flex;
      gap: 15px;
      margin-bottom: 10px;
    }

    .form-row input,
    .form-row textarea {
      flex: 1;
    }

    .form-row textarea {
      height: 100px;
      resize: vertical;
    }
  </style>
</head>

<body>
  <?php include '../Components/sidebar_admin.php'; ?>
  <main class="main">
    <?php include '../Components/header_admin.php'; ?>
    <!-- Title OUTSIDE the container -->
    <h1 class="page-title">Applicant Tracking</h1>

    <div class="container">
      <div class="top-bar">
        <button id="openModalBtn" class="btn">Add Applicant Information</button>
        <div class="search-wrapper">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Search Employee..." class="search-bar">
        </div>
      </div>
      <!-- Table -->
      <table id="jobsTable" border="1" cellspacing="0" cellpadding="10">
        <thead>
          <tr>
            <th>Title</th>
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
          <h2 id="modalTitle">Add New Applicant</h2>
          <form id="jobForm">
            <input type="hidden" id="editRowIndex" value="">

            <div class="form-row">
              <input type="text" id="title" placeholder="Title" required>
              <input type="text" id="position" placeholder="Position" required>
            </div>

            <div class="form-row">
              <input type="text" id="location" placeholder="Location" required>
              <input type="tel" id="contact" placeholder="Contact"
                oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
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
        document.getElementById("modalTitle").innerText = "Add New Applicant";
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