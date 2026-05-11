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

// ⭐ CONTADOR CARRITO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$total_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $cantidad) {
        if (is_numeric($cantidad)) {
            $total_carrito += (int)$cantidad;
        }
    }
}

// ⭐ LÓGICA CHEF (sin cambios)
if ($rol == 'Chef') {
    $estados = ['ingreso', 'elaboracion', 'terminado'];
    $ordenesPorEstado = [];
   
    foreach ($estados as $estado) {
        $sql = "
            SELECT TOP 20 Id_Venta, Cajero, Total, Moneda,
                   CAST(Productos AS NVARCHAR(MAX)) as Productos,
                   Fecha,
                   ISNULL(Estado_Cocina, 'ingreso') as estado
            FROM Ventas_Caja
            WHERE ISNULL(Estado_Cocina, 'ingreso') = ?
            AND Fecha > DATEADD(HOUR, -48, GETDATE())
            ORDER BY Fecha DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$estado]);
        $ordenesPorEstado[$estado] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
   
    $nuevas = $ordenesPorEstado['ingreso'];
    $proceso = $ordenesPorEstado['elaboracion'];
    $listas = $ordenesPorEstado['terminado'];
   
    $ordenes = array_merge($nuevas, $proceso, $listas);
    usort($ordenes, fn($a, $b) => strtotime($b['Fecha']) - strtotime($a['Fecha']));
} else {
    $ordenes = $nuevas = $proceso = $listas = [];
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
    :root {
        --jet-black: #1a1a1a;
        --black: #2d2d2d;
        --white-smoke: #f5f5f5;
        --dusty-taupe: #8b7d6b;
        --stone-brown: #a68a64;
        --text-primary: #2c3e50;
        --text-secondary: #7f8c8d;
        --shadow-heavy: rgba(0,0,0,0.3);
        --shadow-medium: rgba(0,0,0,0.2);
    }
    .dashboard-card {
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        text-align: center;
        padding: 2.5rem 2rem;
        border-radius: 20px;
        background: white;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 70px var(--shadow-heavy);
    }
    .card { transition: all 0.3s ease; }
    .card:hover { transform: translateY(-5px); }
    .badge { font-weight: 600; }

    /* ⭐ HEADER COMÚN PARA CAJERO Y CHEF */
    .operativo-header {
        background: linear-gradient(135deg, var(--dusty-taupe), var(--stone-brown)) !important;
        box-shadow: 0 12px 40px var(--shadow-heavy) !important;
        padding: 1rem 0 !important;
    }
    .operativo-logo {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        background: rgba(255,255,255,0.15) !important;
        padding: 1rem 1.5rem !important;
        border-radius: 15px !important;
        backdrop-filter: blur(10px) !important;
        font-weight: 800 !important;
        font-size: 1.6rem !important;
    }
    .status-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        background: rgba(255,255,255,0.2) !important;
        padding: 0.8rem 1.8rem !important;
        border-radius: 25px !important;
        color: var(--white-smoke) !important;
        font-weight: 700 !important;
        backdrop-filter: blur(10px) !important;
        animation: pulse-glow 2s infinite;
    }
    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 4px 20px rgba(255,255,255,0.3); }
        50% { box-shadow: 0 4px 30px rgba(255,255,255,0.6); }
    }
    </style>
