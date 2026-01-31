<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <title>About Us - HR Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");

        :root {
            --primary-color: #6366f1;
            --background-light: #000000;
            --background-card: #111827;
            --text-dark: #f3f4f6;
            --text-light: #f4f4f4;
            --text-muted: #9ca3af;
            --shadow-subtle: 0 10px 30px rgba(0, 0, 0, 0.4);
            --border-radius: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            background-color: var(--background-light);
            min-height: 100vh;
            color: var(--text-dark);
            /* Custom Scroll */
        }
 
        ::-webkit-scrollbar {
            width: 6px;
        }
 
        ::-webkit-scrollbar-track {
            background: #000;
        }
 
        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 10px;
        }
 
        ::-webkit-scrollbar-thumb:hover {
            background: #444;
        }

        /* Sidebar styles handled by component */

        /* Main Content */
        .main-content {
            margin-left: 16rem;
            /* Match w-64 of sidebar */
            min-height: 100vh;
            transition: all 0.3s ease;
            position: relative;
            padding: 110px 2.5rem 2.5rem;
            /* 110px top padding (70px header + 40px gap) */
        }

        @media screen and (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }

        .page-header {
            background: var(--background-card);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-subtle);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .menu-toggle {
            display: none;
            /* Hide by default, show on smaller screens if needed */
            font-size: 24px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        /* Team Section UI */
        .team-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .team-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .team-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .team-container {
                grid-template-columns: 1fr;
            }
        }

        .member-card {
            background-color: var(--background-card);
            border-radius: var(--border-radius);
            padding: 24px 20px;
            text-align: center;
            box-shadow: var(--shadow-subtle);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
 
        .member-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary-color);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        .member-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: block;
            border: 4px solid var(--primary-color);
        }

        .member-card h3 {
            margin: 15px 0 5px;
            color: var(--text-dark);
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            white-space: nowrap;
            /* Keep on one line */
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .member-card .role {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .member-card .bio {
            font-size: 0.75rem;
            /* Reduced for better fit in 5-col */
            color: var(--text-muted);
            line-height: 1.5;
            text-align: left;
            /* Natural spacing */
            margin-top: 10px;
            flex-grow: 1;
        }

        .btn-resume {
            margin-top: 15px;
            padding: 10px 16px;
            background: var(--primary-color);
            color: #fff;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            text-decoration: none;
            transition: all 0.3s ease;
            width: 100%;
            display: inline-block;
            border: 1px solid var(--primary-color);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
 
        .btn-resume:hover {
            background: transparent;
            color: var(--primary-color);
            box-shadow: none;
        }
    </style>
</head>

<body>

    <?php include '../Components/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../Components/header_admin.php'; ?>

        <header class="page-header">
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">About Us</h1>
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-2">Meet the passionate team behind our success</p>
        </header>

        <?php
        // Define the team array
        $team = [
            [
                "name" => "Siegfried Mar Viloria",
                "role" => "Team Leader / Developer",
                "bio" => "Experienced Team Leader and Full-Stack Developer with a strong background in leading cross-functional teams and delivering scalable software solutions.",
                "photo" => "../Profile/Viloria.jpeg",
                "resume" => "uploads/resumes/69536e89a093a_Sigfried_villoria.jpg"
            ],
            [
                "name" => "John Lloyd Morales",
                "role" => "System Analyst",
                "bio" => "Detail-oriented System Analyst with a strong background in analyzing business requirements and translating them into effective technical solutions.",
                "photo" => "../Profile/morales.jpeg",
                "resume" => "uploads/resumes/6953715ae9128_john_lloyd_Morales.jpg"
            ],
            [
                "name" => "Andy Ferrer",
                "role" => "Document Specialist",
                "bio" => "Skilled Document Specialist with expertise in managing, formatting, and maintaining high-quality business documents across various platforms.",
                "photo" => "../Profile/ferrer.jpeg",
                "resume" => "uploads/resumes/695370982e5cd_Andy-resume-1_page-0001.jpg"
            ],
            [
                "name" => "Andrea Ilagan",
                "role" => "Technical Support Analyst",
                "bio" => "A dedicated Technical Support Analyst with experience in diagnosing and resolving hardware, software, and network issues across various platforms.",
                "photo" => "../Profile/ilagan.jpeg",
                "resume" => "uploads/resumes/69536fc988fb1_Adrea_Ilagan.jpg"
            ],
            [
                "name" => "Charlotte Achivida",
                "role" => "Cyber Security Analyst",
                "bio" => "A detail-oriented Cybersecurity Analyst with expertise in identifying vulnerabilities, monitoring threats, and implementing security measures.",
                "photo" => "../Profile/achivida.jpeg",
                "resume" => "uploads/resumes/695372096ccd1_Charlott_achivida.jpg"
            ]
        ];
        ?>

        <div class="team-container">
            <?php foreach ($team as $member): ?>
                <div class="member-card">
                    <img src="<?= htmlspecialchars($member["photo"]) ?>"
                        alt="Photo of <?= htmlspecialchars($member["name"]) ?>">
                    <h3><?= htmlspecialchars($member["name"]) ?></h3>
                    <p class="role"><?= htmlspecialchars($member["role"]) ?></p>
                    <p class="bio"><?= htmlspecialchars($member["bio"]) ?></p>
                    <a href="javascript:void(0)" onclick="openPinModal('<?= htmlspecialchars($member['resume']) ?>')" class="btn-resume">Resume</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PIN Modal -->
    <div id="pinModal" class="fixed inset-0 bg-black bg-opacity-80 hidden flex items-center justify-center z-50 backdrop-blur-md transition-all duration-300">
        <div class="bg-gray-900 p-8 rounded-[32px] shadow-2xl w-80 transform transition-all scale-100 border border-gray-800 text-center relative overflow-hidden">
            <!-- Decorative circle -->
            <div class="absolute top-0 left-0 w-full h-1 bg-indigo-500"></div>
            
            <div class="mx-auto w-14 h-14 bg-indigo-500/10 rounded-2xl border border-indigo-500/20 flex items-center justify-center mb-6 shadow-lg">
                <i class="fa-solid fa-lock text-indigo-500 text-xl"></i>
            </div>
            
            <h2 class="text-lg font-black text-white mb-1 uppercase tracking-tight">Protected Content</h2>
            <p class="text-[10px] font-bold text-gray-500 mb-8 uppercase tracking-widest">Please enter the security PIN to view this resume.</p>
            
            <div class="mb-6 relative">
                <input type="password" id="pinInput" placeholder="PIN" 
                    class="w-full px-4 py-4 bg-black border border-gray-800 rounded-xl text-center text-3xl font-black tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all placeholder:text-xs placeholder:font-bold placeholder:tracking-widest text-white"
                    maxlength="4">
                <p id="errorMessage" class="text-red-500 text-[10px] mt-2 font-black uppercase tracking-widest hidden flex items-center justify-center gap-1 absolute -bottom-6 left-0 right-0">
                    <i class="fa-solid fa-circle-exclamation"></i> Incorrect PIN
                </p>
            </div>
 
            <div class="flex gap-3 mt-10">
                <button onclick="closePinModal()" class="flex-1 px-4 py-3 border border-gray-800 text-gray-500 rounded-xl hover:bg-gray-800 transition-colors font-bold text-[10px] uppercase tracking-widest">Cancel</button>
                <button onclick="verifyPin()" class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-950/40">Access</button>
            </div>
        </div>
    </div>

    <script>
        // Sidebar and Date Time handled by header_admin.php
    </script>

    <script>
        let currentResumeUrl = '';
        const CORRECT_PIN = "1234"; // Default PIN

        function openPinModal(url) {
            currentResumeUrl = url;
            const modal = document.getElementById('pinModal');
            const input = document.getElementById('pinInput');
            const errorMsg = document.getElementById('errorMessage');
            
            modal.classList.remove('hidden');
            // Small checking to ensure animation plays if we add fade in classes
            // We use standard Tailwind utility classes
            
            input.value = '';
            input.classList.remove('border-red-500', 'bg-red-50');
            errorMsg.classList.add('hidden');
            
            setTimeout(() => input.focus(), 100);
        }

        function closePinModal() {
            const modal = document.getElementById('pinModal');
            modal.classList.add('hidden');
            currentResumeUrl = '';
        }

        function verifyPin() {
            const input = document.getElementById('pinInput');
            const errorMsg = document.getElementById('errorMessage');
            
            if (input.value === CORRECT_PIN) {
                // Success
                window.open(currentResumeUrl, '_blank');
                closePinModal();
            } else {
                // Error
                errorMsg.classList.remove('hidden');
                input.classList.add('border-red-500', 'bg-red-50');
            }
        }
        
        // Event Listeners
        document.getElementById('pinInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') verifyPin();
        });
        
        document.getElementById('pinInput').addEventListener('input', function() {
             this.classList.remove('border-red-500', 'bg-red-50');
             document.getElementById('errorMessage').classList.add('hidden');
        });

        // Close on backdrop click
        document.getElementById('pinModal').addEventListener('click', function(e) {
            if (e.target === this) closePinModal();
        });
    </script>

</body>

</html>