<?php
// reportes_stats.php
// Devuelve JSON para las gráficas
header('Content-Type: application/json');
require_once 'includes/auth.php'; // Solo usuarios logueados
require_once 'includes/db.php';

$data = [
    'labels' => [],
    'counts' => []
];

try {
    // Contar reportes por tipo
    $sql = "SELECT tipo, COUNT(*) as total FROM reportes WHERE deleted_at IS NULL GROUP BY tipo";
    $stmt = $pdo->query($sql);
    
    while($row = $stmt->fetch()) {
        $data['labels'][] = $row['tipo'];
        $data['counts'][] = (int)$row['total'];
    }
} catch (Exception $e) {
    // En caso de error devolvemos arrays vacíos para no romper el JS
}

echo json_encode($data);
?>