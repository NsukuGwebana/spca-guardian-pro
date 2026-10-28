<?php
session_start();
require_once ("databaseConnection.php");

if(isset($_REQUEST['submit'])){
    $search = trim($_REQUEST['search_term']);
    $search_type = $_REQUEST['search_type'];
    
    $_SESSION['last_search'] = [
        'term' => $search,
        'type' => $search_type,
        'time' => time()
    ];
    
    if(!isset($_SESSION['search_history'])) {
        $_SESSION['search_history'] = [];
    }
    
    $conn = new mysqli($serverName, $username, $password, $database);
    if ($conn->connect_error){
        die("<p class=\"error\">Connection to the database failed!</p>");
    }
    
    switch($search_type) {
        case 'surname':
            $sql = "SELECT idnumber, surname, firstname, userrole FROM users 
                    WHERE surname LIKE ? ORDER BY surname ASC";
            $search_type_text = "Surname";
            break;
        case 'firstname':
            $sql = "SELECT idnumber, surname, firstname, userrole FROM users 
                    WHERE firstname LIKE ? ORDER BY firstname ASC";
            $search_type_text = "First Name";
            break;
        case 'userrole':
            $sql = "SELECT idnumber, surname, firstname, userrole FROM users 
                    WHERE userrole LIKE ? ORDER BY userrole ASC";
            $search_type_text = "User Role";
            break;
        case 'idnumber':
        default:
            $sql = "SELECT idnumber, surname, firstname, userrole FROM users 
                    WHERE idnumber LIKE ? ORDER BY idnumber ASC";
            $search_type_text = "ID Number";
            break;
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === FALSE) {
        die("<p class=\"error\">Unable to prepare search query!</p>");
    }
    
    $search_param = "%" . $search . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $records = $result->num_rows;
    
    array_unshift($_SESSION['search_history'], [
        'term' => $search,
        'type' => $search_type,
        'results_count' => $records,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $_SESSION['search_history'] = array_slice($_SESSION['search_history'], 0, 5);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #e0f7fa;
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

        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            margin: 2px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            color: #fff;
            cursor: pointer;
            background-color: #60a5fa;
        }

        .action-btn:hover {
            background-color: #3b82f6;
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
    </style>
</head>
<body>
<div class="overlay">
    <h2>Search Results</h2>

    <?php
    if(isset($_REQUEST['submit'])){
        echo "<p>$records results found containing <strong>\"$search\"</strong> in <strong>$search_type_text</strong>:</p>";
        
        if ($records > 0) {
            echo "<table>";
            echo "<tr>
                    <th>ID Number</th>
                    <th>Surname</th>
                    <th>First Name</th>
                    <th>Role</th>
                  </tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['idnumber']}</td>";
                echo "<td>{$row['surname']}</td>";
                echo "<td>{$row['firstname']}</td>";
                echo "<td>{$row['userrole']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No results found.</p>";
        }
        
        if(!empty($_SESSION['search_history'])) {
            echo "<h3>Recent Searches</h3><ul>";
            foreach($_SESSION['search_history'] as $history) {
                echo "<li>" . htmlspecialchars($history['term']) . " ({$history['type']}) - " . 
                     $history['results_count'] . " results - " . $history['timestamp'] . "</li>";
            }
            echo "</ul>";
        }
        
        $stmt->close();
        $conn->close();
        
        echo "<a href=\"SearchUser.html\" class='back-btn'>Back to Search</a>";
    } else {
        echo "<p>No search query submitted.</p>";
        echo "<a href=\"SearchUser.html\" class='back-btn'>Back to Search</a>";
    }
    ?>
</div>
</body>
</html>
