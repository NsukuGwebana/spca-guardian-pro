<?php
$ALLOWED_ROLES = ['Administrator']; // who may open this page
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
require_once __DIR__ . '/bootstrap.php';
// Database connection
$conn = db();


// Messages sent through the Contact Us form (saved by message.php)
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $like = '%' . $search . '%';
    $result = q("SELECT * FROM message_submissions
                 WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR subject LIKE ?
                 ORDER BY submission_date DESC", [$like, $like, $like, $like]);
} else {
    $result = q("SELECT * FROM message_submissions ORDER BY submission_date DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SPCA Contact Form Submissions</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #fff;
}

.overlay {
    background: rgba(19, 79, 92, 0.85);
    min-height: 100vh;
    padding: 30px;
}

h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #e0f7fa;
}

.search-row {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
}

.search-form {
    display: flex;
    width: 100%;
    max-width: 500px;
}

.search-input {
    padding: 10px;
    border-radius: 4px 0 0 4px;
    border: 1px solid #60a5fa;
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    width: 100%;
}

.search-input::placeholder {
    color: #e0f7fa;
}

.search-btn {
    padding: 10px 16px;
    border-radius: 0 4px 4px 0;
    text-decoration: none;
    font-weight: bold;
    color: #fff;
    background-color: #60a5fa;
    border: 1px solid #60a5fa;
    cursor: pointer;
}

.search-btn:hover {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #60a5fa;
    padding: 8px;
    text-align: left;
}

th {
    background-color: #134f5c;
    color: #fff;
}

td {
    background-color: rgba(255,255,255,0.1);
}

.back-btn {
    display: block;
    width: fit-content;
    background: #60a5fa;
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: bold;
    margin: 20px auto;
    text-align: center;
    text-decoration: none;
}

.back-btn:hover {
    background: #3b82f6;
}

.result-count {
    text-align: center;
    margin-bottom: 10px;
    font-style: italic;
}

.message-cell {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.message-cell:hover {
    white-space: normal;
    overflow: visible;
    position: absolute;
    background: rgba(19, 79, 92, 0.95);
    border: 1px solid #60a5fa;
    padding: 10px;
    z-index: 100;
    max-width: 400px;
}
</style>
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<div class="overlay">
    <h1>SPCA Contact Form Submissions</h1>

    <div class="search-row">
        <form method="GET" class="search-form">
            <input type="text" name="search" class="search-input" placeholder="Search submissions by name, email or subject..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>

    <?php 
    if ($result && $result->num_rows > 0): 
        $count = $result->num_rows;
    ?>
        <div class="result-count">
            <?php echo $count; ?> submission<?php echo $count !== 1 ? 's' : ''; ?> found
            <?php if (!empty($search)): ?>
                for "<?php echo htmlspecialchars($search); ?>"
            <?php endif; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Submission Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td class="message-cell" title="<?= htmlspecialchars($row['message']) ?>">
                        <?= htmlspecialchars($row['message']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['submission_date']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center;">
            <?php if (!empty($search)): ?>
                No submissions found for "<?php echo htmlspecialchars($search); ?>"
            <?php else: ?>
                No contact form submissions found in the database.
                
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a>
</div>
</body>
</html>
<?php $conn->close(); ?>