<?php
session_start();
require_once 'includes/db.php'; // Usa la nueva conexión PDO

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = trim($_POST['password'] ?? '');

    if (empty($correo) || empty($clave)) {
        $error = "Ingrese sus credenciales.";
    } else {
        try {
            // Consulta segura con PDO
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? AND deleted_at IS NULL");
            $stmt->execute([$correo]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($clave, $usuario['password_hash'])) {
                if ($usuario['estado'] !== 'ACTIVO') {
                    $error = "Cuenta inactiva.";
                } else {
                    // Login Exitoso
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_rol'] = $usuario['rol'];
                    
                    // Actualizar ultimo login
                    $upd = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                    $upd->execute([$usuario['id']]);

                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = "Credenciales incorrectas.";
            }
        } catch (Exception $e) {
            $error = "Error del sistema.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso SGTM</title>
    <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">
    <style>
        body { background-color: #00296b; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { width: 400px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .btn-primary { background-color: #00296b; border-color: #00296b; }
        .btn-primary:hover { background-color: #001f4d; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="text-center mb-4">
            <h2 style="color: #00296b;"><b>SGTM</b> PUCE</h2>
            <p>Sistema de Gestión de Tutorías</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group mb-3">
                <input type="email" name="correo" class="form-control" placeholder="Correo Institucional" required>
            </div>
            <div class="form-group mb-3">
                <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>