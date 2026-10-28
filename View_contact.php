<?php
require_once __DIR__ . '/config.php';
// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Try to detect the correct table name
$table_name = "";
$tables = $conn->query("SHOW TABLES");
while ($table = $tables->fetch_array()) {
    if (strpos(strtolower($table[0]), 'contact') !== false || strpos(strtolower($table[0]), 'submission') !== false) {
        $table_name = $table[0];
        break;
    }
}

// If no table found, try a different approach
if (empty($table_name)) {
    // Check if we can find a table with the expected columns
    $tables = $conn->query("SHOW TABLES");
    while ($table = $tables->fetch_array()) {
        $table_name_test = $table[0];
        $columns = $conn->query("SHOW COLUMNS FROM $table_name_test");
        $has_columns = 0;
        while ($column = $columns->fetch_assoc()) {
            if (in_array($column['Field'], ['first_name', 'last_name', 'email', 'subject', 'message'])) {
                $has_columns++;
            }
        }
        if ($has_columns >= 5) {
            $table_name = $table_name_test;
            break;
        }
    }
}

// If still no table found, use a default name
if (empty($table_name)) {
    $table_name = "contact_submissions";
}

// Check if search parameter is provided
$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}

// Run query to get contact submissions with optional search
$sql = "SELECT * FROM $table_name";
if (!empty($search)) {
    $sql .= " WHERE email LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR subject LIKE '%$search%'";
}
$sql .= " ORDER BY submission_date DESC";

$result = $conn->query($sql);

// If query failed, try without the date ordering
if (!$result) {
    $sql = "SELECT * FROM $table_name";
    if (!empty($search)) {
        $sql .= " WHERE email LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR subject LIKE '%$search%'";
    }
    $result = $conn->query($sql);
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
</head>
<body>
<div class="overlay">
    <h1>SPCA Contact Form Submissions</h1>
    
    <?php if (!empty($table_name)): ?>
    <p style="text-align: center; font-style: italic;">Using table: <?php echo $table_name; ?></p>
    <?php endif; ?>

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
                <?php if ($conn->error): ?>
                    <br>Error: <?php echo $conn->error; ?>
                <?php endif; ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <a href="admin dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
</div>
</body>
</html>
<?php $conn->close(); ?>