<?php
// reset_admin.php
// 1. Mostrar errores para saber si falla la conexión
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Incluir la conexión a la base de datos
// Asegúrate de que el archivo 'includes/db.php' exista y tenga los datos correctos
require_once 'includes/db.php';

try {
    // Datos a restaurar
    $email = 'admin@pucesa.edu.ec';
    $password_plana = '12345';
    
    // Generar el hash compatible con TU versión actual de PHP
    $password_hash = password_hash($password_plana, PASSWORD_DEFAULT);
    
    echo "<h3>Iniciando reseteo para: $email</h3>";

    // 3. Verificar si el usuario existe
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $check->execute([$email]);
    $user = $check->fetch();

    if ($user) {
        // 4. Actualizar la contraseña
        $sql = "UPDATE usuarios SET password_hash = ?, estado = 'ACTIVO' WHERE correo = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$password_hash, $email])) {
            echo "<p style='color: green; font-weight: bold;'>✅ ¡ÉXITO! La contraseña se ha cambiado a: $password_plana</p>";
        } else {
            echo "<p style='color: red;'>❌ Error al ejecutar el UPDATE.</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ El usuario con correo '$email' NO EXISTE en la base de datos.</p>";
        echo "<p>Intenta crear el usuario admin manualmente en phpMyAdmin.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR FATAL: " . $e->getMessage() . "</p>";
}

echo "<br><a href='login.php'>--> Ir al Login</a>";
?>