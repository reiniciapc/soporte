<?php
session_start();
include 'conexion.php';

// Verificar si existe al menos un administrador
$sql_check_admin = "SELECT COUNT(*) AS admin_count FROM clientes WHERE rol = 'Administrador'";
$result = $conn->query($sql_check_admin);
$row = $result->fetch_assoc();
$admin_count = $row['admin_count'];

if ($admin_count == 0) {
    // No existe ningún administrador, redirigir a registro.php
    header("Location: registro.php?mensaje=No%20existe%20administrador.%20Es%20necesario%20crear%20uno.");
    exit();
}

// Verificar si el usuario ya está logueado como administrador
if (isset($_SESSION['cliente_id']) && $_SESSION['rol'] == 'Administrador') {
    header("Location: admin_panel.php");
    exit();
}

// Si no está logueado, redirigir a login.php
header("Location: login.php");
exit();
?>
