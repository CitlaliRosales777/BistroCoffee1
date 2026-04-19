<?php
require_once '../config/database.php';
require_once '../includes/reservas-functions.php';
require_once 'includes/functions.php';

if ($_POST) {
    if (guardarReserva($conn, $_POST)) {
        // ¡ÉXITO!
        $mensaje = "¡Reserva confirmada! Te contactaremos en breve.";
        $redirect = "reservas.php?success=1";
    } else {
        $mensaje = "Error al procesar reserva. Intenta de nuevo.";
        $redirect = "reservas.php?error=1";
    }
    
    // Redirigir con mensaje
    $_SESSION['mensaje'] = $mensaje;
    header("Location: $redirect");
    exit;
}
?>

<!--NOMAS SON NOTIFICACIONES QUE VAN DE LA MANO CON LA CARGA DE LA RESERVA-->