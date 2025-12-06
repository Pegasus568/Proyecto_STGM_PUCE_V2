<?php
// controllers/reportes_controller.php
session_start();
require_once '../includes/db.php';

// Seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

// Solo Admin y Docentes
if ($_SESSION['usuario_rol'] === 'ESTUDIANTE') {
    header("Location: ../index.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];

try {
    $titulo = trim($_POST['titulo']);
    $tipo = $_POST['tipo'];
    $contenido = trim($_POST['contenido']);
    $privado = isset($_POST['privado']) ? 1 : 0;
    
    $estudiante_id = null;
    $tutoria_id = null;

    // Lógica de asignación de estudiante
    if (!empty($_POST['tutoria_id'])) {
        $tutoria_id = $_POST['tutoria_id'];
        $stmt = $pdo->prepare("SELECT estudiante_id FROM tutorias WHERE id = ?");
        $stmt->execute([$tutoria_id]);
        $estudiante_id = $stmt->fetchColumn();
    } elseif (!empty($_POST['estudiante_manual_id'])) {
        $estudiante_id = $_POST['estudiante_manual_id'];
    }

    if (empty($titulo) || empty($contenido) || empty($estudiante_id)) {
        throw new Exception("Faltan datos obligatorios (Título, Contenido o Estudiante).");
    }

    $sql = "INSERT INTO reportes (tutoria_id, tipo, titulo, contenido, creado_por, estudiante_id, privado, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tutoria_id, $tipo, $titulo, $contenido, $usuarioId, $estudiante_id, $privado]);

    $_SESSION['flash_mensaje'] = "Reporte guardado exitosamente.";
    $_SESSION['flash_tipo'] = "success";

} catch (Exception $e) {
    $_SESSION['flash_mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['flash_tipo'] = "danger";
}

header("Location: ../reportes.php");
exit;
?>