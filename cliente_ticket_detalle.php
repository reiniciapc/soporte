<?php
session_start();
if (!isset($_SESSION['cliente_id']) || $_SESSION['rol'] != 'Cliente') {
    error_log("Sesión no válida: cliente_id o rol no establecido");
    header("Location: login.php");
    exit();
}
include 'conexion.php';

$ticket_id = $_GET['id'];

// Obtener detalles del ticket
error_log("Obteniendo detalles del ticket con ID: " . $ticket_id);
$sql_ticket = "SELECT t.*, c.nombre AS cliente_nombre, c.id AS cliente_id FROM tickets t 
               JOIN clientes c ON t.cliente_id = c.id 
               WHERE t.id = ?";
$stmt_ticket = $conn->prepare($sql_ticket);
if (!$stmt_ticket) {
    error_log("Error al preparar la consulta del ticket: " . $conn->error);
    die("Error al preparar la consulta del ticket");
}
$stmt_ticket->bind_param("i", $ticket_id);
$stmt_ticket->execute();
$ticket = $stmt_ticket->get_result()->fetch_assoc();

if (!$ticket) {
    error_log("Ticket no encontrado con ID: " . $ticket_id);
    die("Ticket no encontrado.");
}

// Obtener bono activo del cliente
error_log("Obteniendo bono activo para cliente ID: " . $ticket['cliente_id']);
$sql_bono = "SELECT * FROM bonos WHERE cliente_id = ? ORDER BY fecha_asignacion DESC LIMIT 1";
$stmt_bono = $conn->prepare($sql_bono);
if (!$stmt_bono) {
    error_log("Error al preparar la consulta del bono: " . $conn->error);
    die("Error al preparar la consulta del bono");
}
$stmt_bono->bind_param("i", $ticket['cliente_id']);
$stmt_bono->execute();
$bono = $stmt_bono->get_result()->fetch_assoc();

$bono_existe = $bono && $bono['horas_asignadas'] > 0;

if ($bono_existe) {
    // Recalcular minutos usados y restantes dinámicamente a nivel de cliente si hay bono
    error_log("Calculando minutos usados y restantes para cliente ID: " . $ticket['cliente_id']);
    $sql_total_minutos_cliente = "SELECT SUM(minutos_usados) AS total_usados FROM comentarios WHERE ticket_id IN (SELECT id FROM tickets WHERE cliente_id = ?)";
    $stmt_total_minutos_cliente = $conn->prepare($sql_total_minutos_cliente);
    if (!$stmt_total_minutos_cliente) {
        error_log("Error al preparar la consulta de minutos usados: " . $conn->error);
        die("Error al preparar la consulta de minutos usados");
    }
    $stmt_total_minutos_cliente->bind_param("i", $ticket['cliente_id']);
    $stmt_total_minutos_cliente->execute();
    $result_total_minutos_cliente = $stmt_total_minutos_cliente->get_result()->fetch_assoc();

    $total_minutos_usados_cliente = $result_total_minutos_cliente['total_usados'] ?? 0;
    $minutos_restantes = max(0, ($bono['horas_asignadas'] * 60) - $total_minutos_usados_cliente);
    error_log("Minutos restantes para cliente ID " . $ticket['cliente_id'] . ": " . $minutos_restantes);
}

// Obtener comentarios
error_log("Obteniendo comentarios para el ticket ID: " . $ticket_id);
$sql_comentarios = "SELECT * FROM comentarios WHERE ticket_id = ? ORDER BY fecha_creacion ASC";
$stmt_comentarios = $conn->prepare($sql_comentarios);
if (!$stmt_comentarios) {
    error_log("Error al preparar la consulta de comentarios: " . $conn->error);
    die("Error al preparar la consulta de comentarios");
}
$stmt_comentarios->bind_param("i", $ticket_id);
$stmt_comentarios->execute();
$comentarios = $stmt_comentarios->get_result();

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_comentario'])) {
    $nuevo_comentario = $_POST['comentario'];
    $autor = $_SESSION['cliente_nombre'];
    error_log("Procesando nuevo comentario para el ticket ID: " . $ticket_id . " por el autor: " . $autor);

    $sql_nuevo_comentario = "INSERT INTO comentarios (ticket_id, comentario, creado_por) VALUES (?, ?, ?)";
    $stmt_nuevo_comentario = $conn->prepare($sql_nuevo_comentario);
    if (!$stmt_nuevo_comentario) {
        error_log("Error al preparar la consulta de nuevo comentario: " . $conn->error);
        die("Error al preparar la consulta de nuevo comentario");
    }
    $stmt_nuevo_comentario->bind_param("iss", $ticket_id, $nuevo_comentario, $autor);
    if (!$stmt_nuevo_comentario->execute()) {
        error_log("Error al ejecutar la consulta de nuevo comentario: " . $stmt_nuevo_comentario->error);
        die("Error al ejecutar la consulta de nuevo comentario");
    }

    // Enviar correo al administrador con el nuevo comentario
    enviarCorreoNuevoComentario($ticket, $nuevo_comentario, $autor);

    error_log("Nuevo comentario añadido correctamente para el ticket ID: " . $ticket_id);
    header("Location: cliente_ticket_detalle.php?id=$ticket_id");
    exit();
}

