<?php
session_start();
if (!isset($_SESSION['cliente_id']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: login.php");
    exit();
}
include 'conexion.php';

// Consultar estadísticas
$sql_estado = "SELECT estado, COUNT(*) AS cantidad FROM tickets GROUP BY estado";
$resultado_estado = $conn->query($sql_estado);

$sql_prioridad = "SELECT prioridad, COUNT(*) AS cantidad FROM tickets GROUP BY prioridad";
$resultado_prioridad = $conn->query($sql_prioridad);

// Variables de filtro
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_prioridad = isset($_GET['prioridad']) ? $_GET['prioridad'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Construir consulta SQL dinámica
$sql = "SELECT tickets.id, clientes.nombre AS cliente, tickets.titulo, tickets.estado, tickets.prioridad, tickets.fecha_creacion 
        FROM tickets 
        JOIN clientes ON tickets.cliente_id = clientes.id 
        WHERE 1=1";

if ($filtro_estado) {
    $sql .= " AND tickets.estado = '$filtro_estado'";
}

if ($filtro_prioridad) {
    $sql .= " AND tickets.prioridad = '$filtro_prioridad'";
}

if ($busqueda) {
    $sql .= " AND (tickets.titulo LIKE '%$busqueda%' OR clientes.nombre LIKE '%$busqueda%')";
}

$sql .= " ORDER BY tickets.fecha_creacion DESC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Panel Administrativo</title>
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
            position: relative;
        }
        h2, h3 {
            color: #343a40;
        }
        .btn-primary, .btn-danger, .btn-secondary, .btn-info {
            margin-top: 10px;
        }
        .table th {
            background-color: #343a40;
            color: #ffffff;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f2f2f2;
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
        .badge-alta {
            background-color: #000000;
            color: #ffffff;
        }
        .badge-media {
            background-color: #6c757d;
            color: #ffffff;
        }
        .badge-baja {
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #6c757d;
        }
        .help-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #007bff;
            font-size: 1.5em;
        }
        .help-icon:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <!-- Icono de Instrucciones -->
    <a href="instrucciones.php" class="help-icon" title="Ver Instrucciones">
        <i class="fas fa-question-circle"></i>
    </a>
    
    <h2><i class="fas fa-tools"></i> Panel Administrativo</h2>
    <p>Gestiona los tickets y administra los usuarios registrados.</p>

    <!-- Botón de logout -->
    <a href="logout.php" class="btn btn-danger mb-4"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    <!-- Botón de acceso a registro -->
    <a href="registro.php" class="btn btn-secondary mb-4"><i class="fas fa-user-plus"></i> Registrar Usuario</a>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h4><i class="fas fa-chart-bar"></i> Tickets por Estado</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($estado = $resultado_estado->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge <?php 
                                    echo $estado['estado'] == 'Cerrado' ? 'badge-cerrado' : 
                                         ($estado['estado'] == 'En Proceso' ? 'badge-en-proceso' : 'badge-abierto'); ?>">
                                    <?php echo htmlspecialchars($estado['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo $estado['cantidad']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <h4><i class="fas fa-exclamation-circle"></i> Tickets por Prioridad</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Prioridad</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($prioridad = $resultado_prioridad->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge <?php 
                                    echo $prioridad['prioridad'] == 'Alta' ? 'badge-alta' : 
                                         ($prioridad['prioridad'] == 'Media' ? 'badge-media' : 'badge-baja'); ?>">
                                    <?php echo htmlspecialchars($prioridad['prioridad']); ?>
                                </span>
                            </td>
                            <td><?php echo $prioridad['cantidad']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Listado de Tickets -->
    <h3 class="mt-5"><i class="fas fa-ticket-alt"></i> Listado de Tickets</h3>
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
            <input type="text" name="busqueda" id="busqueda" class="form-control" placeholder="Título o Cliente" value="<?php echo htmlspecialchars($busqueda); ?>">
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Fecha de Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ticket = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $ticket['id']; ?></td>
                        <td><?php echo htmlspecialchars($ticket['cliente']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                        <td>
                            <span class="badge <?php 
                                echo $ticket['estado'] == 'Cerrado' ? 'badge-cerrado' : 
                                     ($ticket['estado'] == 'En Proceso' ? 'badge-en-proceso' : 'badge-abierto'); ?>">
                                <?php echo htmlspecialchars($ticket['estado']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php 
                                echo $ticket['prioridad'] == 'Alta' ? 'badge-alta' : 
                                     ($ticket['prioridad'] == 'Media' ? 'badge-media' : 'badge-baja'); ?>">
                                <?php echo htmlspecialchars($ticket['prioridad']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></td>
                        <td>
                            <a href="admin_ticket_detalle.php?id=<?php echo $ticket['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Ver Detalle</a>
                            <a href="eliminar_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('¿Está seguro de que desea eliminar este ticket? Esto restaurará las horas utilizadas y borrará todos los registros asociados.');">
                               <i class="fas fa-trash-alt"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
