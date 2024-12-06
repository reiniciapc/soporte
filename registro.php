<?php
include 'conexion.php';
session_start();

// Verificar si existe al menos un administrador en la base de datos
$sql_check_admin = "SELECT COUNT(*) AS admin_count FROM clientes WHERE rol = 'Administrador'";
$result = $conn->query($sql_check_admin);
$row = $result->fetch_assoc();
$admin_count = $row['admin_count'];

$mostrar_alerta_admin = false;

// Proteger la página para que solo los administradores puedan acceder si ya hay al menos uno creado
if ($admin_count == 0) {
    // No hay administradores, se muestra una alerta y se permite registrar un administrador
    $mostrar_alerta_admin = true;
} elseif (!isset($_SESSION['cliente_id']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: login.php");
    exit();
}

// Procesar el registro de usuarios
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_usuario'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
    $rol = $_POST['rol'];

    // Insertar usuario en la base de datos
    $sql = "INSERT INTO clientes (nombre, email, telefono, contrasena, rol) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nombre, $email, $telefono, $contrasena, $rol);

    if ($stmt->execute()) {
        if ($rol == 'Cliente') {
            $cliente_id = $stmt->insert_id;
            $horas = isset($_POST['horas']) && $_POST['horas'] !== "" ? $_POST['horas'] : 0; // Asignar 0 si no se proporciona una cantidad
            $sql_bono = "INSERT INTO bonos (cliente_id, horas_asignadas) VALUES (?, ?)";
            $stmt_bono = $conn->prepare($sql_bono);
            $stmt_bono->bind_param("ii", $cliente_id, $horas);
            $stmt_bono->execute();
        }
        $mensaje = "Usuario registrado con éxito.";
    } else {
        $error = "Error al registrar el usuario: " . $conn->error;
    }
}

// Procesar la eliminación de usuarios
if (isset($_POST['eliminar_usuario'])) {
    $usuario_id = $_POST['usuario_id'];

    // Verificar si el usuario está protegido
    $sql = "SELECT protegido FROM clientes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error = "Error en la preparación de la consulta: " . $conn->error;
    } else {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();

        if ($usuario && $usuario['protegido']) {
            $error = "No se puede eliminar un usuario protegido.";
        } else {
            // Eliminar registros relacionados (ej. bonos) antes de eliminar el usuario
            $sql_bonos = "DELETE FROM bonos WHERE cliente_id = ?";
            $stmt_bonos = $conn->prepare($sql_bonos);
            if ($stmt_bonos) {
                $stmt_bonos->bind_param("i", $usuario_id);
                $stmt_bonos->execute();
            }

            // Eliminar el usuario
            $sql = "DELETE FROM clientes WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $usuario_id);
                if ($stmt->execute()) {
                    $mensaje = "Usuario eliminado con éxito.";
                } else {
                    $error = "Error al eliminar el usuario: " . $stmt->error;
                }
            } else {
                $error = "Error en la preparación de la consulta de eliminación: " . $conn->error;
            }
        }
    }
}

