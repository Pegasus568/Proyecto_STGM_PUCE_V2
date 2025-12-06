<?php
// controllers/api_get_entity.php
header('Content-Type: application/json');
require_once '../includes/db.php';

$id = $_GET['id'] ?? null;
$entity = $_GET['entity'] ?? null; // 'usuario', 'tutoria', 'reporte'

if (!$id || !$entity) {
    echo json_encode(['error' => 'Faltan parámetros']);
    exit;
}

try {
    $data = [];
    
    if ($entity === 'usuario') {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        // Por seguridad, borramos el hash de la contraseña antes de enviar
        unset($data['password_hash']);
    } 
    elseif ($entity === 'tutoria') {
        $stmt = $pdo->prepare("SELECT * FROM tutorias WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($entity === 'reporte') {
        $stmt = $pdo->prepare("SELECT * FROM reportes WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>