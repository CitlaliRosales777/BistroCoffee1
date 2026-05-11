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

// ⭐ LÓGICA CHEF
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
    :root {
        --cafe-dark: #3C2F2A;
        --cafe-brown: #6B5A4A;
        --cafe-taupe: #A9927D;
        --cream: #F8F4ED;
        --beige-light: #FAF7F2;
        --beige-lighter: #FFFBF5;
        --text-dark: #3C2F2A;
        --shadow-soft: rgba(107, 90, 74, 0.1);
    }

    body {
        background: linear-gradient(to bottom, #FFFBF5, #F8F4ED);
        color: var(--text-dark);
        font-family: 'Segoe UI', system-ui, sans-serif;
    }

    /* HEADER - Estilo claro y elegante */
    .operativo-header {
        background: linear-gradient(135deg, var(--cafe-dark), #4A3A32) !important;
        box-shadow: 0 8px 25px var(--shadow-soft) !important;
        padding: 1rem 0 !important;
    }
    
    .operativo-logo {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        color: white !important;
        text-decoration: none;
        font-weight: 800 !important;
        font-size: 1.55rem !important;
    }

    .status-badge {
        background: rgba(255,255,255,0.15) !important;
        color: white !important;
        padding: 0.7rem 1.6rem !important;
        border-radius: 30px !important;
        backdrop-filter: blur(10px);
    }

    .dashboard-card {
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        text-align: center;
        padding: 2.5rem 2rem;
        border-radius: 22px;
        background: white;
        box-shadow: 0 10px 30px var(--shadow-soft);
        border: 1px solid #f0e9df;
    }
    
    .dashboard-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 45px rgba(169, 146, 125, 0.2);
    }

    /* Tarjetas del Dashboard */
    .card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 8px 25px var(--shadow-soft);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 35px rgba(107, 90, 74, 0.15);
    }
    </style>
