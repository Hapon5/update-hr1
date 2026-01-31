<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sender Performance API</title>
</head>
<body>
    <h1>Check Console (F12) for API Response</h1>

    <script>
        const data = {
            employee_id: 1,
            review_date: "2026-01-31",
            review_type: "Monthly",
            kpi_score: 95.5,
            attendance_score: 100,
            supervisor_quality_rating: 5,
            productivity_score: 98,
            promotion_recommended: 1,
            comments: "Sent using the requested JS Fetch format."
        };

        fetch("performance.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            console.log("Success:", result);
            document.body.innerHTML += "<pre>" + JSON.stringify(result, null, 2) + "</pre>";
        })
        .catch(error => {
            console.error("Error:", error);
            document.body.innerHTML += "<p style='color:red'>Error: " + error.message + "</p>";
        });
    </script>
</body>
</html>
