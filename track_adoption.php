<?php
require_once __DIR__ . '/bootstrap.php';

$conn = db();

$application = null;
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_number = trim($_POST['id_number'] ?? '');

    $stmt = $conn->prepare("
        SELECT a.*, ani.Aname AS animal_name, ani.Species, ani.Animal_breed
        FROM adoptions a
        JOIN animals ani ON a.animal_id = ani.id
        WHERE a.id_number = ?
        ORDER BY a.created_at DESC LIMIT 1
    ");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $application = $result->fetch_assoc();
    } else {
        $message = "No adoption application found for that ID number.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Track Adoption | SPCA Guardian Pro</title>
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
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .tracking-box {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #e0f7fa;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        input[type="text"] {
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
        }

        button {
            background: #60a5fa;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
            color: #fff;
            cursor: pointer;
        }

        button:hover {
            background: #3b82f6;
        }

        .status-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }

        .status-box { background: #fff; color: #1d2f33; text-align: left; font-weight: 400; border: 1px solid #cfdcdf; }
        .steps { list-style: none; display: flex; padding: 0; margin: 16px 0; counter-reset: s; }
        .steps li { flex: 1; text-align: center; font-size: .8rem; color: #5b6e72; position: relative; padding-top: 34px; }
        .steps li::before { counter-increment: s; content: counter(s); position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 26px; height: 26px; line-height: 26px; border-radius: 50%; background: #e6eff1; color: #134f5c; font-weight: 700; z-index: 1; }
        .steps li::after { content: ""; position: absolute; top: 12px; left: -50%; width: 100%; height: 3px; background: #e6eff1; }
        .steps li:first-child::after { display: none; }
        .steps li.done::before, .steps li.now::before { background: #134f5c; color: #fff; }
        .steps li.done::after, .steps li.now::after { background: #134f5c; }
        .steps li.now { color: #134f5c; font-weight: 700; }
        .steps li.done::before { content: "\2713"; }
        .help { background: #e6eff1; border-radius: 8px; padding: 10px; }
        .step-rejected { color: #c0392b; font-weight: 700; }

        .back-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
            background: #60a5fa;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        .back-btn:hover { background: #3b82f6; }
    </style>
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<script src="site-nav.js"></script>
<div class="overlay">
    <div class="tracking-box">
        <h1>Track Your Adoption</h1>
        <form method="POST">
            <input type="text" name="id_number" placeholder="Enter your ID number" required>
            <button type="submit">Track</button>
        </form>

        <?php if ($message): ?>
            <p style="text-align:center; margin-top:15px; color:#ffd700;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($application):
            $st = $application['status'];
            $steps = ['Pending' => 'Applied', 'Home visit' => 'Home visit', 'Approved' => 'Approved', 'Adopted' => 'Adopted'];
            $reached = array_search($st, array_keys($steps), true); ?>
            <div class="status-box">
                <p><strong><?= e($application['animal_name']) ?></strong> (<?= e($application['Species']) ?> / <?= e($application['Animal_breed']) ?>)</p>
                <p>Applied on <?= e(date('j F Y', strtotime($application['created_at']))) ?></p>
                <?php if ($st === 'Rejected'): ?>
                    <p class="step-rejected">Not approved</p>
                <?php else: ?>
                <ol class="steps">
                    <?php $n = 0; foreach ($steps as $key => $label): ?>
                        <li class="<?= $n < $reached ? 'done' : ($n === $reached ? 'now' : '') ?>"><?= e($label) ?></li>
                    <?php $n++; endforeach; ?>
                </ol>
                <?php endif; ?>
                <p class="help"><?= e(adoption_status_help($st)) ?></p>
                <?php if ($st === 'Home visit' && $application['home_visit_at']): ?>
                    <p><strong>Home visit:</strong> <?= e(date('l j F Y, H:i', strtotime($application['home_visit_at']))) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <a href="Homepage.html" class="back-btn">&larr; Back to home</a>
    </div>
</div>
</body>
</html>
