<?php
require_once __DIR__ . '/config.php';
// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if search parameter is provided
$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}

// Run query to get subscribers with optional search
$sql = "SELECT * FROM spca_subscribers";
if (!empty($search)) {
    $sql .= " WHERE email LIKE '%$search%'";
}
$sql .= " ORDER BY subscription_date DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SPCA Newsletter Subscribers</title>
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
</style>
</head>
<body>
<div class="overlay">
    <h1>SPCA Newsletter Subscribers</h1>

    <div class="search-row">
        <form method="GET" class="search-form">
            <input type="text" name="search" class="search-input" placeholder="Search subscribers by email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>

    <?php 
    if ($result && $result->num_rows > 0): 
        $count = $result->num_rows;
    ?>
        <div class="result-count">
            <?php echo $count; ?> subscriber<?php echo $count !== 1 ? 's' : ''; ?> found
            <?php if (!empty($search)): ?>
                for "<?php echo htmlspecialchars($search); ?>"
            <?php endif; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Subscription Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['subscription_date']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center;">
            <?php if (!empty($search)): ?>
                No subscribers found for "<?php echo htmlspecialchars($search); ?>"
            <?php else: ?>
                No subscribers found in the database.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <a href="admin dashboard.php" class="back-btn">Back to Dashboard</a>
</div>
</body>
</html>
<?php $conn->close(); ?>