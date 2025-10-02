<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "empleados";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

?>