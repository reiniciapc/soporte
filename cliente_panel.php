<?php
session_start();
if (!isset($_SESSION['cliente_id']) || $_SESSION['rol'] != 'Cliente') {
    header("Location: login.php");
    exit();
}
include 'conexion.php';

// Usar las configuraciones del archivo conexion.php
$correoAdministrador = $correoAdministrador;
$dominioInstalacion = $dominioInstalacion;

// Obtener información de bono del cliente
$cliente_id = $_SESSION['cliente_id'];
$sql_bono = "SELECT IFNULL(bonos.horas_asignadas, 0) AS bono, IFNULL(bonos.horas_consumidas, 0) AS uso
             FROM clientes
             LEFT JOIN bonos ON clientes.id = bonos.cliente_id
             WHERE clientes.id = ?";
$stmt_bono = $conn->prepare($sql_bono);
$stmt_bono->bind_param("i", $cliente_id);
$stmt_bono->execute();
$bono_result = $stmt_bono->get_result()->fetch_assoc();
$bono_asignado = $bono_result['bono'];
$minutos_usados = $bono_result['uso'];
$minutos_restantes = ($bono_asignado * 60) - $minutos_usados;

$bono_existe = $bono_asignado > 0;

// Variables de filtro
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_prioridad = isset($_GET['prioridad']) ? $_GET['prioridad'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Construir consulta SQL dinámica para los tickets
$sql_tickets_cliente = "SELECT * FROM tickets WHERE cliente_id = ?";

if ($filtro_estado) {
    $sql_tickets_cliente .= " AND estado = '$filtro_estado'";
}

if ($filtro_prioridad) {
    $sql_tickets_cliente .= " AND prioridad = '$filtro_prioridad'";
}

if ($busqueda) {
    $sql_tickets_cliente .= " AND titulo LIKE '%$busqueda%'";
}

$stmt_tickets_cliente = $conn->prepare($sql_tickets_cliente);
$stmt_tickets_cliente->bind_param("i", $cliente_id);
$stmt_tickets_cliente->execute();
$resultado_tickets = $stmt_tickets_cliente->get_result();

// Procesar formulario para registrar tickets
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_ticket'])) {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $prioridad = $_POST['prioridad'];

    $sql = "INSERT INTO tickets (cliente_id, titulo, descripcion, prioridad) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $cliente_id, $titulo, $descripcion, $prioridad);

    if ($stmt->execute()) {
        // Obtener el ID del ticket creado
        $id_ticket = $stmt->insert_id;

        // Preparar los datos del ticket para enviar el correo
        $datosTicket = [
            'id_ticket' => $id_ticket,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'nombre_cliente' => $_SESSION['cliente_nombre'],
            'correo_cliente' => $_SESSION['cliente_correo'],
            'fecha_creacion' => date("Y-m-d H:i:s")
        ];

        // Llamar a la función para enviar el correo
        enviarCorreoTicketNuevo($datosTicket);

        // Redirigir para evitar reenvío de formulario y actualizar la lista de tickets
        header("Location: cliente_panel.php");
        exit();
    } else {
        $error = "Error al crear el ticket: " . $conn->error;
    }
}

