<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
        <table width = "35%" bgcolor = "lightyellow"> <tr><th> User Name</th> <th> Login attemt</th> <th>Comment</th></tr>
    <?php
    include('databaseConnection.php');

        //retrieves all login attempts
            $spl5 = "SELECT * FROM logtrails";
            $sql_result = $conn->query($spl5) ;
            
            while ($row =  $sql_result->fetch_assoc() ){?> <!-- code to display reocrds
            <tr>
                <td> <?php echo $row['userid']; ?></td>
                <td> <?php echo $row['attempttime']; ?></td>
                <td> <?php echo $row['comment']; ?></td>
        </tr>
        
           <?php }       


    ?>
    </tabe>

</body>
</html>