// Procesar la edición de usuarios
if (isset($_POST['editar_usuario'])) {
    $usuario_id = $_POST['usuario_id'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $rol = $_POST['rol'];
    $contrasena = $_POST['contrasena'];

    $sql = "UPDATE clientes SET nombre = ?, email = ?, telefono = ?, rol = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $email, $telefono, $rol, $usuario_id);

    if ($stmt->execute()) {
        // Actualizar la contraseña si se proporciona una nueva
        if (!empty($contrasena)) {
            $contrasena_hashed = password_hash($contrasena, PASSWORD_BCRYPT);
            $sql_contrasena = "UPDATE clientes SET contrasena = ? WHERE id = ?";
            $stmt_contrasena = $conn->prepare($sql_contrasena);
            $stmt_contrasena->bind_param("si", $contrasena_hashed, $usuario_id);
            $stmt_contrasena->execute();
        }

        if ($rol == 'Cliente' && isset($_POST['horas'])) {
            $horas = $_POST['horas'];
            $sql_bono = "UPDATE bonos SET horas_asignadas = ? WHERE cliente_id = ?";
            $stmt_bono = $conn->prepare($sql_bono);
            $stmt_bono->bind_param("ii", $horas, $usuario_id);
            $stmt_bono->execute();
        }
        $mensaje = "Usuario actualizado con éxito.";
    } else {
        $error = "Error al actualizar el usuario: " . $conn->error;
    }
}

// Obtener lista de usuarios
$sql = "SELECT clientes.id, clientes.nombre, clientes.email, clientes.telefono, clientes.rol, clientes.protegido, 
               IFNULL(bonos.horas_asignadas, 0) AS bono, IFNULL(bonos.horas_consumidas, 0) AS uso
        FROM clientes
        LEFT JOIN bonos ON clientes.id = bonos.cliente_id";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Registro y Gestión de Usuarios</title>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h2, h3 {
            color: #343a40;
        }
        .btn-primary, .btn-danger, .btn-secondary, .btn-warning {
            margin-top: 10px;
        }
        .table th {
            background-color: #343a40;
            color: #ffffff;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f2f2f2;
        }
        .alert-warning {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
    <script>
        function toggleHoras() {
            const rolSelect = document.getElementById("rol");
            const horasField = document.getElementById("horasField");
            if (rolSelect.value === "Cliente") {
                horasField.style.display = "block";
            } else {
                horasField.style.display = "none";
            }
        }
    </script>
</head>
<body>
<div class="container mt-5">
    <?php if ($mostrar_alerta_admin): ?>
        <div class="alert alert-warning text-center"><strong>No existe ningún administrador en el sistema.</strong> Esta es la primera vez que se accede al programa o se han eliminado todos los administradores. Por favor, registre un administrador.</div>
    <?php endif; ?>

    <h2><i class="fas fa-user-plus"></i> Registro de Usuarios</h2>

    <!-- Mensajes -->
    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $mensaje; ?></div>
    <?php elseif (isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Formulario para registrar usuarios -->
    <form method="POST" class="mt-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="col-md-6">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="telefono" required>
            </div>
            <div class="col-md-6">
                <label for="contrasena" class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="contrasena" required>
            </div>
            <div class="col-md-4">
                <label for="rol" class="form-label">Rol</label>
                <select class="form-select" name="rol" id="rol" required onchange="toggleHoras()">
                    <option value="Cliente">Cliente</option>
                    <option value="Administrador">Administrador</option>
                </select>
            </div>
            <div class="col-md-4" id="horasField" style="display: block;">
                <label for="horas" class="form-label">Horas de Bono (Opcional)</label>
                <input type="number" name="horas" id="horas" class="form-control">
            </div>
        </div>
        <button type="submit" name="registrar_usuario" class="btn btn-primary mt-3"><i class="fas fa-save"></i> Registrar</button>
    </form>

    <!-- Listado de usuarios -->
    <h3 class="mt-5"><i class="fas fa-users"></i> Usuarios Registrados</h3>
    <div class="table-responsive">
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Bono (Horas)</th>
                    <th>Uso (Minutos)</th>
                    <th>Protegido</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($usuario = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['telefono']); ?></td>
                        <td><?php echo $usuario['rol']; ?></td>
                        <td><?php echo $usuario['bono']; ?></td>
                        <td><?php echo $usuario['uso']; ?></td>
                        <td><?php echo $usuario['protegido'] ? 'Sí' : 'No'; ?></td>
                        <td>
                            <?php if (!$usuario['protegido']): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Está seguro de que desea eliminar este usuario? Se borrarán todos los datos relacionados, incluyendo tickets y bonos.')">
                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" name="eliminar_usuario" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                </form>
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editarUsuarioModal<?php echo $usuario['id']; ?>">
                                    <i class="fas fa-edit"></i> Editar
                                </button>

                                <!-- Modal para editar usuario -->
                                <div class="modal fade" id="editarUsuarioModal<?php echo $usuario['id']; ?>" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Usuario</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="nombre" class="form-label">Nombre</label>
                                                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Correo Electrónico</label>
                                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="telefono" class="form-label">Teléfono</label>
                                                        <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="rol" class="form-label">Rol</label>
                                                        <select class="form-select" name="rol" required onchange="toggleHoras()">
                                                            <option value="Cliente" <?php if ($usuario['rol'] == 'Cliente') echo 'selected'; ?>>Cliente</option>
                                                            <option value="Administrador" <?php if ($usuario['rol'] == 'Administrador') echo 'selected'; ?>>Administrador</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3" id="horasField">
                                                        <label for="horas" class="form-label">Horas de Bono (Opcional)</label>
                                                        <input type="number" class="form-control" name="horas" value="<?php echo $usuario['bono']; ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="contrasena" class="form-label">Contraseña Nueva (Opcional)</label>
                                                        <input type="password" class="form-control" name="contrasena" placeholder="Dejar en blanco para mantener la actual">
                                                    </div>
                                                    <button type="submit" name="editar_usuario" class="btn btn-primary">Actualizar Usuario</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>Protegido</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <a href="admin_panel.php" class="btn btn-secondary mt-3">Volver al Panel Administrativo</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        toggleHoras();
    });
</script>
</body>
</html>