// Función para enviar correo electrónico al administrador cuando se crea un ticket nuevo
function enviarCorreoTicketNuevo($datosTicket) {
    global $correoAdministrador, $dominioInstalacion;
    
    // Asunto del correo
    $asunto = "[Nuevo Ticket] ID: " . $datosTicket['id_ticket'] . " - " . $datosTicket['titulo'];
    
    // Encabezados del correo
    $encabezados = "MIME-Version: 1.0" . "\r\n";
    $encabezados .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $encabezados .= "From: " . $correoAdministrador . "\r\n";

    // Contenido del mensaje en formato HTML
    $mensaje = "<html><body>";
    $mensaje .= "<h2>Nuevo Ticket Creado</h2>";
    $mensaje .= "<p><strong>ID del Ticket:</strong> " . $datosTicket['id_ticket'] . "</p>";
    $mensaje .= "<p><strong>Título:</strong> " . $datosTicket['titulo'] . "</p>";
    $mensaje .= "<p><strong>Descripción:</strong> " . $datosTicket['descripcion'] . "</p>";
    $mensaje .= "<p><strong>Cliente:</strong> " . $datosTicket['nombre_cliente'] . " (" . $datosTicket['correo_cliente'] . ")</p>";
    $mensaje .= "<p><strong>Fecha de Creación:</strong> " . $datosTicket['fecha_creacion'] . "</p>";
    $mensaje .= "<p>Puedes gestionar este ticket ingresando en el <a href='https://" . $dominioInstalacion . "/admin_ticket_detalle.php?id=" . $datosTicket['id_ticket'] . "'>panel de administración de tickets</a>.</p>";
    $mensaje .= "</body></html>";

    // Enviar el correo
    mail($correoAdministrador, $asunto, $mensaje, $encabezados);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Panel del Cliente</title>
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
        .btn-primary, .btn-danger, .btn-info {
            margin-top: 10px;
        }
        .table th {
            background-color: #343a40;
            color: #ffffff;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f2f2f2;
        }
        .badge {
            padding: 10px;
            border-radius: 5px;
            color: #fff;
        }
        .badge-abierto {
            background-color: red;
        }
        .badge-en-proceso {
            background-color: orange;
        }
        .badge-cerrado {
            background-color: green;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2><i class="fas fa-user"></i> Bienvenido, <?php echo htmlspecialchars($_SESSION['cliente_nombre']); ?></h2>
    <p>Desde aquí puedes gestionar tus tickets<?php echo $bono_existe ? ', agregar comentarios y consultar tu saldo de horas.' : '.'; ?></p>

    <?php if ($bono_existe): ?>
    <!-- Información del Bono -->
    <div class="my-4">
        <h4>Información del Bono</h4>
        <p><strong>Bono Asignado:</strong> <?php echo $bono_asignado; ?> horas</p>
        <p><strong>Minutos Gastados:</strong> <?php echo $minutos_usados; ?> minutos</p>
        <p><strong>Minutos Restantes:</strong> <?php echo $minutos_restantes; ?> minutos</p>
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: <?php echo ($minutos_usados / ($bono_asignado * 60)) * 100; ?>%;" aria-valuenow="<?php echo $minutos_usados; ?>" aria-valuemin="0" aria-valuemax="<?php echo $bono_asignado * 60; ?>"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtrar Tickets -->
    <h3 class="mt-5"><i class="fas fa-filter"></i> Filtrar Tickets</h3>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos</option>
                <option value="Abierto" <?php if ($filtro_estado == 'Abierto') echo 'selected'; ?>>Abierto</option>
                <option value="En Proceso" <?php if ($filtro_estado == 'En Proceso') echo 'selected'; ?>>En Proceso</option>
                <option value="Cerrado" <?php if ($filtro_estado == 'Cerrado') echo 'selected'; ?>>Cerrado</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="prioridad" class="form-label">Prioridad</label>
            <select name="prioridad" id="prioridad" class="form-select">
                <option value="">Todas</option>
                <option value="Alta" <?php if ($filtro_prioridad == 'Alta') echo 'selected'; ?>>Alta</option>
                <option value="Media" <?php if ($filtro_prioridad == 'Media') echo 'selected'; ?>>Media</option>
                <option value="Baja" <?php if ($filtro_prioridad == 'Baja') echo 'selected'; ?>>Baja</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="busqueda" class="form-label">Búsqueda</label>
            <input type="text" name="busqueda" id="busqueda" class="form-control" placeholder="Título" value="<?php echo htmlspecialchars($busqueda); ?>">
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
        </div>
    </form>

    <!-- Listado de Tickets -->
    <h3 class="mt-5"><i class="fas fa-ticket-alt"></i> Tus Tickets</h3>
    <div class="table-responsive">
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Fecha de Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ticket = $resultado_tickets->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $ticket['id']; ?></td>
                        <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                        <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>"><?php echo $ticket['estado']; ?></span></td>
                        <td><?php echo $ticket['prioridad']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></td>
                        <td>
                            <a href="cliente_ticket_detalle.php?id=<?php echo $ticket['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Ver Detalle</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Crear Ticket -->
    <h3 class="mt-5"><i class="fas fa-plus-circle"></i> Crear un Nuevo Ticket</h3>
    <form method="POST" class="row g-3 mt-2">
        <div class="col-md-6">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" name="titulo" id="titulo" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label for="prioridad" class="form-label">Prioridad</label>
            <select name="prioridad" id="prioridad" class="form-select" required>
                <option value="Baja" selected>Baja</option>
                <option value="Media">Media</option>
                <option value="Alta">Alta</option>
            </select>
        </div>
        <div class="col-md-12">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required></textarea>
        </div>
        <div class="col-md-12">
            <button type="submit" name="crear_ticket" class="btn btn-primary"><i class="fas fa-save"></i> Crear Ticket</button>
        </div>
    </form>

    <!-- Cerrar Sesión -->
    <a href="logout.php" class="btn btn-danger mt-4"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
