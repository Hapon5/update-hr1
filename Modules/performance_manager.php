<?php
// DATABASE CONNECTION
$host = 'localhost';
$db = 'hr1';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

// HANDLE FORMS
$msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_equipment'])) {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $model = $_POST['model'];
        $purchase_date = $_POST['purchase_date'];
        $conn->query("INSERT INTO equipment (name, type, model, purchase_date)
                      VALUES ('$name', '$type', '$model', '$purchase_date')");
        $msg = "✅ Equipment added.";
    }

    if (isset($_POST['log_usage'])) {
        $equipment_id = $_POST['equipment_id'];
        $date = $_POST['date'];
        $hours = $_POST['hours_used'];
        $fuel = $_POST['fuel_used'];
        $load = $_POST['load_handled'];
        $conn->query("INSERT INTO usage_logs (equipment_id, date, hours_used, fuel_used, load_handled)
                      VALUES ('$equipment_id', '$date', '$hours', '$fuel', '$load')");
        $msg = "✅ Usage logged.";
    }
}
?>

<!DOCTYPE html>

<html>

<head>
    <link rel="icon" type="image/x-icon" href="../../Image/logo.png">
    <title>Crane & Truck Performance System</title>
    <style>
        body {
            font-family: Arial;
            margin: 40px;
        }

        nav a {
            margin-right: 20px;
            text-decoration: none;
            font-weight: bold;
        }

        section {
            margin-top: 30px;
        }

        form {
            margin-bottom: 30px;
        }

        input,
        select {
            margin-bottom: 10px;
            padding: 5px;
            width: 250px;
        }

        table {
            border-collapse: collapse;
            width: 90%;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        h2 {
            border-bottom: 2px solid #ddd;
            padding-bottom: 5px;
        }

        .msg {
            background: #e0ffe0;
            padding: 10px;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>

<body>

    <h1>🚜 Crane & Truck Performance Management</h1>
    <nav>
        <a href="#add">➕ Add Equipment</a>
        <a href="#log">📝 Log Usage</a>
        <a href="#report">📊 View Report</a>
    </nav>

    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>

    <section id="add">
        <h2>➕ Add Equipment</h2>
        <form method="post">
            <input type="text" name="name" placeholder="Equipment Name" required><br>
            <select name="type" required>
                <option value="">-- Select Type --</option>
                <option value="crane">Crane</option>
                <option value="truck">Truck</option>
            </select><br>
            <input type="text" name="model" placeholder="Model"><br>
            <input type="date" name="purchase_date" required><br>
            <input type="submit" name="add_equipment" value="Add Equipment">
        </form>
    </section>

    <section id="log">
        <h2>📝 Log Equipment Usage</h2>
        <form method="post">
            <select name="equipment_id" required>
                <option value="">-- Select Equipment --</option>
                <?php
                $equipments = $conn->query("SELECT id, name FROM equipment");
                while ($e = $equipments->fetch_assoc()) {
                    echo "<option value='{$e['id']}'>{$e['name']}</option>";
                }
                ?>
            </select><br>
            <input type="date" name="date" required><br>
            <input type="number" name="hours_used" step="0.1" placeholder="Hours Used" required><br>
            <input type="number" name="fuel_used" step="0.1" placeholder="Fuel Used (liters)" required><br>
            <input type="number" name="load_handled" step="0.1" placeholder="Load Handled (tons)" required><br>
            <input type="submit" name="log_usage" value="Log Usage">
        </form>
    </section>

    <section id="report">
        <h2>📊 Performance Report</h2>
        <table>
            <tr>
                <th>Equipment</th>
                <th>Type</th>
                <th>Total Hours</th>
                <th>Total Fuel Used</th>
                <th>Total Load Handled</th>
            </tr>
            <?php
            $sql = "SELECT e.name, e.type,
                           IFNULL(SUM(u.hours_used), 0) AS total_hours,
                           IFNULL(SUM(u.fuel_used), 0) AS total_fuel,
                           IFNULL(SUM(u.load_handled), 0) AS total_load
                    FROM equipment e
                    LEFT JOIN usage_logs u ON e.id = u.equipment_id
                    GROUP BY e.id";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['name']}</td>
                        <td>{$row['type']}</td>
                        <td>{$row['total_hours']} hrs</td>
                        <td>{$row['total_fuel']} L</td>
                        <td>{$row['total_load']} tons</td>
                      </tr>";
            }
            ?>
        </table>
    </section>

</body>

</html>
<?php
// --- CONFIG ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "performance_db";

// --- CONNECT TO DB ---
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

// --- CREATE DATABASE AND TABLES IF NOT EXISTS ---
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    review_date DATE NOT NULL,
    score INT NOT NULL,
    comments TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
)");

