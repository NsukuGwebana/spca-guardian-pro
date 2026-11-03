<?php
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
include 'databaseConnection.php';

$idnum = $_GET['idnum']; //Extract idnumber from the url

$sql_select = "SELECT * FROM user where idnum = '$idnum' ";
 $result = $conn->query($sql_select);
$row2 = $result->fetch_assoc();

echo "User idnumber = ". $idnum . " extracted from the URL using GET<br>";

echo "User idnumber = ". $row2['idnum'] . " extracted from database using query";

//if($_SERVER['REQUEST_METHOD'] == "POST")

if(isset($_POST['update'])) { //update is name of submit button
    $dog_Id = $_GET['idnumber']; 
    $surname = $_POST['surname'];
    $firstname = $_POST['firstname'];
    $pword = $_POST['userpassword'];
    $username = $_POST['username'];
    $userrole = $_POST['userrole'];

    $sql_update = "UPDATE dogsrecords  SET    username = '$username',
         surname = '$surname',
         firstname = '$dbreed',
         userpassword = '$pword',
         username = '$username',
         userrole = '$userrole' WHERE  didnum = '$dog_Id'";

    $query_execute = $conn->query($sql_update);

    if($query_execute){
    // echo "<script>alert('Record successfully updated')</script>"; // Commented out
    header("Location:displaySystemUser.php");

      $conn->close();
    } else {

        echo "<script>alert('Record could not update failed. Retry')</script>";
        header("Location:displaySystemUser.php");

}
  }
?>
<!DOCTYPE html>

<html>
    <head>
    <title></title>

    <style>
    body{

    background-color: lightblue;

    }
    h2 {
        color: blue;
    }
        </style>

</head>

<body>

<h2>User record update form</h2>
<hr>


    <form action="" method="POST" enctype="multipart/form-data">

        <table width = 25%>

        <tr><td>ID Number: </td><td> <input type="text" id="idnum" name="idnum" value="<?php echo $row2['didnum']; ?>"></td></tr>

        <tr><td>Name:</td><td> <input type="text" id="dname" name="dname" value="<?php echo $row2['dname']; ?>"></td></tr>

        <tr><td>Gender:</td><td><input type="text" id="dgender" name="dgender" value="<?php echo $row2['dgender']; ?>"></td></tr><tr>

        <td>Breed:</td><td><input type="text" id="dbreed" name="dbreed" value="<?php echo $row2['dbreed']; ?>"></td></tr>

        <tr><td>Color:</td><td><input type="text" id="dcolor" name="dcolor" value="<?php echo $row2['dcolor']; ?>"></td></tr>

        <tr><td>Age:</td><td><input type="text" id="dage" name="dage" size="4" value="<?php echo $row2['dage']; ?>" > (Yrs)</td></tr>

        <tr><td>Height:</td><td>
        <input type="text" id="dheight" name="dheight" size="4" value="<?php echo $row2['dheight']; ?>"> (CM)</td></tr>
        
        <tr><td>Weight:</td><td>
        <input type="text" id="dweight" name="dweight" size="4" value="<?php echo $row2['dweight']; ?>"> (Kgs)</td></tr>
        
        <tr><td>Image:</td><td>
        <input type="file" name="picture" id="picture"></td></tr>
            
          <tr><td colspan="2"><input type="submit" name="update" value="Update dog record"></td></tr>
    </table>
    </form>
</body>
</html>
