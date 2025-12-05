<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$res = $mysqli->query("SELECT tipo, COUNT(*) AS c FROM reportes GROUP BY tipo");
$labels = []; $counts = [];
while($row = $res->fetch_assoc()) {
    $labels[] = $row['tipo'];
    $counts[] = (int)$row['c'];
}
echo json_encode(['labels'=>$labels,'counts'=>$counts]);
