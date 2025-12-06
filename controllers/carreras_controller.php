<?php
// controllers/carreras_controller.php
session_start();
require_once '../includes/db.php';

// Seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'ADMIN') {
    header("Location: ../login.php");
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    // --- 1. CREAR CARRERA ---
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre']);
        $codigo = trim($_POST['codigo']); // Ej: IS-2025

        if (empty($nombre)) {
            throw new Exception("El nombre de la carrera es obligatorio.");
        }

        $sql = "INSERT INTO carreras (nombre, codigo, estado) VALUES (?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $codigo]);

        $_SESSION['flash_mensaje'] = "Carrera '$nombre' creada exitosamente.";
        $_SESSION['flash_tipo'] = "success";
    }

    // --- 2. ELIMINAR (DESACTIVAR) CARRERA ---
    elseif ($accion === 'eliminar') {
        $id = $_POST['carrera_id'];
        // No borramos físicamente para mantener historial, solo desactivamos
        // O si prefieres borrar: DELETE FROM carreras...
        $sql = "UPDATE carreras SET estado = 0 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        $_SESSION['flash_mensaje'] = "Carrera eliminada/desactivada.";
        $_SESSION['flash_tipo'] = "warning";
    }

} catch (Exception $e) {
    $_SESSION['flash_mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['flash_tipo'] = "danger";
}

header("Location: ../carreras.php");
exit;
?>