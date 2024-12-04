<?php
session_start();
if (!isset($_SESSION['cliente_id']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: login.php");
    exit();
}
include 'conexion.php';

// Obtener ID del ticket
if (!isset($_GET['id'])) {
    header("Location: admin_panel.php");
    exit();
}
$ticket_id = $_GET['id'];

// Verificar si el ticket existe
$sql_ticket = "SELECT * FROM tickets WHERE id = ?";
$stmt_ticket = $conn->prepare($sql_ticket);
$stmt_ticket->bind_param("i", $ticket_id);
$stmt_ticket->execute();
$result_ticket = $stmt_ticket->get_result();

if ($result_ticket->num_rows === 0) {
    header("Location: admin_panel.php");
    exit();
}

$ticket = $result_ticket->fetch_assoc();
$cliente_id = $ticket['cliente_id'];

// Revertir horas descontadas del bono
$sql_horas = "SELECT * FROM consumo_horas WHERE ticket_id = ?";
$stmt_horas = $conn->prepare($sql_horas);
$stmt_horas->bind_param("i", $ticket_id);
$stmt_horas->execute();
$result_horas = $stmt_horas->get_result();

while ($consumo = $result_horas->fetch_assoc()) {
    $horas_utilizadas = $consumo['horas_consumidas'];
    $bono_id = $consumo['bono_id'];

    // Devolver horas al bono
    $sql_revertir_bono = "UPDATE bonos SET horas_consumidas = horas_consumidas - ? WHERE id = ?";
    $stmt_revertir_bono = $conn->prepare($sql_revertir_bono);
    $stmt_revertir_bono->bind_param("ii", $horas_utilizadas, $bono_id);
    $stmt_revertir_bono->execute();
}

// Eliminar consumo de horas asociado al ticket
$sql_eliminar_horas = "DELETE FROM consumo_horas WHERE ticket_id = ?";
$stmt_eliminar_horas = $conn->prepare($sql_eliminar_horas);
$stmt_eliminar_horas->bind_param("i", $ticket_id);
$stmt_eliminar_horas->execute();

// Eliminar comentarios asociados al ticket
$sql_eliminar_comentarios = "DELETE FROM comentarios WHERE ticket_id = ?";
$stmt_eliminar_comentarios = $conn->prepare($sql_eliminar_comentarios);
$stmt_eliminar_comentarios->bind_param("i", $ticket_id);
$stmt_eliminar_comentarios->execute();

// Eliminar el ticket
$sql_eliminar_ticket = "DELETE FROM tickets WHERE id = ?";
$stmt_eliminar_ticket = $conn->prepare($sql_eliminar_ticket);
$stmt_eliminar_ticket->bind_param("i", $ticket_id);
$stmt_eliminar_ticket->execute();

header("Location: admin_panel.php?mensaje=Ticket eliminado correctamente.");
exit();
