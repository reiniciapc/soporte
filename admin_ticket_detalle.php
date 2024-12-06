<?php 
session_start();
if (!isset($_SESSION['cliente_id']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: login.php");
    exit();
}
include 'conexion.php';

$ticket_id = $_GET['id'];

// Obtener detalles del ticket
$sql_ticket = "SELECT t.*, c.nombre AS cliente_nombre, c.id AS cliente_id FROM tickets t 
               JOIN clientes c ON t.cliente_id = c.id 
               WHERE t.id = ?";
$stmt_ticket = $conn->prepare($sql_ticket);
$stmt_ticket->bind_param("i", $ticket_id);
$stmt_ticket->execute();
$ticket = $stmt_ticket->get_result()->fetch_assoc();

if (!$ticket) {
    die("Ticket no encontrado.");
}

// Procesar Guardar Registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['guardar_estado'])) {
        $nuevo_estado = $_POST['estado'];
        // Actualizar estado del ticket
        $sql_cambiar_estado = "UPDATE tickets SET estado = ? WHERE id = ?";
        $stmt_cambiar_estado = $conn->prepare($sql_cambiar_estado);
        $stmt_cambiar_estado->bind_param("si", $nuevo_estado, $ticket_id);
        $stmt_cambiar_estado->execute();
        $mensaje = "Estado del ticket actualizado con éxito.";
    } elseif (isset($_POST['guardar_comentario'])) {
        $comentario = $_POST['comentario'] ?? null;
        $minutos_descontados = $_POST['minutos_descontados'] ?? null;
        $cliente_id = $ticket['cliente_id'];

        // Obtener bono global del cliente
        $sql_bono = "SELECT * FROM bonos WHERE cliente_id = ? ORDER BY fecha_asignacion DESC LIMIT 1";
        $stmt_bono = $conn->prepare($sql_bono);
        $stmt_bono->bind_param("i", $cliente_id);
        $stmt_bono->execute();
        $bono = $stmt_bono->get_result()->fetch_assoc();

        if ($bono) {
            $minutos_totales_disponibles = ($bono['horas_asignadas'] * 60) - $bono['horas_consumidas'];

            // Verificar si hay suficientes minutos disponibles en el bono global o si no se aplica descuento
            if (is_null($minutos_descontados) || $minutos_descontados <= $minutos_totales_disponibles) {
                // Registrar comentario con o sin minutos utilizados
                $sql_comentario = "INSERT INTO comentarios (ticket_id, comentario, creado_por, minutos_usados) VALUES (?, ?, ?, ?)";
                $stmt_comentario = $conn->prepare($sql_comentario);
                $stmt_comentario->bind_param("issi", $ticket_id, $comentario, $_SESSION['cliente_nombre'], $minutos_descontados);
                $stmt_comentario->execute();

                // Actualizar el bono global del cliente si hay minutos descontados
                if (!is_null($minutos_descontados) && $minutos_descontados > 0) {
                    $sql_actualizar_bono = "UPDATE bonos SET horas_consumidas = horas_consumidas + ? WHERE id = ?";
                    $stmt_actualizar_bono = $conn->prepare($sql_actualizar_bono);
                    $stmt_actualizar_bono->bind_param("ii", $minutos_descontados, $bono['id']);
                    $stmt_actualizar_bono->execute();
                }

                $mensaje = "Comentario guardado con éxito.";
            } else {
                $error = "El cliente no tiene suficientes minutos en su bono.";
            }
        } else {
            $error = "No se encontró un bono activo para este cliente.";
        }
    }
}

// Recalcular minutos usados y restantes dinámicamente a nivel de cliente
$sql_total_minutos_cliente = "SELECT SUM(minutos_usados) AS total_usados FROM comentarios WHERE ticket_id IN (SELECT id FROM tickets WHERE cliente_id = ?)";
$stmt_total_minutos_cliente = $conn->prepare($sql_total_minutos_cliente);
$stmt_total_minutos_cliente->bind_param("i", $ticket['cliente_id']);
$stmt_total_minutos_cliente->execute();
$result_total_minutos_cliente = $stmt_total_minutos_cliente->get_result()->fetch_assoc();

$total_minutos_usados_cliente = $result_total_minutos_cliente['total_usados'] ?? 0;

// Obtener bono activo del cliente
$sql_bono = "SELECT * FROM bonos WHERE cliente_id = ? ORDER BY fecha_asignacion DESC LIMIT 1";
$stmt_bono = $conn->prepare($sql_bono);
$stmt_bono->bind_param("i", $ticket['cliente_id']);
$stmt_bono->execute();
$bono = $stmt_bono->get_result()->fetch_assoc();

