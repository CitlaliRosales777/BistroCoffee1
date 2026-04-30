<?php
session_start();
require_once '../config/database.php';
require_once '../includes/reservas-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Método no permitido'];
    header('Location: reservas.php');
    exit;
}

$datos = [
    'nombre' => trim($_POST['nombre'] ?? ''),
    'telefono' => trim($_POST['telefono'] ?? ''),
    'correo' => trim($_POST['correo'] ?? ''),
    'personas' => (int)($_POST['personas'] ?? 2),
    'fecha' => $_POST['fecha'] ?? '',
    'hora' => trim($_POST['hora'] ?? ''),
    'notas' => trim($_POST['notas'] ?? '')
];

// Validaciones
$errores = [];
if (empty($datos['nombre']) || strlen($datos['nombre']) < 2) $errores[] = 'Nombre inválido';
if (empty($datos['telefono']) || strlen($datos['telefono']) < 10) $errores[] = 'Teléfono inválido';
if (empty($datos['fecha']) || empty($datos['hora'])) $errores[] = 'Fecha u hora inválida';
if ($datos['personas'] < 2 || $datos['personas'] > 4) $errores[] = 'Personas inválidas';

// ✅ VALIDACIÓN CRÍTICA: Horario ocupado
if (!validarReserva($conn, $datos['fecha'], $datos['hora'])) {
    $errores[] = '❌ Ese horario ya está ocupado. Elige otro.';
}

if (!empty($errores)) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => implode('<br>', $errores)];
    header('Location: reservas.php?fecha=' . $datos['fecha']);
    exit;
}

// ✅ Guardar reserva
if (guardarReserva($conn, $datos)) {
    $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => '✅ ¡Reserva creada! Te contactaremos pronto.'];
} else {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al guardar reserva'];
}

header('Location: reservas.php?fecha=' . $datos['fecha']);
exit;
?>