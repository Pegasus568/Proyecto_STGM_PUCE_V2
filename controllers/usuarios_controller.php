<?php
// controllers/usuarios_controller.php
session_start();
require_once '../includes/db.php';

// Seguridad: Solo POST y Solo ADMIN
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['usuario_rol'] !== 'ADMIN') {
    header("Location: ../index.php"); 
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    // --- 1. CREAR USUARIO ---
    if ($accion === 'crear') {
        $nombre   = trim($_POST['nombre']);
        $correo   = trim($_POST['correo']);
        $rol      = $_POST['rol'];
        $cedula   = trim($_POST['cedula']);
        $password = $_POST['password'];
        
        $carrera  = null;
        $semestre = null;

        if ($rol === 'DOCENTE') {
            $carrera = !empty($_POST['carrera_id']) ? $_POST['carrera_id'] : null;
        } elseif ($rol === 'ESTUDIANTE') {
            $carrera = !empty($_POST['carrera_id']) ? $_POST['carrera_id'] : null;
            $semestre = !empty($_POST['semestre']) ? $_POST['semestre'] : null;
        }

        if (empty($nombre) || empty($correo) || empty($password)) {
            throw new Exception("Datos incompletos.");
        }

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) throw new Exception("El correo ya existe.");

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre, correo, rol, password_hash, cedula, carrera_id, semestre, estado, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVO', NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $correo, $rol, $hash, $cedula, $carrera, $semestre]);

        $_SESSION['flash_mensaje'] = "Usuario creado exitosamente.";
        $_SESSION['flash_tipo'] = "success";
    }

    // --- 2. EDITAR USUARIO ---
    elseif ($accion === 'editar') {
        $id = $_POST['usuario_id'];
        $nombre = trim($_POST['nombre']);
        $correo = trim($_POST['correo']);
        $rol = $_POST['rol'];
        $cedula = trim($_POST['cedula']);
        $password = $_POST['password'];

        $carrera = null; $semestre = null;
        if ($rol === 'DOCENTE') {
            $carrera = $_POST['carrera_id'] ?: null;
        } elseif ($rol === 'ESTUDIANTE') {
            $carrera = $_POST['carrera_id'] ?: null;
            $semestre = $_POST['semestre'] ?: null;
        }

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

    // --- 3. ELIMINAR USUARIO (NUEVO) ---
    elseif ($accion === 'eliminar') {
        $idEliminar = $_POST['usuario_id'];

        // Protección: No auto-eliminarse
        if ($idEliminar == $_SESSION['usuario_id']) {
            throw new Exception("No puedes eliminar tu propia cuenta de administrador.");
        }

        // Borrado Lógico (Soft Delete)
        $sql = "UPDATE usuarios SET estado = 'INACTIVO', deleted_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idEliminar]);

        $_SESSION['flash_mensaje'] = "Usuario eliminado correctamente.";
        $_SESSION['flash_tipo'] = "warning";
    }

} catch (Exception $e) {
    $_SESSION['flash_mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['flash_tipo'] = "danger";
}

header("Location: ../usuarios.php");
exit;
?>