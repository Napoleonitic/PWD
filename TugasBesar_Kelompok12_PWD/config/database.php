<?php
// config/database.php
$servername = "localhost";
$username   = "root"; 
$password   = "";     
$dbname     = "bioskop_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}


$conn->set_charset("utf8");
?>