<?php
// config.php
error_reporting(0);
ini_set('display_errors', 0);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "12345";
$DB_NAME = "sgtm_puce";
$DB_PORT = 3307; // muy importante: igual que en tu phpMyAdmin

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

if ($mysqli->connect_errno) {
    die("Error de conexión a la base de datos: " . $mysqli->connect_error);
}
?>
