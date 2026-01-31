<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Performance & Appraisals | HR Admin</title>
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Fonts -->
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    body {
      background-color: #f1f5f9;
    }

    .main-content {
      margin-left: 16rem;
      /* Match w-64 of sidebar */
      min-height: 100vh;
      transition: all 0.3s ease;
      position: relative;
      padding: 110px 2.5rem 2.5rem;
      /* 110px top padding (70px header + 40px gap) */
    }

    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
      }
    }
  </style>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../Css/performance.css" />
  <link rel="icon" type="image/x-icon" href="../Image/logo.png">
</head>

<body>

  <!-- Sidebar -->
  <?php include '../Components/sidebar_admin.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Menu Toggle for Mobile -->
    <?php include '../Components/header_admin.php'; ?>

    <h1>Performance & Appraisals</h1>

    <div class="search-wrapper">
      <i class="fa fa-search search-icon"></i>
      <input type="text" id="searchBar" placeholder="Search employees by name...">
    </div>

    <div class="employee-container">
      <!-- Employee Card 1 -->
      <div class="employee-card" onclick="openModal('Andy Ferrer', '001', 'IT Support', 'profile/ferrer.jpeg')">
        <img src="../Profile/ferrer.jpeg" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"
          alt="Employee Photo">
        <h3 data-name="Andy Ferrer">Andy Ferrer</h3>
        <p>ID: 001</p>
        <p>Position: IT Support</p>
        <div class="stars">
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa fa-star"></i><i class="fa fa-star-half-alt"></i>
          <i class="fa-regular fa-star"></i>
        </div>
      </div>

      <!-- Employee Card 2 -->
      <div class="employee-card"
        onclick="openModal('Siegfried Mar Viloria', '002', 'Team Leader', 'profile/Viloria.jpeg')">
        <img src="../Profile/Viloria.jpeg" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"
          alt="Employee Photo">
        <h3 data-name="Siegfried Mar Viloria">Siegfried Mar Viloria</h3>
        <p>ID: 002</p>
        <p>Position: Team Leader</p>
        <div class="stars">
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa fa-star"></i>
        </div>
      </div>

      <!-- Employee Card 3 -->
      <div class="employee-card"
        onclick="openModal('John Lloyd Morales', '003', 'System Analyst', 'profile/morales.jpeg')">
        <img src="../Profile/morales.jpeg" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"
          alt="Employee Photo">
        <h3 data-name="John Lloyd Morales">John Lloyd Morales</h3>
        <p>ID: 003</p>
        <p>Position: System Analyst</p>
        <div class="stars">
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa-regular fa-star"></i>
        </div>
      </div>
      <!-- Employee Card 4 -->
      <div class="employee-card"
        onclick="openModal('Andrea Ilagan', '004', 'Technical Support', 'profile/ilagan.jpeg')">
        <img src="../Profile/ilagan.jpeg" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"
          alt="Employee Photo">
        <h3 data-name="Andrea Ilagan">Andrea Ilagan</h3>
        <p>ID: 004</p>
        <p>Position: Technical Support</p>
        <div class="stars">
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa-regular fa-star"></i>
        </div>
      </div>
      <!-- Employee Card 5 -->
      <div class="employee-card"
        onclick="openModal('Charlotte Achivida', '005', 'Cyber Security', 'profile/achivida.jpeg')">
        <img src="../Profile/achivida.jpeg" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"
          alt="Employee Photo">
        <h3 data-name="Charlotte Achivida">Charlotte Achivida</h3>
        <p>ID: 005</p>
        <p>Position: Cyber Security</p>
        <div class="stars">
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa fa-star"></i><i class="fa fa-star"></i>
          <i class="fa-regular fa-star"></i>
        </div>
      </div>
    </div>

    <!-- Feedback Modal -->
    <div id="employeeModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <img id="modalImg" src="" alt="Employee Photo"
          onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
        <h2 id="modalName">Employee Name</h2>

        <div class="modal-info">
          <p><strong>ID:</strong> <span id="modalID">--</span></p>
          <p><strong>Position:</strong> <span id="modalPos">--</span></p>
          <p><strong>Status:</strong> Active</p>
        </div>

        <div class="rating-section">
          <label style="display:block; margin-bottom:10px; color:#666;">Rate Performance:</label>
          <div class="star-rating">
            <input type="radio" name="stars" value="5" id="star5"><label for="star5" title="Excellent">&#9733;</label>
            <input type="radio" name="stars" value="4" id="star4"><label for="star4" title="Good">&#9733;</label>
            <input type="radio" name="stars" value="3" id="star3"><label for="star3" title="Average">&#9733;</label>
            <input type="radio" name="stars" value="2" id="star2"><label for="star2" title="Poor">&#9733;</label>
            <input type="radio" name="stars" value="1" id="star1"><label for="star1" title="Very Poor">&#9733;</label>
          </div>
        </div>

        <div class="comment-box">
          <textarea rows="4" placeholder="Write additional feedback or comments..."></textarea>
        </div>

        <button class="btn-submit" onclick="submitFeedback()">Submit Review</button>
      </div>
    </div>
  </div>

  <script>
    // Sidebar Toggle Logic handled by header_admin.php

    // Modal Logic
    const modal = document.getElementById("employeeModal");
    const modalName = document.getElementById("modalName");
    const modalID = document.getElementById("modalID");
    const modalPos = document.getElementById("modalPos");
    const modalImg = document.getElementById("modalImg");

    function openModal(name, id, pos, imgPath) {
      modal.style.display = "flex";
      modalName.textContent = name;
      modalID.textContent = id;
      modalPos.textContent = pos;
      // Handle image path - check if it's already full path or needs prefix
      if (imgPath.startsWith('http') || imgPath.startsWith('../')) {
        modalImg.src = imgPath;
      } else {
        modalImg.src = "../Profile/" + imgPath.split('/').pop();
      }
    }

    function closeModal() {
      modal.style.display = "none";
      // Reset form
      document.querySelectorAll('input[name="stars"]').forEach(el => el.checked = false);
      document.querySelector('.comment-box textarea').value = '';
    }

    function submitFeedback() {
      const rating = document.querySelector('input[name="stars"]:checked');
      const comment = document.querySelector('.comment-box textarea').value;
      const name = modalName.textContent;

      if (rating) {
        alert(`Success! \nFeedback for ${name} submitted.\nRating: ${rating.value} Stars\nComment: ${comment}`);
        closeModal();
      } else {
        alert("Please select a star rating before submitting.");
      }
    }

    // Close modal on outside click
    window.onclick = function (event) {
      if (event.target === modal) {
        closeModal();
      }
    };

    // Search Logic
    const searchBar = document.getElementById("searchBar");
    searchBar.addEventListener("input", function () {
      const query = searchBar.value.toLowerCase();
      const cards = document.querySelectorAll(".employee-card");

      cards.forEach(card => {
        const name = card.querySelector('h3').getAttribute("data-name").toLowerCase();
        if (name.includes(query)) {
          card.style.display = "block";
        } else {
          card.style.display = "none";
        }
      });
    });
  </script>
</body>

</html>