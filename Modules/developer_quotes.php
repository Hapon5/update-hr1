<?php

$quotes = [
    [
        "author" => "Linus Torvalds",
        "quote" => "Talk is cheap. Show me the code.",
        "category" => "Programming",
        "likes" => 1247
    ],
    [
        "author" => "Steve Jobs",
        "quote" => "Innovation distinguishes between a leader and a follower.",
        "category" => "Innovation",
        "likes" => 892
    ],
    [
        "author" => "Bill Gates",
        "quote" => "The computer was born to solve problems that did not exist before.",
        "category" => "Technology",
        "likes" => 756
    ],
    [
        "author" => "Tim Berners-Lee",
        "quote" => "The Web as I envisaged it, we have not seen it yet. The future is still so much bigger than the past.",
        "category" => "Web Development",
        "likes" => 634
    ],
    [
        "author" => "Grace Hopper",
        "quote" => "The most dangerous phrase in the language is, 'We've always done it this way.'",
        "category" => "Innovation",
        "likes" => 923
    ],
    [
        "author" => "Alan Kay",
        "quote" => "The best way to predict the future is to invent it.",
        "category" => "Innovation",
        "likes" => 567
    ],
    [
        "author" => "Donald Knuth",
        "quote" => "Programs are meant to be read by humans and only incidentally for computers to execute.",
        "category" => "Programming",
        "likes" => 445
    ],
    [
        "author" => "Kent Beck",
        "quote" => "Make it work, make it right, make it fast.",
        "category" => "Programming",
        "likes" => 678
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Quotes - HR Admin</title>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <!-- Use Tailwind for sidebar/layout consistency -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: {
                        brand: { 500: '#6366f1', 600: '#4f46e5' },
                        accent: { DEFAULT: '#00ff88' }
                    }
                }
            }
        }
    </script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");

        :root {
            --primary-color: #000;
            --secondary-color: #1a1a1a;
            --accent-color: #00ff88;
            --background-dark: #0a0a0a;
            --background-card: #111111;
            --text-light: #ffffff;
            --text-muted: #888888;
            --text-accent: #00ff88;
            --border-color: #333333;
            --shadow-dark: 0 8px 32px rgba(0, 0, 0, 0.3);
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        /* Override some Tailwind defaults to match the dark theme */
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            color: var(--text-light);
            min-height: 100vh;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #1a1a1a;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #444;
        }

        /* Main Content - Adjusted for Fixed Sidebar */
        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            /* Default sidebar width */
            padding: 100px 30px 30px 30px;
            transition: margin-left 0.3s ease;
            width: calc(100% - 260px);
        }

        /* Quote Cards Styling */
        .page-header {
            background: var(--background-card);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-dark);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00ff88, #00cc6a, #00ff88);
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #00ff88, #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Quotes Grid */
        .quotes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .quote-card {
            background: var(--background-card);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-dark);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .quote-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #00ff88, #00cc6a);
        }

        .quote-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 255, 136, 0.2);
            border-color: var(--accent-color);
        }

        .quote-text {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
            color: var(--text-light);
            font-style: italic;
            position: relative;
        }

        .quote-text::before {
            content: '"';
            font-size: 3rem;
            color: var(--accent-color);
            position: absolute;
            top: -10px;
            left: -10px;
            opacity: 0.3;
        }

        .quote-author {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 8px;
        }

        .quote-category {
            display: inline-block;
            background: linear-gradient(45deg, #00ff88, #00cc6a);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .quote-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
        }

        .like-button,
        .share-button {
            background: none;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .like-button:hover,
        .share-button:hover {
            background: var(--accent-color);
            color: var(--primary-color);
            border-color: var(--accent-color);
        }

        .like-button.liked {
            background: var(--accent-color);
            color: var(--primary-color);
            border-color: var(--accent-color);
        }

        /* Stats Section */
        .stats-section {
            background: var(--background-card);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-dark);
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            background: linear-gradient(135deg, #1a1a1a, #0a0a0a);
            border: 1px solid var(--border-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-left: 15px;
                padding-right: 15px;
            }

            .quotes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="bg-black">
    <!-- Include Reusable Sidebar -->
    <?php include '../Components/sidebar_admin.php'; ?>

    <div class="main-content" id="mainContent">
        <?php include '../Components/header_admin.php'; ?>

        <header class="page-header">
            <h1>Developer Quotes</h1>
            <p>Inspirational quotes from legendary developers and tech leaders</p>
        </header>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($quotes); ?></div>
                    <div class="stat-label">Total Quotes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo array_sum(array_column($quotes, 'likes')); ?></div>
                    <div class="stat-label">Total Likes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count(array_unique(array_column($quotes, 'category'))); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count(array_unique(array_column($quotes, 'author'))); ?></div>
                    <div class="stat-label">Authors</div>
                </div>
            </div>
        </div>

        <!-- Quotes Grid -->
        <div class="quotes-grid">
            <?php foreach ($quotes as $index => $quote): ?>
                <div class="quote-card">
                    <div class="quote-text">
                        <?php echo htmlspecialchars($quote['quote']); ?>
                    </div>
                    <div class="quote-author">
                        — <?php echo htmlspecialchars($quote['author']); ?>
                    </div>
                    <div class="quote-category">
                        <?php echo htmlspecialchars($quote['category']); ?>
                    </div>
                    <div class="quote-actions">
                        <button class="like-button" onclick="toggleLike(<?php echo $index; ?>)">
                            <i class="fas fa-heart"></i>
                            <span><?php echo $quote['likes']; ?></span>
                        </button>
                        <button class="share-button"
                            onclick="shareQuote('<?php echo addslashes($quote['quote']); ?>', '<?php echo addslashes($quote['author']); ?>')">
                            <i class="fas fa-share"></i>
                            <span>Share</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Sidebar alignment script for the main content
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById("sidebar");
            const toggleBtn = document.getElementById("sidebar-toggle");
            const mainContent = document.getElementById("mainContent");

            if (toggleBtn && sidebar && mainContent) {
                toggleBtn.addEventListener("click", () => {
                    // Sidebar class toggling is handled in header_admin.php or sidebar_admin.php
                    // Here we just adjust the margin
                    sidebar.classList.toggle("close");

                    if (sidebar.classList.contains("close")) {
                        mainContent.style.marginLeft = "80px";
                        mainContent.style.width = "calc(100% - 80px)";
                    } else {
                        mainContent.style.marginLeft = "260px";
                        mainContent.style.width = "calc(100% - 260px)";
                    }
                });
            }
        });

        function toggleLike(index) {
            const button = document.querySelectorAll('.like-button')[index];
            const span = button.querySelector('span');
            const icon = button.querySelector('i');

            if (button.classList.contains('liked')) {
                button.classList.remove('liked');
                span.textContent = parseInt(span.textContent) - 1;
                icon.classList.remove('fas');
                icon.classList.add('far');
            } else {
                button.classList.add('liked');
                span.textContent = parseInt(span.textContent) + 1;
                icon.classList.remove('far');
                icon.classList.add('fas');
            }
        }

        function shareQuote(quote, author) {
            const text = `"${quote}" - ${author}`;
            if (navigator.share) {
                navigator.share({
                    title: 'Developer Quote',
                    text: text,
                });
            } else {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Quote copied to clipboard!');
                });
            }
        }

        // Add some interactive effects
        document.querySelectorAll('.quote-card').forEach(card => {
            card.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>

</html>