</head>
<body>

    <?php if (in_array($rol, ['Cajero', 'Chef'])): ?>
    <!-- HEADER OPERATIVO -->
    <header class="operativo-header">
        <div class="nav-container" style="display: flex; align-items: center; justify-content: space-between; max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
            <a href="dashboard.php" class="operativo-logo">
                <img src="../assets/images/logo.png" alt="Bistro Coffee" style="width: 60px; height: 60px; border-radius: 50%;">
                <span>
                    <?php if ($rol == 'Cajero'): ?>
                        Bistro <span style="color: #E8D5B8;">Caja</span>
                    <?php else: ?>
                        Bistro <span style="color: #E8D5B8;">Cocina</span>
                    <?php endif; ?>
                </span>
            </a>

            <div style="text-align: center; flex: 1; max-width: 400px;">
                <div class="status-badge">
                    <?php if ($rol == 'Cajero'): ?>
                        <i class="fas fa-circle-dot" style="color: #A9927D;"></i>
                        CAJERO ACTIVO
                    <?php else: ?>
                        <i class="fas fa-circle-dot" style="color: #A9927D;"></i>
                        CHEF ACTIVO
                    <?php endif; ?>
                </div>
                <div style="color: #E8D5B8; font-weight: 600; margin-top: 0.4rem;">
                    <?= htmlspecialchars($usuario['Nombre']) ?>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if ($rol == 'Cajero'): ?>
                    <a href="carrito.php" class="btn btn-light position-relative">
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
                    <a href="cocina.php" class="btn btn-light">
                        <i class="fas fa-utensils"></i> Cocina
                    </a>
                <?php endif; ?>
                
                <a href="logout.php" class="btn btn-light">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <?php else: ?>
    <!-- Header para otros roles -->
    <header style="background: linear-gradient(135deg, var(--cafe-dark), #5A473C); padding: 1.2rem 0;">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="../index.php" class="text-white text-decoration-none fs-4 fw-bold">Bistro & Coffee</a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white">
                    <?= htmlspecialchars($usuario['Nombre']) ?> 
                    <small>(<?= $rol ?>)</small>
                </span>
                <a href="logout.php" class="btn btn-light">Salir</a>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <section class="dashboard py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h1 style="color: var(--cafe-brown); font-weight: 700;">
                    <i class="fas fa-tachometer-alt me-3"></i> 
                    Bienvenido, <?= htmlspecialchars($usuario['Nombre']) ?>
                </h1>
                <div class="d-inline-block px-4 py-2 rounded-pill" 
                     style="background: var(--cafe-taupe); color: white; font-weight: 600;">
                    <?= $rol ?>
                </div>
            </div>

            <?php if ($rol == 'Administrador'): ?>
                <div class="row g-4">
                    <a href="../admin/" class="col-md-4 dashboard-card">
                        <i class="fas fa-cogs fa-3x mb-3" style="color: var(--cafe-taupe);"></i>
                        <h4>Panel Administrativo</h4>
                        <p class="text-muted">Gestión completa del sistema</p>
                    </a>
                    <a href="reservas.php" class="col-md-4 dashboard-card">
                        <i class="fas fa-calendar-check fa-3x mb-3" style="color: var(--cafe-taupe);"></i>
                        <h4>Reservas</h4>
                        <p class="text-muted">Ver y gestionar reservas</p>
                    </a>
                    <a href="#" class="col-md-4 dashboard-card">
                        <i class="fas fa-chart-bar fa-3x mb-3" style="color: var(--cafe-taupe);"></i>
                        <h4>Reportes</h4>
                        <p class="text-muted">Estadísticas y ventas</p>
                    </a>
                </div>

            <?php elseif ($rol == 'Cajero'): ?>
                <div class="row g-4">
                    <div class="col-6 col-md-3">
                        <a href="caja.php" class="card h-100 text-decoration-none" 
                           style="background: linear-gradient(135deg, #FFF4E6, #FFE8C8);">
                            <div class="p-4 text-center">
                                <i class="fas fa-cash-register fa-3x mb-3" style="color: #D98C3D;"></i>
                                <h4 class="fw-bold text-dark">Caja Rápida</h4>
                                <p class="text-muted">Registrar Venta</p>
                                <span class="badge bg-warning px-4 py-2">EMPEZAR</span>
                            </div>
                        </a>
                    </div>

                    <div class="col-6 col-md-3">
                        <a href="carrito.php" class="card h-100 text-decoration-none" 
                           style="background: linear-gradient(135deg, #FCE4E4, #F8C8C8);">
                            <div class="p-4 text-center">
                                <i class="fas fa-shopping-cart fa-3x mb-3" style="color: #E06C6C;"></i>
                                <h4 class="fw-bold"><?= $total_carrito ?></h4>
                                <p class="text-danger fw-semibold">Pedidos Online</p>
                                <span class="badge bg-danger px-4 py-2">Ver Pedidos</span>
                            </div>
                        </a>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="card h-100" style="background: linear-gradient(135deg, #E8F5E8, #C8E6C9); color: #1e4d2b;">
                            <div class="p-4 text-center">
                                <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                <h4 class="fw-bold">$12,450</h4>
                                <p class="fw-semibold">Ventas Hoy</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="card h-100" style="background: linear-gradient(135deg, #E6F3FA, #B8E0F0);">
                            <div class="p-4 text-center">
                                <i class="fas fa-fire fa-3x mb-3 text-info"></i>
                                <h4 class="fw-bold">Activo</h4>
                                <p class="text-info fw-semibold">Turno en Curso</p>
                                <a href="caja.php" class="btn btn-light w-100 mt-2">Ir a Caja</a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($rol == 'Chef'): ?>
                <div class="row g-4">
                    <div class="col-6 col-md-3">
                        <a href="cocina.php#ingreso" class="card h-100 text-decoration-none" 
                           style="background: linear-gradient(135deg, #FFF8E1, #FFEEB8);">
                            <div class="p-4 text-center">
                                <i class="fas fa-clock fa-3x mb-3" style="color: #D9A23B;"></i>
                                <h3 class="fw-bold"><?= count($nuevas) ?></h3>
                                <p class="text-muted">En Espera</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="cocina.php#elaboracion" class="card h-100 text-decoration-none" 
                           style="background: linear-gradient(135deg, #E6F0FA, #B8D4F0);">
                            <div class="p-4 text-center">
                                <i class="fas fa-spinner fa-spin fa-3x mb-3" style="color: #4A8CC7;"></i>
                                <h3 class="fw-bold"><?= count($proceso) ?></h3>
                                <p class="text-muted">En Proceso</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="cocina.php#terminado" class="card h-100 text-decoration-none" 
                           style="background: linear-gradient(135deg, #E8F5E8, #C8E6C9);">
                            <div class="p-4 text-center">
                                <i class="fas fa-check-circle fa-3x mb-3" style="color: #4A9C5E;"></i>
                                <h3 class="fw-bold"><?= count($listas) ?></h3>
                                <p class="text-muted">Listas</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100" style="background: linear-gradient(135deg, #F0EDE8, #E0D9C8);">
                            <div class="p-4 text-center">
                                <i class="fas fa-fire fa-3x mb-3" style="color: var(--cafe-brown);"></i>
                                <h3 class="fw-bold"><?= count($ordenes) ?></h3>
                                <p class="text-muted">Total Hoy</p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-circle fa-5x mb-4" style="color: var(--cafe-taupe); opacity: 0.6;"></i>
                    <h2>Bienvenido Cliente</h2>
                    <p class="lead text-muted">Tu perfil está en desarrollo</p>
                    <a href="../index.php" class="btn btn-outline-primary btn-lg mt-3">
                        <i class="fas fa-home me-2"></i>Ir al Menú
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>