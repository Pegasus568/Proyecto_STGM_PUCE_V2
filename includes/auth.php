<?php
// includes/auth.php
session_start();

// Si no hay usuario en sesión, mandar al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Función helper para verificar roles (Ej: solo ADMIN puede ver esto)
function verificarRol($rolesPermitidos) {
    if (!in_array($_SESSION['usuario_rol'], $rolesPermitidos)) {
        // Si no tiene permiso, redirigir o mostrar error
        header("Location: index.php?error=acceso_denegado");
        exit;
    }
}
?>