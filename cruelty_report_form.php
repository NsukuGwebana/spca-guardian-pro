<?php require_once __DIR__ . '/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Animal Cruelty | SPCA Guardian Pro</title>

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
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<script src="site-nav.js"></script>
<div class="overlay">
<div class="container">
    <h2>Report animal cruelty</h2>

    <form action="cruelty_report.php" method="POST">
        <p class="form-intro">Tell us what you saw. If an animal is in immediate danger, also call us straight away.</p>

        <label for="animal_type">Which animal? *</label>
        <select name="animal_type" id="animal_type" required>
            <option value="">Choose an animal</option>
            <option value="Dog">Dog</option>
            <option value="Cat">Cat</option>
            <option value="Bird">Bird</option>
            <option value="Horse">Horse</option>
            <option value="Other">Other</option>
        </select>

        <label for="cruelty_type">What is happening? *</label>
        <select name="cruelty_type" id="cruelty_type" required>
            <option value="">Choose one</option>
            <option value="Neglect">Neglect (no food, water or shelter)</option>
            <option value="Physical Abuse">Physical abuse</option>
            <option value="Abandonment">Abandoned</option>
            <option value="Illegal Trade">Illegal selling or trading</option>
            <option value="Other">Something else</option>
        </select>

        <label for="location">Where? *</label>
        <input type="text" name="location" id="location" placeholder="Street and suburb" required>

        <label for="description">What did you see? *</label>
        <textarea name="description" id="description" rows="4" required></textarea>

        <div class="two-col">
            <div><label for="reporter_name">Your name *</label>
            <input type="text" name="reporter_name" id="reporter_name" autocomplete="name" required></div>
            <div><label for="contact_info">Phone or email *</label>
            <input type="text" name="contact_info" id="contact_info" required></div>
        </div>

        <div class="btn-container">
            <button type="submit" class="btn">Send report</button>
            <a href="Homepage.html" class="back-btn">&larr; Back to home</a>
        </div>
    </form>
</div>
</div>
</body>
</html>
