<?php
// usuarios.php
require_once 'includes/auth.php'; // Control de sesión
require_once 'includes/db.php';   // Conexión PDO

// Verificación de Rol (Solo ADMIN accede aquí)
verificarRol(['ADMIN']);

$mensaje = "";
$error = "";

// --- LÓGICA: CREAR USUARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $rol      = $_POST['rol'] ?? 'ESTUDIANTE';
    $cedula   = trim($_POST['cedula'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validación básica
    if (empty($nombre) || empty($correo) || empty($password)) {
        $error = "Nombre, correo y contraseña son obligatorios.";
    } else {
        try {
            // Verificar si existe correo
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            
            if ($stmt->fetch()) {
                $error = "El correo $correo ya está registrado.";
            } else {
                // Encriptar contraseña
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar (Usando la nueva estructura de BD)
                $sql = "INSERT INTO usuarios (nombre, correo, rol, password_hash, cedula, estado, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'ACTIVO', NOW())";
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([$nombre, $correo, $rol, $hash, $cedula]);
                
                $mensaje = "Usuario registrado exitosamente.";
            }
        } catch (PDOException $e) {
            $error = "Error de base de datos: " . $e->getMessage();
        }
    }
}

// --- LÓGICA: LISTAR USUARIOS (Solo activos) ---
$stmtList = $pdo->query("SELECT * FROM usuarios WHERE deleted_at IS NULL ORDER BY id DESC");
$usuarios = $stmtList->fetchAll();

// Configuración de la vista
$tituloPagina = "Gestión de Usuarios";
require_once 'includes/header.php'; 
?>

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Nuevo Usuario</h3>
            </div>
            <form method="post" action="usuarios.php">
                <div class="card-body">
                    <?php if($mensaje): ?>
                        <div class="alert alert-success"><?php echo $mensaje; ?></div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Juan Perez" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Institucional</label>
                        <input type="email" name="correo" class="form-control" placeholder="@pucesa.edu.ec" required>
                    </div>
                    <div class="form-group">
                        <label>Cédula</label>
                        <input type="text" name="cedula" class="form-control" placeholder="10 dígitos">
                    </div>
                    <div class="form-group">
                        <label>Rol</label>
                        <select name="rol" class="form-control">
                            <option value="ESTUDIANTE">Estudiante</option>
                            <option value="DOCENTE">Docente (Tutor)</option>
                            <option value="ADMIN">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Usuarios Registrados</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-valign-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($usuarios as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($u['nombre']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($u['correo']); ?></small>
                        </td>
                        <td>
                            <span class="badge <?php echo ($u['rol']=='ADMIN')?'badge-danger':(($u['rol']=='DOCENTE')?'badge-info':'badge-secondary'); ?>">
                                <?php echo $u['rol']; ?>
                            </span>
                        </td>
                        <td><?php echo $u['estado']; ?></td>
                        <td>
                            <a href="#" class="text-muted">
                                <i class="fas fa-search"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>