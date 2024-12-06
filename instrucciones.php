<?php
session_start();
include 'conexion.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Instrucciones de Funcionamiento</title>
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
            margin-top: 30px;
        }
        h2, h3 {
            color: #343a40;
        }
        .btn-primary {
            margin-top: 20px;
        }
        .list-group-item {
            font-size: 1.1em;
        }
        .list-group-item .badge {
            float: right;
        }
    </style>
</head>
<body>
<div class="container">
    <h2><i class="fas fa-info-circle"></i> Instrucciones de Funcionamiento</h2>
    <p>A continuación encontrarás las instrucciones para el uso del sistema, tanto para administradores como para clientes.</p>

    <hr>

    <h3><i class="fas fa-user-shield"></i> Instrucciones para Administradores</h3>
    <ul class="list-group mb-5">
        <li class="list-group-item">
            <strong>Registrar y gestionar usuarios:</strong> 
            Los administradores pueden registrar nuevos usuarios (clientes y administradores), editar su información y eliminar usuarios.
            <span class="badge bg-primary">Registro</span>
        </li>
        <li class="list-group-item">
            <strong>Asignar bonos de horas:</strong> 
            Los administradores pueden asignar un número de horas como bono a cada cliente. Estas horas se descontarán según el uso de servicios.
            <span class="badge bg-warning">Bonos</span>
        </li>
        <li class="list-group-item">
            <strong>Gestionar tickets de soporte:</strong> 
            Los administradores tienen acceso a todos los tickets de los clientes, y pueden cambiar su estado, añadir comentarios y cerrar tickets.
            <span class="badge bg-info">Tickets</span>
        </li>
        <li class="list-group-item">
            <strong>Panel de administración:</strong> 
            Desde el panel principal, los administradores pueden filtrar los tickets por estado, prioridad y buscar por nombre de cliente o título de ticket.
            <span class="badge bg-secondary">Panel</span>
        </li>
        <li class="list-group-item">
            <strong>Eliminar usuarios:</strong> 
            Los administradores pueden eliminar usuarios, aunque algunos usuarios están protegidos y no pueden ser eliminados (por ejemplo, administradores fundamentales).
            <span class="badge bg-danger">Eliminar</span>
        </li>
    </ul>

    <h3><i class="fas fa-user"></i> Instrucciones para Clientes</h3>
    <ul class="list-group mb-5">
        <li class="list-group-item">
            <strong>Crear nuevos tickets:</strong> 
            Los clientes pueden crear nuevos tickets para solicitar soporte. Es importante proporcionar un título y una descripción detallada del problema.
            <span class="badge bg-success">Nuevo Ticket</span>
        </li>
        <li class="list-group-item">
            <strong>Filtrar y buscar tickets:</strong> 
            Los clientes pueden filtrar los tickets por estado (Abierto, En Proceso, Cerrado), prioridad (Alta, Media, Baja) y buscar tickets por título.
            <span class="badge bg-info">Filtrar Tickets</span>
        </li>
        <li class="list-group-item">
            <strong>Consultar información de bono:</strong> 
            Si el cliente tiene horas asignadas, puede consultar la cantidad de horas asignadas, los minutos usados y los minutos restantes.
            <span class="badge bg-primary">Bono de Horas</span>
        </li>
        <li class="list-group-item">
            <strong>Agregar comentarios a los tickets:</strong> 
            Los clientes pueden añadir comentarios adicionales a sus tickets para mantener informados a los administradores del progreso o nuevos detalles.
            <span class="badge bg-warning">Comentarios</span>
        </li>
        <li class="list-group-item">
            <strong>Cerrar sesión:</strong> 
            Recuerde cerrar sesión al finalizar el uso del sistema para proteger la seguridad de su cuenta.
            <span class="badge bg-danger">Cerrar Sesión</span>
        </li>
    </ul>

    <a href="index.php" class="btn btn-primary"><i class="fas fa-home"></i> Volver al Inicio</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
