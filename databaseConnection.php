<?php
require_once __DIR__ . '/config.php';
//variable declaration
$serverName=DB_HOST;
$username=DB_USER; 
$password=DB_PASS;
$database=DB_NAME;
//connection string
$conn=new mysqli($serverName,$username,$password,$database);
if ($conn->connect_error){
    die("connection to the server and database changed");
}else{
   // echo "Connection succesfully established ";
}

//$conn->close()
?>