// --- SEED SAMPLE EMPLOYEES IF EMPTY ---
$checkEmployees = $conn->query("SELECT COUNT(*) as total FROM employees")->fetch_assoc()['total'];
if ($checkEmployees == 0) {
    $conn->query("INSERT INTO employees (name) VALUES ('Alice'), ('Bob'), ('Charlie')");
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id'])) {
    $employee_id = (int) $_POST['employee_id'];
    $score = (int) $_POST['score'];
    $comments = $conn->real_escape_string($_POST['comments']);
    $review_date = date('Y-m-d');

    $conn->query("INSERT INTO performance_reviews (employee_id, review_date, score, comments)
                  VALUES ($employee_id, '$review_date', $score, '$comments')");
    echo "<p style='color:green;'>Review submitted!</p>";
}

// --- GET EMPLOYEES ---
$employees = $conn->query("SELECT * FROM employees");

// --- GET REPORT DATA ---
$report = $conn->query("
    SELECT e.name, COUNT(r.id) as reviews, AVG(r.score) as avg_score
    FROM employees e
    LEFT JOIN performance_reviews r ON e.id = r.employee_id
    GROUP BY e.id
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Performance Management System</title>
</head>

<body>
    <h2>Submit Performance Review</h2>
    <form method="POST">
        <label>Employee:</label>
        <select name="employee_id" required>
            <?php while ($emp = $employees->fetch_assoc()): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Score (1-10):</label>
        <input type="number" name="score" min="1" max="10" required><br><br>

        <label>Comments:</label><br>
        <textarea name="comments" rows="4" cols="40"></textarea><br><br>

        <input type="submit" value="Submit Review">
    </form>

    <hr>

    <h2>Performance Summary</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>Employee</th>
            <th>Average Score</th>
            <th>Number of Reviews</th>
        </tr>
        <?php while ($row = $report->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= $row['avg_score'] ? number_format($row['avg_score'], 2) : 'N/A' ?></td>
                <td><?= $row['reviews'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>
<?php
// --- CONFIG ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "performance_db";

// --- CONNECT TO DB ---
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

// --- CREATE DATABASE AND TABLES IF NOT EXISTS ---
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    review_date DATE NOT NULL,
    score INT NOT NULL,
    comments TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
)");

// --- SEED SAMPLE EMPLOYEES IF EMPTY ---
$checkEmployees = $conn->query("SELECT COUNT(*) as total FROM employees")->fetch_assoc()['total'];
if ($checkEmployees == 0) {
    $conn->query("INSERT INTO employees (name) VALUES ('Alice'), ('Bob'), ('Charlie')");
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id'])) {
    $employee_id = (int) $_POST['employee_id'];
    $score = (int) $_POST['score'];
    $comments = $conn->real_escape_string($_POST['comments']);
    $review_date = date('Y-m-d');

    $conn->query("INSERT INTO performance_reviews (employee_id, review_date, score, comments)
                  VALUES ($employee_id, '$review_date', $score, '$comments')");
    echo "<p style='color:green;'>Review submitted!</p>";
}

// --- GET EMPLOYEES ---
$employees = $conn->query("SELECT * FROM employees");

// --- GET REPORT DATA ---
$report = $conn->query("
    SELECT e.name, COUNT(r.id) as reviews, AVG(r.score) as avg_score
    FROM employees e
    LEFT JOIN performance_reviews r ON e.id = r.employee_id
    GROUP BY e.id
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Performance Management System</title>
</head>

<body>
    <h2>Submit Performance Review</h2>
    <form method="POST">
        <label>Employee:</label>
        <select name="employee_id" required>
            <?php while ($emp = $employees->fetch_assoc()): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Score (1-10):</label>
        <input type="number" name="score" min="1" max="10" required><br><br>

        <label>Comments:</label><br>
        <textarea name="comments" rows="4" cols="40"></textarea><br><br>

        <input type="submit" value="Submit Review">
    </form>

    <hr>

    <h2>Performance Summary</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>Employee</th>
            <th>Average Score</th>
            <th>Number of Reviews</th>
        </tr>
        <?php while ($row = $report->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= $row['avg_score'] ? number_format($row['avg_score'], 2) : 'N/A' ?></td>
                <td><?= $row['reviews'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>