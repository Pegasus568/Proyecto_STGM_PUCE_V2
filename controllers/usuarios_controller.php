<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['usuario_rol'] !== 'ADMIN') {
    header("Location: ../index.php"); exit;
}

$accion = $_POST['accion'] ?? '';

try {
    // --- LÓGICA COMÚN DE VARIABLES ---
    $nombre   = trim($_POST['nombre']);
    $correo   = trim($_POST['correo']);
    $rol      = $_POST['rol'];
    $cedula   = trim($_POST['cedula']);
    $carrera  = null;
    $semestre = null;

    // Filtro de datos según rol
    if ($rol === 'DOCENTE') {
        $carrera = !empty($_POST['carrera_id']) ? $_POST['carrera_id'] : null;
    } elseif ($rol === 'ESTUDIANTE') {
        $carrera = !empty($_POST['carrera_id']) ? $_POST['carrera_id'] : null;
        $semestre = !empty($_POST['semestre']) ? $_POST['semestre'] : null;
    }
    // Admin se queda con nulls

    // --- CASO 1: CREAR ---
    if ($accion === 'crear') {
        $password = $_POST['password'];
        if (empty($nombre) || empty($correo) || empty($password)) throw new Exception("Datos incompletos.");

        // Verificar duplicado
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) throw new Exception("Correo ya registrado.");

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre, correo, rol, password_hash, cedula, carrera_id, semestre, estado, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVO', NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $correo, $rol, $hash, $cedula, $carrera, $semestre]);

        $_SESSION['flash_mensaje'] = "Usuario creado.";
        $_SESSION['flash_tipo'] = "success";
    }

    // --- CASO 2: EDITAR ---
    elseif ($accion === 'editar') {
        $id = $_POST['usuario_id'];
        $password = $_POST['password'];

        $sql = "UPDATE usuarios SET nombre=?, correo=?, rol=?, cedula=?, carrera_id=?, semestre=?, updated_at=NOW()";
        $params = [$nombre, $correo, $rol, $cedula, $carrera, $semestre];

        if (!empty($password)) {
            $sql .= ", password_hash=?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id=?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['flash_mensaje'] = "Usuario actualizado.";
        $_SESSION['flash_tipo'] = "info";
    }

} catch (Exception $e) {
    $_SESSION['flash_mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['flash_tipo'] = "danger";
}

header("Location: ../usuarios.php");
exit;
?>