// Función para enviar correo electrónico al administrador cuando se agrega un nuevo comentario
function enviarCorreoNuevoComentario($ticket, $comentario, $autor) {
    global $correoAdministrador, $dominioInstalacion;
    
    error_log("Enviando correo al administrador para el ticket ID: " . $ticket['id']);
    // Asunto del correo
    $asunto = "[Nuevo Comentario] Ticket ID: " . $ticket['id'] . " - " . $ticket['titulo'];
    
    // Encabezados del correo
    $encabezados = "MIME-Version: 1.0" . "\r\n";
    $encabezados .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $encabezados .= "From: " . $correoAdministrador . "\r\n";

    // Contenido del mensaje en formato HTML
    $mensaje = "<html><body>";
    $mensaje .= "<h2>Nuevo Comentario en el Ticket</h2>";
    $mensaje .= "<p><strong>ID del Ticket:</strong> " . $ticket['id'] . "</p>";
    $mensaje .= "<p><strong>Título del Ticket:</strong> " . $ticket['titulo'] . "</p>";
    $mensaje .= "<p><strong>Autor del Comentario:</strong> " . $autor . "</p>";
    $mensaje .= "<p><strong>Comentario:</strong> " . nl2br(htmlspecialchars($comentario)) . "</p>";
    $mensaje .= "<p>Puedes revisar el ticket ingresando en el <a href='https://" . $dominioInstalacion . "/admin_ticket_detalle.php?id=" . $ticket['id'] . "'>panel de administración de tickets</a>.</p>";
    $mensaje .= "</body></html>";

    // Enviar el correo
    if (!mail($correoAdministrador, $asunto, $mensaje, $encabezados)) {
        error_log("Error al enviar el correo al administrador para el ticket ID: " . $ticket['id']);
    } else {
        error_log("Correo enviado correctamente al administrador para el ticket ID: " . $ticket['id']);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Detalle del Ticket</title>
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
                    <?php if ($bono_existe): ?>
                        <th>Minutos Usados (Total Cliente)</th>
                        <th>Minutos Restantes</th>
                    <?php endif; ?>
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
                    <?php if ($bono_existe): ?>
                        <td><?php echo htmlspecialchars($total_minutos_usados_cliente); ?> minutos</td>
                        <td><?php echo htmlspecialchars($minutos_restantes); ?> minutos</td>
                    <?php endif; ?>
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
                    <?php if ($bono_existe): ?>
                        <th>Minutos Usados</th>
                    <?php endif; ?>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($comentario = $comentarios->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comentario['creado_por']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></td>
                        <?php if ($bono_existe): ?>
                            <td>
                                <?php 
                                if (isset($comentario['minutos_usados']) && $comentario['minutos_usados'] > 0) {
                                    echo htmlspecialchars($comentario['minutos_usados']) . ' minutos';
                                } 
                                ?>
                            </td>
                        <?php endif; ?>
                        <td><?php echo date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Formulario para Agregar Comentario -->
    <?php if ($ticket['estado'] != 'Cerrado'): ?>
        <h3 class="mt-5"><i class="fas fa-plus"></i> Agregar Comentario</h3>
        <form method="POST" class="row g-3">
            <div class="col-md-12">
                <label for="comentario" class="form-label">Comentario</label>
                <textarea name="comentario" id="comentario" class="form-control" rows="3" required></textarea>
            </div>
            <div class="col-md-12">
                <button type="submit" name="guardar_comentario" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Comentario</button>
            </div>
        </form>
    <?php endif; ?>

    <!-- Regresar -->
    <a href="cliente_panel.php" class="btn btn-secondary mt-4"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
