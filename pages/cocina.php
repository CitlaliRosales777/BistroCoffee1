<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Chef', 'Administrador']);

// Queries
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

// Contadores
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
    <title>Cocina Live - <?= htmlspecialchars($usuario['Nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --cafe-claro: #f5e8d3;
            --cafe: #e8d5b8;
            --cafe-medio: #d4b89e;
            --terracota: #c19a6b;
            --cafe-oscuro: #8c5f2e;
            --verde-tierra: #8a9a7b;
        }

        body {
            background: #3c2f2f; /* Fondo café oscuro elegante */
            color: #3c2f2f;
            min-height: 100vh;
        }

        .seccion-cocina {
            border-radius: 20px;
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Colores Terrosos Sólidos */
        .seccion-ingreso {
            background: #f5e8d3;
            border: 3px solid #c19a6b;
        }
        .seccion-elaboracion {
            background: #e8d5b8;
            border: 3px solid #8c5f2e;
        }
        .seccion-terminado {
            background: #d4b89e;
            border: 3px solid #6b4e2f;
        }
        .seccion-entregado {
            background: #b8c9a8;
            border: 3px solid #5a6b4a;
        }

        /* Canceladas - Rojo terracota */
        .seccion-cancelado {
            background: #d9a8a0 !important;
            border: 3px solid #9b3a2f !important;
            min-height: 160px;
        }

        .orden-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: move;
            border-left: 5px solid #8c5f2e;
        }
        .orden-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.18);
        }
        .orden-card.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }

        .canceladas-bar {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 8px 0;
            scrollbar-width: thin;
            scrollbar-color: #9b3a2f transparent;
        }
        .canceladas-bar::-webkit-scrollbar {
            height: 6px;
        }
        .canceladas-bar::-webkit-scrollbar-thumb {
            background: #9b3a2f;
            border-radius: 10px;
        }

        .orden-card-sm {
            flex: 0 0 200px;
            font-size: 0.9rem;
        }

        .drag-over {
            border-color: #c19a6b !important;
            box-shadow: 0 0 0 4px rgba(193, 154, 107, 0.4) !important;
        }

        h5, h4, h1 { color: #3c2f2f; }
        .text-white-50 { color: #d4b89e !important; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- HEADER -->
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h1 class="mb-1" style="color: #e8d5b8;">
                    <i class="fas fa-utensils fa-2x me-3"></i>
                    Cocina Live
                </h1>
                <small style="color: #d4b89e;">
                    <?= $nuevas + $proceso + $listas + $entregadas ?> órdenes activas | 
                    <?= $canceladas ?> canceladas | <?= date('H:i:s') ?>
                </small>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="btn-group" role="group">
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm"><i class="fas fa-home"></i></a>
                    <button class="btn btn-outline-light btn-sm" onclick="location.reload()"><i class="fas fa-refresh"></i></button>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>

        <!-- CANCELADAS COMPACTA -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="seccion-cocina seccion-cancelado p-3" 
                     data-estado="cancelado" onclick="setSeccionActiva(this)">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold" style="color: #9b3a2f;">
                            <i class="fas fa-times-circle me-2"></i>
                            Canceladas Recientes
                        </h5>
                        <div class="badge bg-danger fs-5 px-3"><?= $canceladas ?></div>
                    </div>

                    <div id="drop-cancelado" class="drop-zone canceladas-bar">
                        <?php foreach ($ordenesPorEstado['cancelado'] as $orden): ?>
                            <?= renderOrdenCard($orden, true) ?>
                        <?php endforeach; ?>

                        <?php if (empty($ordenesPorEstado['cancelado'])): ?>
                            <div class="text-muted d-flex align-items-center justify-content-center w-100" style="height: 80px; color:#8c5f2e;">
                                Sin cancelaciones recientes
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- TIMELINE PRINCIPAL -->
        <div class="row g-4">
            <!-- En Espera -->
            <div class="col-xl-3 col-lg-6">
                <div class="seccion-cocina seccion-ingreso p-4 h-100" data-estado="ingreso" onclick="setSeccionActiva(this)">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-clock me-2" style="color:#c19a6b"></i>En Espera</h5>
                        <div class="badge fs-6" style="background:#c19a6b; color:white;"><?= $nuevas ?></div>
                    </div>
                    <div id="drop-ingreso" class="drop-zone h-100">
                        <?php foreach ($ordenesPorEstado['ingreso'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Preparación -->
            <div class="col-xl-3 col-lg-6">
                <div class="seccion-cocina seccion-elaboracion p-4 h-100" data-estado="elaboracion" onclick="setSeccionActiva(this)">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-hammer me-2" style="color:#8c5f2e"></i>Preparación</h5>
                        <div class="badge fs-6" style="background:#8c5f2e; color:white;"><?= $proceso ?></div>
                    </div>
                    <div id="drop-elaboracion" class="drop-zone h-100">
                        <?php foreach ($ordenesPorEstado['elaboracion'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Listas -->
            <div class="col-xl-3 col-lg-6">
                <div class="seccion-cocina seccion-terminado p-4 h-100" data-estado="terminado" onclick="setSeccionActiva(this)">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-check-circle me-2" style="color:#6b4e2f"></i>Listas</h5>
                        <div class="badge fs-6" style="background:#6b4e2f; color:white;"><?= $listas ?></div>
                    </div>
                    <div id="drop-terminado" class="drop-zone h-100">
                        <?php foreach ($ordenesPorEstado['terminado'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Entregadas -->
            <div class="col-xl-3 col-lg-6">
                <div class="seccion-cocina seccion-entregado p-4 h-100" data-estado="entregado" onclick="setSeccionActiva(this)">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-truck me-2" style="color:#5a6b4a"></i>Entregadas</h5>
                        <div class="badge fs-6" style="background:#5a6b4a; color:white;"><?= $entregadas ?></div>
                    </div>
                    <div id="drop-entregado" class="drop-zone h-100">
                        <?php foreach ($ordenesPorEstado['entregado'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
    function renderOrdenCard($orden, $esCancelada = false) {
        $productos = json_decode($orden['Productos'], true) ?: [];
    ?>
    <div class="orden-card <?= $esCancelada ? 'orden-card-sm' : '' ?>" 
         data-id="<?= $orden['Id_Venta'] ?>" draggable="true">
        <div class="p-<?= $esCancelada ? '2' : '3' ?>">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <h6 class="mb-0 fw-bold">#<?= $orden['Id_Venta'] ?></h6>
                <?php if ($esCancelada): ?>
                    <span class="badge bg-danger">CANCELADA</span>
                <?php else: ?>
                    <span class="badge" style="background:#8c5f2e; color:white;"><?= strtoupper($orden['estado']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="mb-2">
                <?php if ($esCancelada): ?>
                    <small class="text-danger fw-bold">
                        <?= htmlspecialchars(substr($productos[0]['nombre'] ?? 'Orden', 0, 32)) ?>
                    </small>
                <?php else: ?>
                    <?php foreach (array_slice($productos, 0, 2) as $p): ?>
                        <div class="small"><?= $p['cantidad'] ?? 1 ?>x <?= htmlspecialchars(substr($p['nombre'], 0, 22)) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between">
                <small class="text-muted"><?= date('H:i', strtotime($orden['Fecha'])) ?></small>
                <strong class="<?= $esCancelada ? 'text-danger' : 'text-cafe-oscuro' ?>">
                    $<?= number_format($orden['Total'], 0) ?>
                </strong>
            </div>
        </div>
    </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.orden-card').forEach(card => {
        card.addEventListener('dragstart', e => {
            e.dataTransfer.setData('text/plain', card.dataset.id);
            card.classList.add('dragging');
        });
        card.addEventListener('dragend', () => card.classList.remove('dragging'));
    });

    document.querySelectorAll('.drop-zone').forEach(zone => {
        zone.addEventListener('dragover', e => e.preventDefault());
        zone.addEventListener('drop', e => {
            e.preventDefault();
            const ordenId = e.dataTransfer.getData('text/plain');
            const seccion = e.currentTarget.closest('.seccion-cocina');
            if (seccion) cambiarEstado(ordenId, seccion.dataset.estado);
        });
    });

    function cambiarEstado(idVenta, nuevoEstado) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cocina-update-simple.php';
        form.style.display = 'none';
        form.innerHTML = `
            <input type="hidden" name="id_venta" value="${idVenta}">
            <input type="hidden" name="estado" value="${nuevoEstado}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>