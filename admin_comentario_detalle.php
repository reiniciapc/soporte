<?php
session_start();
if (!isset($_SESSION['cliente_id']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: login.php");
    exit();
}
include 'conexion.php';

$comentario_id = $_GET['id'];
$ticket_id = $_GET['ticket_id'];

// Eliminar comentario si la acción es eliminar
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $sql_eliminar_comentario = "DELETE FROM comentarios WHERE id = ?";
    $stmt_eliminar_comentario = $conn->prepare($sql_eliminar_comentario);
    $stmt_eliminar_comentario->bind_param("i", $comentario_id);
    $stmt_eliminar_comentario->execute();
    header("Location: admin_ticket_detalle.php?id=$ticket_id");
    exit();
}

// Obtener detalles del comentario
$sql_comentario = "SELECT * FROM comentarios WHERE id = ?";
$stmt_comentario = $conn->prepare($sql_comentario);
$stmt_comentario->bind_param("i", $comentario_id);
$stmt_comentario->execute();
$comentario = $stmt_comentario->get_result()->fetch_assoc();

if (!$comentario) {
    header("Location: admin_ticket_detalle.php?id=$ticket_id");
    exit();
}

// Procesar edición del comentario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_comentario = $_POST['comentario'];
    $minutos_usados = isset($_POST['minutos_usados']) ? $_POST['minutos_usados'] : $comentario['minutos_usados'];

    $sql_actualizar_comentario = "UPDATE comentarios SET comentario = ?, minutos_usados = ? WHERE id = ?";
    $stmt_actualizar_comentario = $conn->prepare($sql_actualizar_comentario);
    $stmt_actualizar_comentario->bind_param("sii", $nuevo_comentario, $minutos_usados, $comentario_id);
    $stmt_actualizar_comentario->execute();

    header("Location: admin_ticket_detalle.php?id=$ticket_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Editar Comentario</title>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Editar Comentario</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="comentario" class="form-label">Comentario</label>
            <textarea name="comentario" id="comentario" class="form-control" rows="3" required><?php echo htmlspecialchars($comentario['comentario']); ?></textarea>
        </div>
        <?php if ($_SESSION['cliente_nombre'] == $comentario['creado_por']): ?>
        <div class="mb-3">
            <label for="minutos_usados" class="form-label">Minutos Usados</label>
            <input type="number" name="minutos_usados" id="minutos_usados" class="form-control" 
                   value="<?php echo htmlspecialchars($comentario['minutos_usados']); ?>">
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="admin_ticket_detalle.php?id=<?php echo $ticket_id; ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>
