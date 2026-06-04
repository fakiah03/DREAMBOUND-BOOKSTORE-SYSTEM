<?php
$host = "localhost";
$user = "root";       /* Default user XAMPP */
$pass = "";           /* Default password XAMPP (kosong) */
$db_name = "dreambound_db";

$conn = new mysqli($host, $user, $pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>