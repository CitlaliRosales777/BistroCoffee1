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

// ⭐ LÓGICA EXACTA de cocina.php (48 horas, TOP 20 por estado)
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
    
    // ⭐ MISMOS CONTADORES que cocina.php
    $nuevas = $ordenesPorEstado['ingreso'];
    $proceso = $ordenesPorEstado['elaboracion'];
    $listas = $ordenesPorEstado['terminado'];
    
    // Para la lista de últimas órdenes (combinar todas)
    $ordenes = array_merge($nuevas, $proceso, $listas);
    usort($ordenes, fn($a, $b) => strtotime($b['Fecha']) - strtotime($a['Fecha']));
    
} else {
    $ordenes = $nuevas = $proceso = $listas = [];
}
?>

<!-- EL RESTO DEL HTML SE MANTIENE IGUAL -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($usuario['Nombre']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <!-- HEADER DASHBOARD -->
            <div class="dashboard-header text-center mb-5">
                <h1 style="color: var(--text-primary); font-size: 3rem; margin-bottom: 1rem;">
                    <i class="fas fa-tachometer-alt"></i> Bienvenido, <?= htmlspecialchars($usuario['Nombre']) ?>
                </h1>
                <div class="rol-badge" style="display: inline-block; background: var(--dusty-taupe); color: var(--white-smoke); padding: 0.8rem 2rem; border-radius: 50px; font-weight: 600; font-size: 1.1rem;">
                    <?= $rol ?>
                </div>
            </div>

            <?php if ($rol == 'Administrador'): ?>
                <!-- DASHBOARD ADMINISTRADOR -->
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
                <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <a href="caja.php" class="dashboard-card card">
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
                <!-- DASHBOARD CHEF -->
                <div class="row g-4">
                    <!-- CONTADORES COCINA -->
                    <div class="row g-3 mb-4">
                        <!-- NUEVAS -->
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
                        
                        <!-- PROCESO -->
                        <div class="col-6 col-md-3">
                            <a href="cocina.php#elaboracion" class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden" 
                               style="border-radius: 20px; background: linear-gradient(135deg, #CCE5FF, #A8D5E2);">
                                <div class="p-4 text-center">
                                    <i class="fas fa-hammer fa-spin fa-3x mb-2 text-primary"></i>
                                    <h3 class="mb-1 fw-bold text-primary"><?= count($proceso) ?></h3>
                                    <p class="mb-2 text-primary fw-semibold">Preparación</p>
                                    <div class="badge bg-primary w-100 py-2">En proceso</div>
                                </div>
                            </a>
                        </div>
                        
                        <!-- LISTAS -->
                        <div class="col-6 col-md-3">
                            <a href="cocina.php#terminado" class="card h-100 shadow-lg border-0 text-decoration-none position-relative overflow-hidden" 
                               style="border-radius: 20px; background: linear-gradient(135deg, #CFF4FC, #A0E4F1);">
                                <div class="p-4 text-center">
                                    <i class="fas fa-check-circle fa-3x mb-2 text-info"></i>
                                    <h3 class="mb-1 fw-bold text-info"><?= count($listas) ?></h3>
                                    <p class="mb-2 text-info fw-semibold">Listas</p>
                                    <div class="badge bg-info text-dark w-100 py-2">Para entrega</div>
                                </div>
                            </a>
                        </div>
                        
                        <!-- TOTALES -->
                        <div class="col-6 col-md-3">
                            <div class="card h-100 shadow-xl border-0 position-relative overflow-hidden" 
                                 style="border-radius: 20px; background: linear-gradient(135deg, #FCA17D, #FA4B37); color: white;">
                                <div class="p-4 text-center">
                                    <i class="fas fa-fire fa-3x mb-2 opacity-90"></i>
                                    <h2 class="mb-1 fw-bold"><?= count($ordenes) ?></h2>
                                    <p class="mb-2 fw-semibold opacity-90">Órdenes Total</p>
                                    <a href="cocina.php" class="btn btn-light w-100 py-2 fw-bold">
                                        <i class="fas fa-utensils me-2"></i>Ir a Cocina
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ÚLTIMAS ÓRDENES -->
<div class="col-12">
    <div class="card shadow-xl border-0" style="border-radius: 20px;">
        <div class="card-header bg-success text-white p-3">
            <h6 class="mb-0 fw-bold">
                <i class="fas fa-list me-2"></i>
                Últimas Órdenes (<?= count($ordenes) ?>)
            </h6>
        </div>
        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
            <div class="list-group list-group-flush">
                <?php 
                $ultimas = array_slice($ordenes, 0, 8);
                                function getEstadoClass($estado) {
                    if ($estado === 'ingreso') return 'warning';
                    if ($estado === 'elaboracion') return 'primary';
                    if ($estado === 'terminado') return 'info';
                    return 'success';
                }
                ?>
                
                <?php foreach ($ultimas as $orden): 
                    $productos = json_decode($orden['Productos'], true) ?: [];
                    $estadoClass = getEstadoClass($orden['estado']);
                ?>
                <a href="cocina.php" class="list-group-item list-group-item-action px-4 py-3">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">#<?= $orden['Id_Venta'] ?> - <?= htmlspecialchars($orden['Cajero']) ?></h6>
                        <span class="badge bg-<?= $estadoClass ?>">
                            <?= strtoupper($orden['estado']) ?>
                        </span>
                    </div>
                    <p class="mb-1 small text-muted">
                        <?php 
                        foreach (array_slice($productos, 0, 2) as $p): 
                            echo ($p['cantidad'] ?? 1) . 'x ' . substr($p['nombre'] ?? '', 0, 15) . ' ';
                        endforeach; 
                        if (count($productos) > 2) echo '+ ' . (count($productos)-2) . ' más';
                        ?>
                    </p>
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i><?= date('H:i', strtotime($orden['Fecha'])) ?>
                        | $<span class="fw-bold"><?= number_format($orden['Total'], 0) ?></span>
                    </small>
                </a>
                <?php endforeach; ?>
                
                <?php if (empty($ultimas)): ?>
                <div class="text-center p-5 text-muted">
                    <i class="fas fa-utensils fa-3x mb-3 opacity-50"></i>
                    <p class="fs-5">Sin órdenes recientes</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
                

           
                
                <?php if (empty($ultimas)): ?>
                <div class="text-center p-5 text-muted">
                    <i class="fas fa-utensils fa-3x mb-3 opacity-50"></i>
                    <p class="fs-5">Sin órdenes recientes</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
                </div>

            <?php else: ?>
                <!-- DASHBOARD CLIENTE -->
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

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
    .dashboard-card h3 {
        color: var(--text-primary);
        margin-bottom: 1rem;
        font-size: 1.4rem;
    }
    .card { transition: all 0.3s ease; }
    .card:hover { transform: translateY(-5px); }
    .badge { font-weight: 600; }
    </style>
</body>
</html>