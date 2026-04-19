<?php 
require_once '../config/database.php';
require_once '../includes/auth.php';

try {
    $usuario = requiereRol($conn, ['Administrador', 'Cajero', 'Chef', 'Cliente']);
    $rol = $usuario['rol_nombre'] ?? 'Cliente';
} catch (Exception $e) {
    header('Location: login.php?error=acceso');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($usuario['Nombre']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- HEADER CON USUARIO -->
    <header style="background: linear-gradient(135deg, var(--jet-black), var(--black));">
        <div class="nav-container">
            <a href="../index.php" class="logo">Bistro & Coffee</a>
            <div style="display: flex; align-items: center; gap: 2rem;">
                <span style="color: var(--white-smoke); font-weight: 500;">
                    <?= htmlspecialchars($usuario['Nombre']) ?> 
                    <span style="color: var(--dusty-taupe); font-size: 0.9rem;">(<?= $rol ?>)</span>
                </span>
                <a href="logout.php" class="btn" style="background: var(--stone-brown); padding: 0.7rem 1.5rem; font-size: 0.9rem;">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <section class="dashboard" style="padding: 4rem 2rem;">
        <div class="container">
            <div class="dashboard-header" style="text-align: center; margin-bottom: 4rem;">
                <h1 style="color: var(--text-primary); font-size: 3rem; margin-bottom: 1rem;">
                    <i class="fas fa-tachometer-alt"></i> Bienvenido, <?= htmlspecialchars($usuario['Nombre']) ?>
                </h1>
                <div class="rol-badge" style="display: inline-block; background: var(--dusty-taupe); color: var(--white-smoke); padding: 0.8rem 2rem; border-radius: 50px; font-weight: 600; font-size: 1.1rem;">
                    <?= $rol ?>
                </div>
            </div>

            <?php if ($rol == 'Administrador'): ?>
                <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <a href="../admin/" class="dashboard-card card">
                        <i class="fas fa-cogs" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1.5rem;"></i>
                        <h3>Panel Administrativo</h3>
                        <p>Gestión completa del sistema</p>
                    </a>
                    <a href="reservas.php" class="dashboard-card card">
                        <i class="fas fa-calendar-check" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1.5rem;"></i>
                        <h3>Reservas</h3>
                        <p>Ver y gestionar reservas</p>
                    </a>
                    <a href="#" class="dashboard-card card">
                        <i class="fas fa-chart-bar" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1.5rem;"></i>
                        <h3>Reportes</h3>
                        <p>Estadísticas y ventas</p>
                    </a>
                </div>

            <?php elseif ($rol == 'Cajero'): ?>
                <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <a href="#" class="dashboard-card card">
                        <i class="fas fa-cash-register" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1.5rem;"></i>
                        <h3>Caja Rápida</h3>
                        <p>Registrar ventas</p>
                    </a>
                    <a href="carrito.php" class="dashboard-card card">
                        <i class="fas fa-shopping-cart" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1.5rem;"></i>
                        <h3>Pedidos Pendientes</h3>
                        <p>Ver pedidos online</p>
                    </a>
                </div>

            <?php elseif ($rol == 'Chef'): ?>
                <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <a href="#" class="dashboard-card card">
                        <i class="fas fa-utensils" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1.5rem;"></i>
                        <h3>Órdenes Cocina</h3>
                        <p>Preparación actual</p>
                    </a>
                    <a href="#" class="dashboard-card card">
                        <i class="fas fa-clock" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1.5rem;"></i>
                        <h3>Próximas Órdenes</h3>
                        <p>Próximas preparaciones</p>
                    </a>
                </div>

            <?php else: ?>
                <div style="text-align: center; color: var(--text-secondary);">
                    <h2>Bienvenido Cliente</h2>
                    <p>Tu perfil está en desarrollo</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <style>
    .dashboard-card {
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        text-align: center;
        padding: 2.5rem 2rem;
    }
    .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 70px var(--shadow-heavy);
    }
    .dashboard-card h3 {
        color: var(--text-primary);
        margin-bottom: 1rem;
        font-size: 1.4rem;
    }
    </style>
</body>
</html>