$minutos_restantes = $bono ? ($bono['horas_asignadas'] * 60 - $total_minutos_usados_cliente) : "N/A";

// Obtener comentarios
$sql_comentarios = "SELECT * FROM comentarios WHERE ticket_id = ? ORDER BY fecha_creacion ASC";
$stmt_comentarios = $conn->prepare($sql_comentarios);
$stmt_comentarios->bind_param("i", $ticket_id);
$stmt_comentarios->execute();
$comentarios = $stmt_comentarios->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Detalle del Ticket - Administrador</title>
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
        h2 {
            color: #343a40;
        }
        .table th {
            background-color: #343a40;
            color: #ffffff;
        }
        .table-bordered th, .table-bordered td {
            vertical-align: middle;
        }
        .btn-secondary {
            background-color: #343a40;
            border-color: #343a40;
        }
        .btn-secondary:hover {
            background-color: #23272b;
        }
        .badge-abierto {
            background-color: #dc3545;
        }
        .badge-en-proceso {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-cerrado {
            background-color: #28a745;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-ticket-alt"></i> Detalle del Ticket</h2>

    <!-- Mensajes -->
    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $mensaje; ?></div>
    <?php elseif (isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Detalle del Ticket -->
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Fecha de Creación</th>
                    <th>Minutos Usados (Total Cliente)</th>
                    <th>Minutos Restantes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                    <td><?php echo htmlspecialchars($ticket['cliente_nombre']); ?></td>
                    <td>
                        <span class="badge <?php 
                            echo $ticket['estado'] == 'Cerrado' ? 'badge-cerrado' : 
                                ($ticket['estado'] == 'En Proceso' ? 'badge-en-proceso' : 'badge-abierto'); ?>">
                            <?php echo htmlspecialchars($ticket['estado']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($ticket['prioridad']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></td>
                    <td><?php echo htmlspecialchars($total_minutos_usados_cliente); ?> minutos</td>
                    <td><?php echo htmlspecialchars($minutos_restantes); ?> minutos</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Descripción del Ticket -->
    <div class="mt-4">
        <h4>Descripción del Ticket</h4>
        <p><?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?></p>
    </div>

    <!-- Comentarios -->
    <h3 class="mt-5"><i class="fas fa-comments"></i> Comentarios</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Autor</th>
                    <th>Comentario</th>
                    <th>Minutos Usados</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($comentario = $comentarios->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comentario['creado_por']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></td>
                        <td>
                            <?php 
                            if (isset($comentario['minutos_usados']) && $comentario['minutos_usados'] > 0) {
                                echo htmlspecialchars($comentario['minutos_usados']) . ' minutos';
                            } 
                            ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])); ?></td>
                        <td>
                            <a href="admin_comentario_detalle.php?id=<?php echo $comentario['id']; ?>&ticket_id=<?php echo $ticket_id; ?>&action=edit" 
                               class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a>
                            <a href="admin_comentario_detalle.php?id=<?php echo $comentario['id']; ?>&ticket_id=<?php echo $ticket_id; ?>&action=delete" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Está seguro de que desea eliminar este comentario?');">
                               <i class="fas fa-trash-alt"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Formulario para Guardar Estado -->
    <h3 class="mt-5"><i class="fas fa-save"></i> Guardar Estado del Ticket</h3>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $ticket_id; ?>" class="row g-3">
        <div class="col-md-6">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select" required>
                <option value="Abierto" <?php if ($ticket['estado'] == 'Abierto') echo 'selected'; ?>>Abierto</option>
                <option value="En Proceso" <?php if ($ticket['estado'] == 'En Proceso') echo 'selected'; ?>>En Proceso</option>
                <option value="Cerrado" <?php if ($ticket['estado'] == 'Cerrado') echo 'selected'; ?>>Cerrado</option>
            </select>
        </div>
        <div class="col-md-12">
            <button type="submit" name="guardar_estado" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Estado</button>
        </div>
    </form>

    <!-- Formulario para Guardar Comentario -->
    <h3 class="mt-5"><i class="fas fa-comment-dots"></i> Agregar Comentario</h3>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $ticket_id; ?>" class="row g-3">
        <div class="col-md-6">
            <label for="minutos_descontados" class="form-label">Minutos Usados (opcional)</label>
            <input type="number" name="minutos_descontados" id="minutos_descontados" class="form-control">
        </div>
        <div class="col-md-12">
            <label for="comentario" class="form-label">Comentario</label>
            <textarea name="comentario" id="comentario" class="form-control" rows="3" required></textarea>
        </div>
        <div class="col-md-12">
            <button type="submit" name="guardar_comentario" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Comentario</button>
        </div>
    </form>

    <!-- Regresar -->
    <a href="admin_panel.php" class="btn btn-secondary mt-4"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
