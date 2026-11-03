<?php
$ALLOWED_ROLES = ['Administrator', 'Vet', 'Volunteer']; // who may open this page
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');
$conn = db();


$sql = "
    (SELECT 'New adoption request' as msg, created_at as date FROM adoptions ORDER BY created_at DESC LIMIT 3)
    UNION
    (SELECT 'New cruelty report' as msg, created_at as date FROM cruelty_report ORDER BY created_at DESC LIMIT 3)
    UNION
    (SELECT 'New donation received' as msg, ddate as date FROM donations ORDER BY ddate DESC LIMIT 3)
    ORDER BY date DESC LIMIT 5
";
$result = $conn->query($sql);

$notifications = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row['msg'];
    }
}
echo json_encode(["count" => count($notifications), "notifications" => $notifications]);

