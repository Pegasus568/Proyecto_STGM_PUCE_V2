<?php
// controllers/tutorias_controller.php
session_start();
date_default_timezone_set('America/Guayaquil'); 
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php"); exit;
}

$usuarioId = $_SESSION['usuario_id'];
$rolUsuario = $_SESSION['usuario_rol'];
$accion = $_POST['accion'] ?? '';

// Función auxiliar para regla 48h
function validar48Horas($fecha, $hora) {
    $cita = new DateTime($fecha . ' ' . $hora);
    $ahora = new DateTime();
    $diff = ($cita->getTimestamp() - $ahora->getTimestamp()) / 3600;
    return $diff >= 48;
}

try {
    // ---------------------------------------------
    // 1. SOLICITAR (Crear)
    // ---------------------------------------------
    if ($accion === 'solicitar') {
        $titulo = trim($_POST['titulo']);
        $rawTipo = $_POST['tipo'];
        $tipoDb = str_replace('_GRUPAL', '', $rawTipo);
        
        $fecha = $_POST['fecha'];
        $inicio = $_POST['hora_inicio'];
        $fin = $_POST['hora_fin'];
        $modalidad = $_POST['modalidad'];
        $lugar = trim($_POST['lugar']);
        
        $contrapartes = $_POST['id_contraparte']; 
        $lista = is_array($contrapartes) ? $contrapartes : [$contrapartes];

        // Validaciones
        if(empty($titulo) || empty($fecha) || empty($inicio) || empty($fin) || empty($lista)) 
            throw new Exception("Datos incompletos.");
        
        if ($rolUsuario === 'ESTUDIANTE' && count($lista) > 1) 
             throw new Exception("Solo docentes pueden crear sesiones grupales.");

        // >>> CORRECCIÓN DEL ERROR DE SINTAXIS AQUÍ <<<
        // Separamos la creación de la fecha del cálculo
        $objFechaCita = new DateTime("$fecha $inicio");
        $timestampCita = $objFechaCita->getTimestamp();
        $diffCita = ($timestampCita - time()) / 3600;
        
        if ($diffCita < 24) {
            throw new Exception("Debe agendar con 24h de anticipación.");
        }
        // >>> FIN CORRECCIÓN <<<

        if ($inicio >= $fin) throw new Exception("Error en horas (Inicio >= Fin).");

        $insertados = 0;
        $errores = [];

        foreach ($lista as $idDestino) {
            $idTutor = ($rolUsuario === 'DOCENTE') ? $usuarioId : $idDestino;
            $idEstudiante = ($rolUsuario === 'DOCENTE') ? $idDestino : $usuarioId;

            // Regla: Anti-Solapamiento
            $sqlConf = "SELECT id FROM tutorias 
                        WHERE fecha=? 
                        AND estado NOT IN ('RECHAZADA','CANCELADA','NO_ASISTIO') 
                        AND deleted_at IS NULL 
                        AND (estudiante_id=?) 
                        AND (? < hora_fin AND ? > hora_inicio)";
            $stmtC = $pdo->prepare($sqlConf);
            $stmtC->execute([$fecha, $idEstudiante, $inicio, $fin]);
            
            if ($stmtC->fetch()) {
                $errores[] = "Cruce de horario para el usuario $idDestino";
                continue;
            }

            // Insertar
            $sql = "INSERT INTO tutorias (solicitado_por, tipo, tutor_id, estudiante_id, titulo, fecha, hora_inicio, hora_fin, modalidad, lugar, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE', NOW())";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$usuarioId, $tipoDb, $idTutor, $idEstudiante, $titulo, $fecha, $inicio, $fin, $modalidad, $lugar])) {
                $insertados++;
            }
        }

        $_SESSION['flash_mensaje'] = "Solicitudes creadas: $insertados. " . implode(" ", $errores);
        $_SESSION['flash_tipo'] = ($insertados > 0) ? "success" : "warning";
    }

    // ---------------------------------------------
    // 2. EDITAR (Con Regla 48h)
    // ---------------------------------------------
    elseif ($accion === 'editar') {
        $id = $_POST['tutoria_id'];
        $t = $pdo->query("SELECT * FROM tutorias WHERE id=$id")->fetch();

        if (!validar48Horas($t['fecha'], $t['hora_inicio'])) 
            throw new Exception("No se puede editar: Faltan menos de 48h.");

        $titulo = trim($_POST['titulo']); $fecha = $_POST['fecha']; $inicio = $_POST['hora_inicio']; $fin = $_POST['hora_fin'];
        
        $pdo->prepare("UPDATE tutorias SET titulo=?, fecha=?, hora_inicio=?, hora_fin=?, lugar=?, modalidad=?, updated_at=NOW() WHERE id=?")->execute([$titulo, $fecha, $inicio, $fin, $_POST['lugar'], $_POST['modalidad'], $id]);
        $_SESSION['flash_mensaje'] = "Sesión reprogramada.";
        $_SESSION['flash_tipo'] = "info";
    }

    // ---------------------------------------------
    // 3. CANCELAR (Con Regla 48h)
    // ---------------------------------------------
    elseif ($accion === 'cancelar') {
        $id = $_POST['tutoria_id'];
        $motivo = $_POST['motivo_cancelacion'];
        
        $t = $pdo->query("SELECT * FROM tutorias WHERE id=$id")->fetch();
        if (!validar48Horas($t['fecha'], $t['hora_inicio'])) 
            throw new Exception("No se puede cancelar: Faltan menos de 48h.");

        $pdo->prepare("UPDATE tutorias SET estado='CANCELADA', motivo_rechazo=?, updated_at=NOW() WHERE id=?")->execute([$motivo, $id]);
        $_SESSION['flash_mensaje'] = "Cita cancelada.";
        $_SESSION['flash_tipo'] = "warning";
    }

    // ---------------------------------------------
    // 4. ASISTENCIA (Solo Docente)
    // ---------------------------------------------
    elseif ($accion === 'asistencia') {
        if ($rolUsuario !== 'DOCENTE') throw new Exception("Acceso denegado.");
        $id = $_POST['tutoria_id'];
        $asistio = $_POST['asistio'];
        $obs = $_POST['observaciones'];

        $estado = ($asistio == 1) ? 'REALIZADA' : 'NO_ASISTIO';
        $pdo->prepare("UPDATE tutorias SET estado=?, asistio=?, observaciones=? WHERE id=?")->execute([$estado, $asistio, $obs, $id]);
        
        $_SESSION['flash_mensaje'] = "Registro guardado.";
        $_SESSION['flash_tipo'] = "success";
    }

    // ---------------------------------------------
    // 5. RESPONDER
    // ---------------------------------------------
    elseif (in_array($accion, ['confirmar', 'rechazar'])) {
        $id = $_POST['tutoria_id'];
        $est = ($accion === 'confirmar') ? 'CONFIRMADA' : 'RECHAZADA';
        $mot = $_POST['motivo_rechazo'] ?? null;
        
        $pdo->prepare("UPDATE tutorias SET estado=?, motivo_rechazo=? WHERE id=?")->execute([$est, $mot, $id]);
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