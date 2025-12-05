<?php
session_start();
require_once 'config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = trim($_POST['password'] ?? '');

    if ($correo === "" || $clave === "") {
        $error = "Ingrese correo y contraseña.";
    } elseif (!preg_match('/^[A-Za-z0-9._%+-]+@pucesa\.edu\.ec$/', $correo)) {
        // Validación de dominio institucional
        $error = "El correo debe ser institucional (@pucesa.edu.ec).";
    } else {
        $stmt = $mysqli->prepare("SELECT id, nombre, rol, estado, password_hash FROM usuarios WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();

        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();

        if (!$usuario) {
            $error = "Usuario no encontrado.";
        } elseif ($usuario['estado'] !== 'ACTIVO') {
            $error = "Usuario inactivo. Contacte al administrador.";
        } elseif (!password_verify($clave, $usuario['password_hash'])) {
            $error = "Contraseña incorrecta.";
        } else {
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            $stmt = $mysqli->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $usuario['id']);
            $stmt->execute();
            $stmt->close();

            if ($usuario['rol'] === 'ADMIN') {
                $destino = 'index.php';
            } elseif ($usuario['rol'] === 'DOCENTE') {
                $destino = 'tutorias.php';
            } else {
                $destino = 'tutorias.php';
            }

            header("Location: " . $destino);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso SGTM - PUCE Ambato</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- AdminLTE / Bootstrap -->
    <link rel="stylesheet" href="adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="adminlte/dist/css/adminlte.min.css">

    <!-- CSS ESPECÍFICO SOLO PARA ESTE LOGIN -->
    <style>
        html, body {
            height: 100%;
        }

        body.login-body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #00296b; /* azul PUCE */
            background-image: none;
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Barra superior */
        .login-header {
            flex: 0 0 auto;
            background-color: #00296b;
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .login-header img {
            height: 70px;
            width: auto;
            display: block;
        }

        .login-header-title {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 0.08em;
        }

        /* Zona central */
        .login-area {
            flex: 1 1 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem 0.75rem;
        }

        .login-card {
            width: 100%;
            max-width: 960px;
            background-color: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
            display: flex;
            flex-wrap: wrap;
        }

        /* Columna izquierda: formulario */
        .login-left {
            flex: 1 1 380px;
            padding: 2.5rem 2.5rem 2rem 2.5rem;
        }

        .login-subtitle {
            color: #0082c6;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }

        .login-input-label {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .login-left small {
            color: #6c757d;
        }

        /* Columna derecha: imagen */
        .login-right {
            flex: 1 1 320px;
            background-image: url("assets/img/login_estudiantes.jpg");
            background-size: cover;
            background-position: center;
        }

        /* Footer inferior */
        .login-footer {
            flex: 0 0 auto;
            background-color: #001b4d;
            color: #ffffff;
            text-align: center;
            padding: 0.6rem;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-card {
                max-width: 480px;
            }
            .login-right {
                display: none;
            }
        }
    </style>

    <!-- Estilos generales del sistema (para el resto de páginas) -->
    <link rel="stylesheet" href="assets/css/sgtm_puce.css">
</head>
<body class="login-body">

    <!-- BARRA SUPERIOR -->
    <header class="login-header">
        <img src="assets/img/logo_puce_blanco.png" alt="PUCE Ambato">
        <span class="login-header-title">PUCE&nbsp;AMBATO</span>
    </header>

    <!-- CONTENIDO CENTRAL -->
    <div class="login-area">
        <div class="login-card">

            <!-- Columna izquierda: formulario -->
            <div class="login-left">
                <div class="mb-3">
                    <div class="login-subtitle">Bienvenido</div>
                    <div class="login-title">Sistema de Gestión de Tutorías y Mentorías</div>
                    <small>Acceso para <strong>Administradores</strong>, <strong>Docentes</strong> y <strong>Estudiantes</strong> de la PUCE Ambato.</small>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="form-group">
                        <label class="login-input-label">Correo institucional</label>
                        <input
                            type="email"
                            name="correo"
                            class="form-control"
                            placeholder="usuario@pucesa.edu.ec"
                            required
                            pattern="^[a-zA-Z0-9._%+-]+@pucesa\.edu\.ec$"
                            title="El correo debe ser institucional, por ejemplo: usuario@pucesa.edu.ec">

                    </div>

                    <div class="form-group mt-3">
                        <label class="login-input-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="********" required>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-block mt-4">
                        Acceder
                    </button>

                    <div class="mt-3 text-center">
                        <a href="#" class="small">¿Olvidó su contraseña?</a><br>
                        <span class="small text-muted">En caso de problemas, contacte a la coordinación de sistemas.</span>
                    </div>
                </form>
            </div>

            <!-- Columna derecha: imagen -->
            <div class="login-right d-none d-md-block"></div>

        </div>
    </div>

    <!-- BARRA INFERIOR -->
    <footer class="login-footer">
        &copy; <?php echo date('Y'); ?> PUCE Ambato · Sistema de Gestión de Tutorías y Mentorías (SGTM)
    </footer>

    <script src="adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>
