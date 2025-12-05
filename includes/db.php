<?php
// includes/db.php

// Configuración de credenciales (Ajusta el puerto si usas 3306 o 3307)
$host = 'localhost';
$db   = 'sgtm_puce';
$user = 'root';
$pass = '';        // En XAMPP suele ser vacío, pon tu clave si tienes una.
$port = '3306';    // Cambia a 3307 si tu MySQL usa ese puerto.
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza errores fatales si algo falla
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa sentencias preparadas reales
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En producción, nunca muestres el error real al usuario (loguealo en un archivo)
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>