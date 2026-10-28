<?php   
require_once 'databaseConnection.php';

// ✅ Handle cancellation request before fetching data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancelId = intval($_POST['cancel_id']);
    $stmt = $conn->prepare("UPDATE appointments SET app_status = 'Cancelled' WHERE app_id = ?");
    $stmt->bind_param("i", $cancelId);
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid resubmission
    header("Location: view_appointments.php?filter=" . ($_GET['filter'] ?? 'all') . "&cancelled=1");
    exit();
}

// Fetch counts
function getCount($conn, $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE app_status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0];
}

$scheduledCount = getCount($conn, 'Scheduled');
$completedCount = getCount($conn, 'Completed');
$cancelledCount = getCount($conn, 'Cancelled');

// Build filter & search query
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "
    SELECT 
        a.Aname AS animal_name,
        a.id AS animal_id,
        a.animal_picture AS animal_picture,
        ap.app_id,
        ap.app_date,
        ap.vet_id,
        ap.Reason AS reason,
        ap.app_status
    FROM appointments ap
    JOIN animals a ON ap.animal_id = a.id
";

$where = [];

if ($filter == 'today') {
    $where[] = "DATE(ap.app_date) = CURDATE()";
} elseif ($filter == 'upcoming') {
    $where[] = "ap.app_date > NOW() AND ap.app_status = 'Scheduled'";
} elseif ($filter == 'completed') {
    $where[] = "ap.app_status = 'Completed'";
} elseif ($filter == 'cancelled') {
    $where[] = "ap.app_status = 'Cancelled'";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where[] = "(a.Aname LIKE '%$search%' OR ap.vet_id LIKE '%$search%' OR ap.Reason LIKE '%$search%')";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY ap.app_date DESC";
$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error . " | Query: " . $query);
}

