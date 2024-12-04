# Sistema de Gestión de Tickets para Soporte al Cliente
Sistema web de gestión de tickets que facilita la comunicación entre clientes y administradores para el soporte de incidencias y consultas. Permite gestionar tickets, hacer seguimientos, y asignar bonos de tiempo de soporte.
Este repositorio contiene el código fuente de un sistema de gestión de tickets desarrollado en PHP, MySQL, y HTML. El sistema está diseñado para facilitar el soporte técnico a través de la creación y seguimiento de tickets de clientes.

## Características principales
- **Módulo de Autenticación**: Los usuarios se pueden registrar como clientes o iniciar sesión, mientras que los administradores tienen acceso al panel completo para gestionar tickets.
- **Gestión de Tickets**: Los clientes pueden crear tickets para reportar problemas o hacer consultas. Cada ticket tiene su propio detalle donde se puede seguir el estado y agregar comentarios.
- **Panel Administrativo**: Los administradores tienen un panel para ver todos los tickets, gestionar su estado y priorización.
- **Bonos de Soporte**: Los administradores pueden asignar y gestionar bonos de tiempo para los clientes, permitiendo un control más detallado del consumo de tiempo de soporte.
- **Comentarios y Seguimiento**: Tanto clientes como administradores pueden agregar comentarios a los tickets para mantener una comunicación clara y efectiva.

El objetivo de este sistema es proporcionar una herramienta ágil y eficiente para empresas que ofrecen servicios técnicos o de atención al cliente, permitiendo una organización clara de las consultas y problemas reportados.

## Archivos Clave del Proyecto
1. **conexion.php**: Archivo de configuración para conectarse a la base de datos. Contiene las credenciales y configuraciones para acceder al servidor MySQL.
2. **login.php**: Gestiona la autenticación de usuarios (clientes y administradores).
3. **admin_panel.php**: Panel de administración donde se pueden gestionar todos los tickets y filtrar por estado o prioridad.
4. **cliente_panel.php**: Panel donde los clientes pueden ver sus tickets y crear nuevos.
5. **sistema_tickets.sql**: Archivo con la estructura de la base de datos necesaria para el sistema. Define tablas como `clientes`, `tickets`, `bonos`, entre otras.
6. **eliminar_ticket.php**: Archivo encargado de eliminar tickets existentes (accesible solo para administradores).
7. **index.php**: Página de inicio del sistema que presenta opciones de login o registro a los usuarios.
8. **instrucciones.php**: Proporciona instrucciones detalladas tanto para clientes como para administradores sobre cómo utilizar el sistema de tickets.
9. **logout.php**: Archivo que gestiona el cierre de sesión de los usuarios.
10. **registro.php**: Página donde los usuarios pueden registrarse en el sistema.
11. **admin_ticket_detalle.php**: Proporciona una vista detallada de cada ticket para los administradores, permitiendo la actualización del estado del ticket y el seguimiento.
12. **cliente_ticket_detalle.php**: Página que muestra a los clientes los detalles del ticket, permitiendo agregar comentarios y ver el historial del mismo.
13. **ticket_detalle.php**: Página común que permite a clientes y administradores visualizar detalles específicos del ticket y agregar seguimientos.

## Tecnologías Utilizadas
- **PHP** para la lógica del servidor.
- **MySQL** para la gestión de la base de datos.
- **HTML y CSS** (Bootstrap) para la interfaz de usuario.
- **JavaScript** (principalmente para mejorar la interacción del usuario).

## Instalación y Configuración
1. Clona este repositorio:
   ```bash
   git clone https://github.com/tu-usuario/mi-proyecto-ticket.git
