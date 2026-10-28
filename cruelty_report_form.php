<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Animal Cruelty</title>

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
    margin: auto;
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
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #134f5c;
}

form input, form select, form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #134f5c;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 1rem;
}

form textarea {
    resize: vertical;
    min-height: 80px;
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
    <h1> Report Animal Cruelty</h1>

    <form action="cruelty_report.php" method="POST">
        <label for="reporter_name">Name:</label>
        <input type="text" name="reporter_name" id="reporter_name" required>

        <label for="contact_info">Contact Info:</label>
        <input type="text" name="contact_info" id="contact_info" required>

        <label for="animal_type">Animal Type:</label>
        <select name="animal_type" id="animal_type" required>
            <option value="">-- Select Animal --</option>
            <option value="Dog">Dog</option>
            <option value="Cat">Cat</option>
            <option value="Bird">Bird</option>
            <option value="Horse">Horse</option>
            <option value="Other">Other</option>
        </select>

        <label for="cruelty_type">Type of Cruelty:</label>
        <select name="cruelty_type" id="cruelty_type" required>
            <option value="">-- Select Type --</option>
            <option value="Neglect">Neglect</option>
            <option value="Physical Abuse">Physical Abuse</option>
            <option value="Abandonment">Abandonment</option>
            <option value="Illegal Trade">Illegal Trade</option>
            <option value="Other">Other</option>
        </select>

        <label for="location">Location of Incident:</label>
        <input type="text" name="location" id="location" required>

        <label for="witnesses">Witnesses (if any):</label>
        <input type="text" name="witnesses" id="witnesses">

        <label for="date">Date of Incident:</label>
        <input type="date" name="date" id="date" required>

        <!-- Description Field Added Here -->
        <label for="description">Description of the Incident:</label>
        <textarea name="description" id="description" rows="5" required placeholder="Please provide a detailed description of the incident."></textarea>

        <button type="submit" class="btn">Submit Report</button>
        <button type="reset" class="btn reset-btn">Reset Form</button>
    </form>

    <!-- Back Button -->
   
    <button class="back-btn" onclick="history.back()"> Back</button>
</div>
</div>
</body>
</html>