// ✅ Group appointments by animal_id
$appointmentsByAnimal = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['animal_id'];

    if (!isset($appointmentsByAnimal[$id])) {
        $appointmentsByAnimal[$id] = [
            'animal_name' => $row['animal_name'],
            'animal_id' => $row['animal_id'],
            'animal_picture' => $row['animal_picture'],
            'appointments' => []
        ];
    }

    $appointmentsByAnimal[$id]['appointments'][] = [
        'app_id' => $row['app_id'],
        'app_date' => $row['app_date'],
        'vet_id' => $row['vet_id'],
        'reason' => $row['reason'],
        'app_status' => $row['app_status']
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointments Information</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 20px;
            color: #134f5c;
        }

        .overlay {
            background: rgba(19, 79, 92, 0.85);
            padding: 30px;
            border-radius: 12px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 15px;
            color: #fff;
        }

        .btn {
            display: inline-block;
            background: #134f5c;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 0 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #0f3f4a; }

        .back-btn {
            display: inline-block;
            background: #60a5b3;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 0 5px;
            border: none;
            cursor: pointer;
        }
        .back-btn:hover { background: #3d8a95; }

        .filters, .search {
            text-align: center;
            margin-bottom: 20px;
        }

        .filters a {
            display: inline-block;
            padding: 8px 20px;
            margin: 0 10px;
            text-decoration: none;
            color: white;
            background-color: #134f5c;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .filters a.active { background-color: #2980b9; }
        .filters a:hover { background-color: #0f3f4a; }

        .search input[type="text"] {
            padding: 8px;
            width: 250px;
            border: 1px solid #134f5c;
            border-radius: 4px;
        }
        .search button {
            padding: 8px 15px;
            background: #134f5c;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .search button:hover { background-color: #0f3f4a; }

        .appointments {
            max-width: 1000px;
            margin: 0 auto;
        }

        .appointment {
            display: flex;
            align-items: flex-start;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.25);
            border-left: 6px solid #134f5c;
            transition: transform 0.3s ease;
        }
        .appointment:hover { transform: scale(1.02); }

        .animal-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 20px;
            border: 2px solid #134f5c;
        }

        .appointment-details { flex: 1; }

        .info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .info i {
            margin-right: 10px;
            font-size: 1.2em;
            color: #134f5c;
            width: 25px;
            text-align: center;
        }
        .info p { margin: 0; }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9em;
        }
        .Scheduled { background: #f39c12; color: white; }
        .Completed { background: #2ecc71; color: white; }
        .Cancelled { background: #e74c3c; color: white; }

        .cancel-btn {
            margin-top: 10px;
            padding: 8px 14px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .cancel-btn:hover { background: #c0392b; }

        .bottom-bar {
            text-align: left;
            margin-top: 30px;
            padding-left: 20px;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }
    </style>
</head>
<body>
<div class="overlay">

<h1>Animal Appointments</h1>

<?php if (isset($_GET['cancelled'])): ?>
    <p style="text-align:center; color:lightgreen; font-weight:bold;">✅ Appointment cancelled successfully.</p>
<?php endif; ?>

<div class="filters">
    <?php
    $filters = ['all' => 'All', 'today' => 'Today', 'upcoming' => 'Upcoming', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    foreach ($filters as $key => $label) {
        $active = ($filter === $key || ($filter === '' && $key === 'all')) ? 'active' : '';
        echo "<a href='?filter=$key' class='$active'>$label</a>";
    }
    ?>
</div>

<div class="search">
    <form method="GET">
        <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        <?php if (!empty($filter) && $filter !== 'all'): ?>
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <?php endif; ?>
        <button type="submit">Search</button>
    </form>
</div>

<div class="appointments">
    <?php if (!empty($appointmentsByAnimal)): ?>
        <?php foreach ($appointmentsByAnimal as $animal): ?>
            <div class="appointment">
                <img src="<?= htmlspecialchars($animal['animal_picture']) ?>" alt="<?= htmlspecialchars($animal['animal_name']) ?>" class="animal-image">

                <div class="appointment-details">
                    <div class="info"><i class="fas fa-paw"></i>
                        <p><strong>Animal:</strong> <?= htmlspecialchars($animal['animal_name']) ?> (ID: <?= $animal['animal_id'] ?>)</p>
                    </div>

                    <?php foreach ($animal['appointments'] as $ap): ?>
                        <hr>
                        <div class="info"><i class="fas fa-id-card"></i>
                            <p><strong>Appointment ID:</strong> <?= $ap['app_id'] ?></p>
                        </div>
                        <div class="info"><i class="fas fa-notes-medical"></i>
                            <p><strong>Reason:</strong> <?= htmlspecialchars($ap['reason']) ?></p>
                        </div>
                        <div class="info"><i class="fas fa-calendar-day"></i>
                            <p><strong>Date:</strong> <?= $ap['app_date'] ?></p>
                        </div>
                        <div class="info"><i class="fas fa-user-md"></i>
                            <p><strong>Vet ID:</strong> <?= $ap['vet_id'] ?></p>
                        </div>
                        <div class="info"><i class="fas fa-circle"></i>
                            <p><strong>Status:</strong> 
                                <span class="status <?= $ap['app_status'] ?>">
                                    <?= htmlspecialchars($ap['app_status']) ?>
                                </span>
                            </p>
                        </div>

                        <?php if ($ap['app_status'] === 'Scheduled'): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                <input type="hidden" name="cancel_id" value="<?= $ap['app_id'] ?>">
                                <button type="submit" class="cancel-btn">Cancel Appointment</button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; color:#fff;">No appointments found<?= !empty($search) ? " for \"$search\"" : '' ?>.</p>
    <?php endif; ?>
</div>

<!-- ✅ Back button at the bottom left -->
<div class="bottom-bar">
    <a href="vetDashboard.php" class="btn back-btn">
        <i></i> Back to Dashboard
    </a>
</div>

</div> <!-- overlay -->
</body>
</html>
