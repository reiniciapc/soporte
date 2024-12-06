<?php
session_start();
if (!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit();
}
include 'conexion.php';

if (!isset($_GET['id'])) {
    die("ID de ticket no proporcionado.");
}

$ticket_id = intval($_GET['id']);
$cliente_id = $_SESSION['cliente_id'];

// Obtener los detalles del ticket
$sql = "SELECT * FROM tickets WHERE id = ? AND cliente_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ticket_id, $cliente_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    die("No se encontró el ticket o no tienes permisos para verlo.");
}

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descripcion = $_POST['descripcion'];
    $tecnico = "Cliente"; // En este caso, quien añade el comentario es el cliente.

    $sql = "INSERT INTO seguimiento (ticket_id, descripcion, tecnico) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $ticket_id, $descripcion, $tecnico);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Detalle del Ticket</title>
</head>
<body>
<div class="container mt-5">
    <h2>Detalle del Ticket</h2>
    <h4><?php echo htmlspecialchars($ticket['titulo']); ?></h4>
    <p><strong>Estado:</strong> <?php echo $ticket['estado']; ?></p>
    <p><strong>Prioridad:</strong> <?php echo $ticket['prioridad']; ?></p>
    <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?></p>
    <p><strong>Fecha de Creación:</strong> <?php echo $ticket['fecha_creacion']; ?></p>

    <!-- Formulario para agregar comentarios -->
    <h3>Agregar Comentario</h3>
    <form method="POST">
        <div class="mb-3">
            <label for="descripcion" class="form-label">Comentario</label>
            <textarea class="form-control" name="descripcion" rows="3" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Agregar</button>
    </form>

    <!-- Listado de comentarios -->
    <h3 class="mt-5">Seguimiento</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Autor</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM seguimiento WHERE ticket_id = ? ORDER BY fecha ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($comentario = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $comentario['fecha']; ?></td>
                    <td><?php echo nl2br(htmlspecialchars($comentario['descripcion'])); ?></td>
                    <td><?php echo $comentario['tecnico']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="cliente_panel.php" class="btn btn-secondary mt-3">Volver al Panel</a>
</div>
</body>

</html>
