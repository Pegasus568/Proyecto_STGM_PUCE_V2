<?php
// controllers/get_horarios.php
header('Content-Type: application/json');
require_once '../includes/db.php';

$docenteId = $_GET['docente_id'] ?? 0;
$fecha = $_GET['fecha'] ?? '';

if (!$docenteId || !$fecha) {
    echo json_encode([]);
    exit;
}

// 1. Saber qué día de la semana es (1=Lunes... 7=Domingo)
$diaSemana = date('N', strtotime($fecha));

try {
    // A. Obtener bloques de disponibilidad del docente
    $sqlHorario = "SELECT hora_inicio, hora_fin FROM horarios_docentes 
                   WHERE docente_id = ? AND dia_semana = ? ORDER BY hora_inicio";
    $stmtH = $pdo->prepare($sqlHorario);
    $stmtH->execute([$docenteId, $diaSemana]);
    $bloques = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    if (empty($bloques)) {
        echo json_encode(['error' => 'El docente no tiene horario configurado para este día.']);
        exit;
    }

    // B. Obtener citas OCUPADAS de ese docente en esa fecha
    $sqlCitas = "SELECT hora_inicio, hora_fin FROM tutorias 
                 WHERE tutor_id = ? AND fecha = ? 
                 AND estado NOT IN ('RECHAZADA', 'CANCELADA', 'NO_ASISTIO')
                 AND deleted_at IS NULL";
    $stmtC = $pdo->prepare($sqlCitas);
    $stmtC->execute([$docenteId, $fecha]);
    $citasOcupadas = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    // C. Generar SLOTS de 30 minutos disponibles
    $slotsDisponibles = [];

    foreach ($bloques as $bloque) {
        $inicioBloque = strtotime($fecha . ' ' . $bloque['hora_inicio']);
        $finBloque = strtotime($fecha . ' ' . $bloque['hora_fin']);

        // Recorrer el bloque en intervalos de 30 min
        $actual = $inicioBloque;
        while ($actual < $finBloque) {
            $finSlot = $actual + (30 * 60); // Slot de 30 min
            
            // Si el slot termina después del fin del bloque, parar
            if ($finSlot > $finBloque) break;

            // Verificar colisión con citas ocupadas
            $choca = false;
            foreach ($citasOcupadas as $cita) {
                $iniCita = strtotime($fecha . ' ' . $cita['hora_inicio']);
                $finCita = strtotime($fecha . ' ' . $cita['hora_fin']);

                // Lógica de colisión: (StartA < EndB) && (EndA > StartB)
                if ($actual < $finCita && $finSlot > $iniCita) {
                    $choca = true;
                    break;
                }
            }

            if (!$choca) {
                $slotsDisponibles[] = date('H:i', $actual);
            }

            $actual += (30 * 60); // Avanzar 30 min
        }
    }

    echo json_encode(['slots' => $slotsDisponibles]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>