</head>
<body>

    <?php if (in_array($rol, ['Cajero', 'Chef'])): ?>
    <!-- ⭐ HEADER COMÚN PARA CAJERO Y CHEF -->
    <header class="operativo-header">
        <div class="nav-container" style="display: flex; align-items: center; justify-content: space-between; max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
            <a href="dashboard.php" class="operativo-logo">
                <img src="../assets/images/logo.png" alt="Bistro Coffee" style="width: 60px; height: 60px;">
                <span>
                    <?php if ($rol == 'Cajero'): ?>
                        Bistro <span style="color: #0b0b08;">Caja</span>
                    <?php else: ?>
                        Bistro <span style="color: #0b0b08;">Cocina</span>
                    <?php endif; ?>
                </span>
            </a>

            <div style="text-align: center; flex: 1; max-width: 400px;">
                <div class="status-badge">
                    <?php if ($rol == 'Cajero'): ?>
                        <i class="fas fa-circle-dot" style="color: #00ff88;"></i>
                        CAJERO ACTIVO
                        <i class="fas fa-cash-register"></i>
                    <?php else: ?>
                        <i class="fas fa-circle-dot" style="color: #00ff88;"></i>
                        CHEF ACTIVO
                        <i class="fas fa-fire"></i>
                    <?php endif; ?>
                </div>
                <div style="color: var(--white-smoke); font-weight: 600; margin-top: 0.5rem; font-size: 1.1rem;">
                    <?= htmlspecialchars($usuario['Nombre']) ?>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if ($rol == 'Cajero'): ?>
                    <!-- BOTONES ESPECÍFICOS CAJERO -->
                    <a href="carrito.php" class="btn btn-light position-relative" title="Pedidos Online">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($total_carrito > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $total_carrito > 99 ? '99+' : $total_carrito ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="caja.php" class="btn btn-light">
                        <i class="fas fa-plus-circle"></i> Nueva Venta
                    </a>
                <?php else: ?>
                    <!-- BOTONES ESPECÍFICOS CHEF -->
                    <a href="cocina.php#ingreso" class="btn btn-light position-relative" title="Nuevas Órdenes">
                        <i class="fas fa-utensils"></i>
                        <?php if (count($nuevas) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                            <?= count($nuevas) > 99 ? '99+' : count($nuevas) ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="cocina.php" class="btn btn-light">
                        <i class="fas fa-fire"></i> Cocina
                    </a>
                <?php endif; ?>
                
                <!-- BOTÓN COMÚN: SALIR -->
                <a href="logout.php" class="btn btn-light">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <?php else: ?>
    <!-- Header Original para otros roles -->
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
    <?php endif; ?>

    <section class="dashboard" style="padding: <?= in_array($rol, ['Cajero', 'Chef']) ? '3rem 2rem' : '4rem 2rem' ?>;">
        <div class="container">
            <!-- Header Dashboard -->
            <div class="dashboard-header text-center mb-5">
                <h1 style="color: var(--text-primary); font-size: 3rem; margin-bottom: 1rem;">
                    <i class="fas fa-tachometer-alt"></i> Bienvenido, <?= htmlspecialchars($usuario['Nombre']) ?>
                </h1>
                <div class="rol-badge" style="display: inline-block; background: var(--dusty-taupe); color: var(--white-smoke); padding: 0.8rem 2rem; border-radius: 50px; font-weight: 600; font-size: 1.1rem;">
                    <?= $rol ?>
                </div>
            </div>

            <?php if ($rol == 'Administrador'): ?>
                <!-- DASHBOARD ADMIN (sin cambios) -->
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
                <!-- DASHBOARD CAJERO -->
                <div class="row g-4">
                    <!-- CAJA RÁPIDA -->
                    <div class="col-6 col-md-3">
                        <a href="caja.php" class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden"
                           style="border-radius: 20px; background: linear-gradient(135deg, #FFE0B2, #FFCC80);">
                            <div class="p-4 text-center">
                                <i class="fas fa-cash-register fa-3x mb-2 text-warning"></i>
                                <h3 class="mb-1 fw-bold text-dark">Caja Rápida</h3>
                                <p class="mb-2 text-muted fw-semibold">Registrar Venta</p>
                                <div class="badge bg-warning w-100 py-2">EMPEZAR</div>
                            </div>
                        </a>
                    </div>

                    <!-- PEDIDOS ONLINE -->
                    <div class="col-6 col-md-3">
                        <a href="carrito.php" class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden"
                           style="border-radius: 20px; background: linear-gradient(135deg, #FFCDD2, #FF8A80);">
                            <div class="p-4 text-center">
                                <i class="fas fa-shopping-cart fa-3x mb-2 text-danger"></i>
                                <h3 class="mb-1 fw-bold text-dark"><?= $total_carrito ?></h3>
                                <p class="mb-2 text-danger fw-semibold">Pedidos Online</p>
                                <div class="badge bg-danger w-100 py-2">Ver Pedidos</div>
                            </div>
                        </a>
                    </div>

                    <!-- ESTADÍSTICAS -->
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden"
                             style="border-radius: 20px; background: linear-gradient(135deg, #C8E6C9, #81C784); color: #1b5e20;">
                            <div class="p-4 text-center">
                                <i class="fas fa-chart-bar fa-3x mb-2"></i>
                                <h3 class="mb-1 fw-bold">$12,450</h3>
                                <p class="mb-2 fw-semibold">Ventas Hoy</p>
                            </div>
                        </div>
                    </div>

                    <!-- TOTAL / ACCIÓN -->
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-xl border-0 position-relative overflow-hidden"
                             style="border-radius: 20px; background: linear-gradient(135deg, #B2EBF2, #4DD0E1);">
                            <div class="p-4 text-center">
                                <i class="fas fa-fire fa-3x mb-2 text-info"></i>
                                <h2 class="mb-1 fw-bold">Activo</h2>
                                <p class="mb-3 fw-semibold text-info">Turno en Curso</p>
                                <a href="caja.php" class="btn btn-light w-100 py-2 fw-bold">
                                    <i class="fas fa-arrow-right me-2"></i>Ir a Caja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($rol == 'Chef'): ?>
                <!-- DASHBOARD CHEF -->
                <div class="row g-4">
                    <!-- CONTADORES COCINA -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <a href="cocina.php#ingreso" class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden"
                               style="border-radius: 20px; background: linear-gradient(135deg, #FFF3CD, #FFEAA7);">
                                <div class="p-4 text-center">
                                    <i class="fas fa-clock fa-3x mb-2 text-warning"></i>
                                    <h3 class="mb-1 fw-bold text-dark"><?= count($nuevas) ?></h3>
                                    <p class="mb-2 text-muted fw-semibold">En Espera</p>
                                    <div class="badge bg-warning w-100 py-2">Ver todas</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="cocina.php#elaboracion" class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden"
                               style="border-radius: 20px; background: linear-gradient(135deg, #E1F5FE, #B3E5FC);">
                                <div class="p-4 text-center">
                                    <i class="fas fa-spinner fa-spin fa-3x mb-2 text-info"></i>
                                    <h3 class="mb-1 fw-bold text-dark"><?= count($proceso) ?></h3>
                                    <p class="mb-2 text-muted fw-semibold">En Proceso</p>
                                    <div class="badge bg-info w-100 py-2">Gestionar</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="cocina.php#terminado" class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden"
                               style="border-radius: 20px; background: linear-gradient(135deg, #E8F5E8, #C8E6C9);">
                                <div class="p-4 text-center">
                                    <i class="fas fa-check-circle fa-3x mb-2 text-success"></i>
                                    <h3 class="mb-1 fw-bold text-dark"><?= count($listas) ?></h3>
                                    <p class="mb-2 text-muted fw-semibold">Listas</p>
                                    <div class="badge bg-success w-100 py-2">Entregar</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden"
                                 style="border-radius: 20px; background: linear-gradient(135deg, #F3E5F5, #E1BEE7);">
                                <div class="p-4 text-center">
                                    <i class="fas fa-fire fa-3x mb-2 text-danger"></i>
                                    <h3 class="mb-1 fw-bold text-dark"><?= count($ordenes) ?></h3>
                                    <p class="mb-2 text-muted fw-semibold">Total Hoy</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- CLIENTE -->
                <div class="text-center py-5" style="color: var(--text-secondary);">
                    <i class="fas fa-user-circle fa-5x mb-4 opacity-50"></i>
                    <h2>Bienvenido Cliente</h2>
                    <p class="lead">Tu perfil está en desarrollo</p>
                    <a href="../index.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-home me-2"></i>Ir al Menú
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>