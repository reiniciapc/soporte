<?php
$host = 'localhost'; // Cambia si usas un servidor diferente
$db = 'sistema_tickets'; // Nombre de la base de datos
$user = 'root'; // Usuario de la base de datos
$password = ''; // Contraseña de la base de datos

// Configuraciones adicionales
$correoAdministrador = 'reiniciapc@reiniciapc.com'; // Correo del administrador para notificaciones
$dominioInstalacion = 'soporte.reiniciapc.net'; // Dominio donde se instala el programa

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
