<?php
// controllers/get_usuarios_carrera.php
header('Content-Type: application/json');
require_once '../includes/db.php';

$carreraId = $_GET['carrera_id'] ?? '';
$rolBuscado = $_GET['rol'] ?? ''; 
$semestre = $_GET['semestre'] ?? ''; 

if (empty($rolBuscado)) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT id, nombre, semestre FROM usuarios 
            WHERE rol = ? AND estado = 'ACTIVO' AND deleted_at IS NULL";
    $params = [$rolBuscado];

    if (!empty($carreraId)) {
        $sql .= " AND carrera_id = ?";
        $params[] = $carreraId;
    }

    if (!empty($semestre)) {
        $sql .= " AND semestre = ?";
        $params[] = $semestre;
    }

    $sql .= " ORDER BY nombre ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($usuarios);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>