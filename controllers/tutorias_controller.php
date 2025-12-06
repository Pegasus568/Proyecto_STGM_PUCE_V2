<?php
session_start();
date_default_timezone_set('America/Guayaquil'); 
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php"); exit;
}

$usuarioId = $_SESSION['usuario_id'];
$rolUsuario = $_SESSION['usuario_rol'];
$accion = $_POST['accion'] ?? '';

try {
    // --- SOLICITAR (Soporta Múltiples) ---
    if ($accion === 'solicitar') {
        $titulo = trim($_POST['titulo']);
        $tipo = $_POST['tipo'];
        $fecha = $_POST['fecha'];
        $inicio = $_POST['hora_inicio'];
        $fin = $_POST['hora_fin'];
        $modalidad = $_POST['modalidad'];
        $lugar = trim($_POST['lugar']);
        
        $contrapartes = $_POST['id_contraparte']; // Puede ser array
        $lista = is_array($contrapartes) ? $contrapartes : [$contrapartes];

        if(empty($titulo) || empty($fecha) || empty($inicio) || empty($fin) || empty($lista)) 
            throw new Exception("Datos incompletos.");
        
        // Validaciones de fecha y hora...
        if ($fecha < date('Y-m-d')) throw new Exception("Fecha inválida (pasado).");
        if ($inicio >= $fin) throw new Exception("Hora fin debe ser mayor a inicio.");

        $insertados = 0;
        $errores = [];

        foreach ($lista as $idDestino) {
            $idTutor = ($rolUsuario === 'DOCENTE') ? $usuarioId : $idDestino;
            $idEstudiante = ($rolUsuario === 'DOCENTE') ? $idDestino : $usuarioId;

            // Validar Cruce
            $sqlConf = "SELECT id FROM tutorias WHERE fecha=? AND estado NOT IN ('RECHAZADA','CANCELADA') AND deleted_at IS NULL AND ((tutor_id=? OR estudiante_id=?)) AND (? < hora_fin AND ? > hora_inicio)";
            $stmtC = $pdo->prepare($sqlConf);
            $stmtC->execute([$fecha, $idEstudiante, $idEstudiante, $inicio, $fin]);
            
            if ($stmtC->fetch()) {
                $errores[] = "Conflicto con usuario ID: $idDestino";
                continue;
            }

            $sql = "INSERT INTO tutorias (solicitado_por, tipo, tutor_id, estudiante_id, titulo, fecha, hora_inicio, hora_fin, modalidad, lugar, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE', NOW())";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$usuarioId, $tipo, $idTutor, $idEstudiante, $titulo, $fecha, $inicio, $fin, $modalidad, $lugar])) {
                $insertados++;
            }
        }

        $_SESSION['flash_mensaje'] = "Agendadas: $insertados. " . implode(" ", $errores);
        $_SESSION['flash_tipo'] = ($insertados > 0) ? "success" : "danger";
    }

    // --- EDITAR ---
    elseif ($accion === 'editar') {
        $id = $_POST['tutoria_id'];
        $titulo = trim($_POST['titulo']);
        $fecha = $_POST['fecha'];
        $inicio = $_POST['hora_inicio'];
        $fin = $_POST['hora_fin'];
        $lugar = trim($_POST['lugar']);
        $modalidad = $_POST['modalidad'];

        // Validar conflicto excluyendo la propia cita
        $sqlConf = "SELECT id FROM tutorias WHERE fecha=? AND id!=? AND estado NOT IN ('RECHAZADA','CANCELADA') AND deleted_at IS NULL AND (tutor_id=? OR estudiante_id=?) AND (? < hora_fin AND ? > hora_inicio)";
        
        // Sacar IDs de la tutoría
        $t = $pdo->query("SELECT tutor_id, estudiante_id FROM tutorias WHERE id=$id")->fetch();
        $stmtConf = $pdo->prepare($sqlConf);
        // Verificar para ambos participantes
        $stmtConf->execute([$fecha, $id, $t['tutor_id'], $t['estudiante_id'], $inicio, $fin]);
        
        if ($stmtConf->fetch()) throw new Exception("El cambio genera conflicto de horario.");

        $upd = $pdo->prepare("UPDATE tutorias SET titulo=?, fecha=?, hora_inicio=?, hora_fin=?, lugar=?, modalidad=?, updated_at=NOW() WHERE id=?");
        $upd->execute([$titulo, $fecha, $inicio, $fin, $lugar, $modalidad, $id]);

        $_SESSION['flash_mensaje'] = "Tutoría reprogramada.";
        $_SESSION['flash_tipo'] = "info";
    }

    // --- RESPONDER ---
    elseif (in_array($accion, ['confirmar', 'rechazar'])) {
        $id = $_POST['tutoria_id'];
        $estado = ($accion === 'confirmar') ? 'CONFIRMADA' : 'RECHAZADA';
        $motivo = $_POST['motivo_rechazo'] ?? null;

        $pdo->prepare("UPDATE tutorias SET estado=?, motivo_rechazo=? WHERE id=?")->execute([$estado, $motivo, $id]);
        $_SESSION['flash_mensaje'] = "Estado actualizado.";
        $_SESSION['flash_tipo'] = "success";
    }

} catch (Exception $e) {
    $_SESSION['flash_mensaje'] = $e->getMessage();
    $_SESSION['flash_tipo'] = "danger";
}

header("Location: ../tutorias.php");
exit;
?>