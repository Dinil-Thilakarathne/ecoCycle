<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "eco_cycle";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully!";
}
?>
