<?php
// php/generar_hash.php
$clave = "admin123"; // cámbiala por la clave que quieras
$hash = password_hash($clave, PASSWORD_DEFAULT);
echo "Contraseña: $clave<br>";
echo "Hash: $hash";
