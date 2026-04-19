<?php 
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/reservas-functions.php';

$usuario = requiereRol($conn, ['Administrador']);

$mensaje = '';
$filtros = [];

// Filtros GET
$filtros['fecha'] = $_GET['fecha'] ?? '';
$filtros['estado'] = $_GET['estado'] ?? '';
$filtros['busqueda'] = $_GET['buscar'] ?? '';

// Acciones POST
if ($_POST && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    if (isset($_POST['confirmar'])) {
        if (actualizarEstadoReserva($conn, $id, 'Confirmada')) $mensaje = '✅ Reserva confirmada';
    } elseif (isset($_POST['cancelar'])) {
        if (actualizarEstadoReserva($conn, $id, 'Cancelada')) $mensaje = '✅ Reserva cancelada';
    } elseif (isset($_POST['completar'])) {
        if (actualizarEstadoReserva($conn, $id, 'Completada')) $mensaje = '✅ Reserva completada';
    } elseif (isset($_POST['eliminar'])) {
        if (eliminarReserva($conn, $id)) $mensaje = '✅ Reserva eliminada';
    }
    
    // Mantener filtros
    $redirect = 'reservas.php?' . http_build_query($filtros);
    header("Location: $redirect");
    exit;
}

$reservas = getReservas($conn, $filtros);
$stats = statsReservas($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - Admin | Bistro Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #f4a261;
            --secondary: #e76f51;
            --dark: #264653;
        }
        .stats-card { transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-5px); }
        .status-badge { font-size: 0.8rem; }
        .table-actions .btn { margin: 0 1px; }
    </style>
</head>
<body class="bg-light">
    <?php include 'index.php'; // Tu sidebar ?>

    <main class="container-fluid px-4 py-4" style="margin-left: 280px;">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-calendar-check text-warning me-2"></i>
                    Gestión de Reservas
                </h1>
                <small class="text-muted">Controla todas las reservas del restaurante</small>
            </div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-house me-2"></i>Volver Dashboard
            </a>
        </div>

        <!-- ALERTA -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card border-0 shadow-sm h-100 bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-3x mb-3 opacity-75"></i>
                        <h2 class="display-5 fw-bold"><?= $stats['total'] ?></h2>
                        <p class="mb-0">Total Reservas</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card border-0 shadow-sm h-100 bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3 opacity-75"></i>
                        <h2 class="display-5 fw-bold"><?= $stats['pendientes'] ?></h2>
                        <p class="mb-0">Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card border-0 shadow-sm h-100 bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-day fa-3x mb-3 opacity-75"></i>
                        <h2 class="display-5 fw-bold"><?= $stats['hoy'] ?></h2>
                        <p class="mb-0">Hoy</p>
                    </div>
                </div