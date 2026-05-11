<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Chef', 'Administrador']);

// ⭐ QUERY por ESTADO
$estados = ['ingreso', 'elaboracion', 'terminado', 'entregado', 'cancelado'];
$ordenesPorEstado = [];

foreach ($estados as $estado) {
    $sql = "
        SELECT TOP 20 Id_Venta, Cajero, Total, Moneda, 
               CAST(Productos AS NVARCHAR(MAX)) as Productos, 
               Fecha,
               ISNULL(Estado_Cocina, 'ingreso') as estado
        FROM Ventas_Caja 
        WHERE (ISNULL(Estado_Cocina, 'ingreso') = ? OR ? = 'todas')
        AND Fecha > DATEADD(HOUR, -48, GETDATE())
        ORDER BY 
            CASE 
                WHEN ISNULL(Estado_Cocina, 'ingreso') = ? THEN 0
                ELSE 1 
            END, Fecha DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$estado, $estado, $estado]);
    $ordenesPorEstado[$estado] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$nuevas = count($ordenesPorEstado['ingreso']);
$proceso = count($ordenesPorEstado['elaboracion']);
$listas = count($ordenesPorEstado['terminado']);
$entregadas = count($ordenesPorEstado['entregado']);
$canceladas = count($ordenesPorEstado['cancelado']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cocina Live - Bistro & Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --cafe-dark: #3C2F2A;
            --cafe-brown: #6B5A4A;
            --cafe-taupe: #A9927D;
            --cream: #F8F4ED;
            --beige-light: #FAF7F2;
            --beige-lighter: #FFFBF5;
            --text-dark: #3C2F2A;
        }

        body {
            background: linear-gradient(to bottom, #FFFBF5, #F8F4ED);
            color: var(--text-dark);
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
        }

        /* HEADER estilo Bistro & Coffee */
        .header-bistro {
            background: var(--cafe-dark);
            padding: 1rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header-bistro .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }
        .header-bistro .logo img {
            width: 52px;
            height: 52px;
            border-radius: 50%;
        }

        .main-title {
            color: var(--cafe-brown);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .seccion-cocina {
            border-radius: 22px;
            overflow: hidden;
            background: white;
            box-shadow: 0 8px 25px rgba(107, 90, 74, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #f0e9df;
            height: 100%;
        }

        .seccion-cocina:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(107, 90, 74, 0.12);
        }

        .header-seccion {
            background: linear-gradient(135deg, #D4BFA8, #B89E7E);
            color: white;
            padding: 18px 20px;
            font-weight: 600;
        }

        .drop-zone {
            background: var(--beige-lighter);
            min-height: 420px;
            padding: 20px;
            overflow-y: auto;
            gap: 14px;
        }

        .orden-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-left: 5px solid var(--cafe-taupe);
            padding: 16px;
            transition: all 0.3s ease;
            position: relative;
        }

        .orden-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(169, 146, 125, 0.18);
            border-left-color: var(--cafe-brown);
        }

        .orden-card .badge-estado {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 0.78rem;
            padding: 4px 9px;
            border-radius: 20px;
            background: rgba(107, 90, 74, 0.9);
            color: white;
            font-weight: 600;
        }

        .seccion-ingreso .orden-card { border-left-color: #C9A97E; }
        .seccion-elaboracion .orden-card { border-left-color: #A17E5F; }
        .seccion-terminado .orden-card { border-left-color: #6B5A4A; }
        .seccion-entregado .orden-card { border-left-color: #B89E7E; }
        .seccion-cancelado .orden-card { 
            border-left-color: #9E9E9E; 
            opacity: 0.92; 
        }

        .info-footer {
            font-size: 0.9rem;
            color: #6b5a4a;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #f0e9df;
        }

        .badge {
            background: var(--cafe-taupe);
            color: white;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="header-bistro">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <a href="#" class="logo">
                        <img src="../assets/images/logo.png" alt="Bistro Coffee" style="width: 60px; height: 60px; border-radius: 50%;">
                        <div>
                            <strong style="font-size:1.4rem;">Bistro & Coffee</strong><br>
                            <small style="opacity:0.9;">Cocina Live</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-white me-3">
                        <i class="fas fa-user-circle"></i> 
                        <?= htmlspecialchars($usuario['Nombre']) ?>
                    </span>
                    
                    <!-- Nuevo botón Dashboard -->
                    <a href="dashboard.php" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    
                    <button onclick="location.reload()" class="btn btn-outline-light btn-sm me-2">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    
                    <a href="../logout.php" class="btn btn-light btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid py-4 px-4">
        <h1 class="main-title mb-1 text-center display-6">
            <i class="fas fa-utensils me-3"></i>Órdenes en Cocina
        </h1>
        <p class="text-center text-muted mb-4 fs-5">
            <?= $nuevas + $proceso + $listas ?> órdenes activas • <?= date('H:i') ?>
        </p>

        <div class="row g-4">
            <!-- En Espera -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-ingreso" data-estado="ingreso">
                    <div class="header-seccion text-center">
                        <h5><i class="fas fa-clock"></i> En Espera</h5>
                        <span class="badge fs-5 px-3"><?= $nuevas ?></span>
                    </div>
                    <div class="drop-zone" id="drop-ingreso">
                        <?php foreach ($ordenesPorEstado['ingreso'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Preparación -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-elaboracion" data-estado="elaboracion">
                    <div class="header-seccion text-center">
                        <h5><i class="fas fa-fire-flame-curved"></i> Preparación</h5>
                        <span class="badge fs-5 px-3"><?= $proceso ?></span>
                    </div>
                    <div class="drop-zone" id="drop-elaboracion">
                        <?php foreach ($ordenesPorEstado['elaboracion'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Listas -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-terminado" data-estado="terminado">
                    <div class="header-seccion text-center">
                        <h5><i class="fas fa-check-circle"></i> Listas</h5>
                        <span class="badge fs-5 px-3"><?= $listas ?></span>
                    </div>
                    <div class="drop-zone" id="drop-terminado">
                        <?php foreach ($ordenesPorEstado['terminado'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Entregadas -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-entregado" data-estado="entregado">
                    <div class="header-seccion text-center">
                        <h5><i class="fas fa-truck"></i> Entregadas</h5>
                        <span class="badge fs-5 px-3"><?= $entregadas ?></span>
                    </div>
                    <div class="drop-zone" id="drop-entregado">
                        <?php foreach ($ordenesPorEstado['entregado'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Canceladas -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-cancelado" data-estado="cancelado">
                    <div class="header-seccion text-center" style="background: linear-gradient(135deg, #B8B0A8, #9E9589);">
                        <h5><i class="fas fa-times-circle"></i> Canceladas</h5>
                        <span class="badge fs-5 px-3"><?= $canceladas ?></span>
                    </div>
                    <div class="drop-zone" id="drop-cancelado">
                        <?php foreach ($ordenesPorEstado['cancelado'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
    function renderOrdenCard($orden) {
        $productos = json_decode($orden['Productos'], true) ?: [];
    ?>
    <div class="orden-card" draggable="true" data-id="<?= $orden['Id_Venta'] ?>">
        <span class="badge badge-estado">#<?= $orden['Id_Venta'] ?></span>
        
        <h6 class="fw-bold mb-2 mt-4">#<?= $orden['Id_Venta'] ?></h6>
        
        <div style="font-size:0.92rem; line-height:1.45; min-height:75px; color: #4a3f35;">
            <?php foreach (array_slice($productos, 0, 3) as $p): ?>
                <div class="mb-1">• <?= ($p['cantidad'] ?? 1) ?>× <?= htmlspecialchars($p['nombre']) ?></div>
            <?php endforeach; ?>
        </div>

        <div class="info-footer d-flex justify-content-between align-items-center">
            <small><?= date('H:i', strtotime($orden['Fecha'])) ?></small>
            <strong style="color: var(--cafe-brown); font-size:1.05rem;">
                $<?= number_format($orden['Total'], 0) ?>
            </strong>
        </div>
    </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>