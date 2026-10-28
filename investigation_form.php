<?php
// Get cruelty_id from query string
$cruelty_id = isset($_GET['cruelty_id']) ? intval($_GET['cruelty_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cruelty Investigation</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #134f5c;
}

.overlay {
    background: rgba(19, 79, 92, 0.85);
    min-height: 100vh;
    padding: 30px 0;
}

.container {
    max-width: 700px;
    margin: 40px auto;
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
}
h1 {
    text-align: center;
    margin-bottom: 25px;
    color: #134f5c;
}
form label {
    font-weight: bold;
    margin-top: 15px;
    display: block;
    color: #134f5c;
}
form input, form textarea, form select {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border: 1px solid #134f5c;
    border-radius: 6px;
    font-size: 1rem;
}
.btn {
    display: inline-block;
    background: #134f5c;
    color: #fff;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    border: none;
    margin-top: 20px;
}
.btn:hover {
    background: #0f3f4a;
}
.reset-btn {
    background: #60a5b3;
    margin-left: 10px;
}
.reset-btn:hover {
    background: #3d8a95;
}
.back-btn {
    display: block;
    width: fit-content;
    background: #60a5b3;
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    margin: 20px auto 0;
    text-align: center;
    cursor: pointer;
}
.back-btn:hover {
    background: #3d8a95;
}
</style>
</head>
<body>
<div class="overlay">
    <div class="container">
        <h1>Cruelty Investigation</h1>
        <form action="save_investigation.php" method="POST">
            <!-- Hidden cruelty ID -->
            <input type="hidden" name="cruelty_id" value="<?= $cruelty_id ?>">

            <!-- Investigator Name -->
            <label for="investigator">Investigator Name:</label>
            <input type="text" name="investigator" id="investigator" required>

            <!-- Case Status -->
            <label for="status">Case Status:</label>
            <select name="status" id="status" required>
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
                <option value="Closed">Closed</option>
            </select>

            <!-- Actions Taken -->
            <label for="actions">Actions Taken:</label>
            <textarea name="actions" id="actions" rows="4" placeholder="Describe steps taken..."></textarea>

            <!-- Follow-up Required -->
            <label for="follow_up">Follow-up Required:</label>
            <select name="follow_up" id="follow_up">
                <option value="No">No</option>
                <option value="Yes">Yes</option>
            </select>

            <!-- Investigation Notes -->
            <label for="notes">Investigation Notes:</label>
            <textarea name="notes" id="notes" rows="5" placeholder="Additional details..."></textarea>

            <button type="submit" class="btn">Save Investigation</button>
            <button type="reset" class="btn reset-btn">Reset Form</button>
        </form>

        <button class="back-btn" onclick="window.location.href='view_reports.php'">Back to Reports</button>
    </div>
</div>
</body>
</html>