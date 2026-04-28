<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Chef', 'Administrador']);

// ⭐ QUERY por ESTADO
$estados = ['ingreso', 'elaboracion', 'terminado', 'entregado'];
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
        :root { --primary: #F4A261; --success: #2A9D8F; --warning: #E9C46A; --info: #0dcaf0; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        
        .seccion-cocina { 
            min-height: 400px; border-radius: 20px; position: relative; 
            transition: all 0.3s; cursor: pointer;
        }
        .seccion-ingreso { background: linear-gradient(135deg, #FFF3CD, #FFEAA7); border: 3px solid #ffc107; }
        .seccion-elaboracion { background: linear-gradient(135deg, #CCE5FF, #A8D5E2); border: 3px solid #0d6efd; }
        .seccion-terminado { background: linear-gradient(135deg, #CFF4FC, #A0E4F1); border: 3px solid #0dcaf0; }
        .seccion-entregado { background: linear-gradient(135deg, #D4EDDA, #A8D5BA); border: 3px solid #198754; }
        
        .orden-card { 
            background: white; border-radius: 12px; margin-bottom: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.3s; cursor: move;
            border-left: 4px solid transparent;
        }
        .orden-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .orden-card.dragging { opacity: 0.5; transform: rotate(5deg); }
        
        .btn-estado { border-radius: 25px; font-size: 0.8rem; padding: 6px 12px; margin: 1px; min-width: 90px; }
        .btn-estado.active { box-shadow: 0 0 0 3px rgba(255,255,255,0.5); transform: scale(1.05); }
        
        .drag-over { transform: scale(1.02) !important; box-shadow: 0 10px 30px rgba(0,123,255,0.3) !important; }
        
        @media (max-width: 768px) { .seccion-cocina { margin-bottom: 20px; } }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- HEADER -->
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h1 class="text-white mb-1">
                    <i class="fas fa-utensils fa-2x me-3"></i>
                    Cocina Live
                </h1>
                <small class="text-white-50">
                    <?= $nuevas + $proceso + $listas ?> órdenes activas | <?= date('H:i:s') ?>
                </small>
            </div>
            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <div class="btn-group" role="group">
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-home"></i>
                    </a>
                    <button class="btn btn-outline-light btn-sm" onclick="location.reload()">
                        <i class="fas fa-refresh"></i>
                    </button>
                    <a href="../logout.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- SECCIONES COCINA -->
        <div class="row g-4">
            <!-- EN ESPERA -->
            <div class="col-xl-3 col-lg-6">
                <div class="seccion-cocina seccion-ingreso p-4 h-100" 
                     data-estado="ingreso" onclick="setSeccionActiva(this)">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-clock me-2 text-warning"></i>
                            En Espera
                        </h5>
                        <div class="badge fs-6 bg-warning text-dark"><?= $nuevas ?></div>
                    </div>
                    <div id="drop-ingreso" class="drop-zone h-100">
                        <?php foreach ($ordenesPorEstado['ingreso'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- EN PREPARACIÓN -->
            <div class="col-xl-3 col-lg-6">
                <div class="seccion-cocina seccion-elaboracion p-4 h-100" 
                     data-estado="elaboracion" onclick="setSeccionActiva(this)">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-hammer me-2"></i>
                            Preparación
                        </h5>
                        <div class="badge fs-6 bg-primary"><?= $proceso ?></div>
                    </div>
                    <div id="drop-elaboracion" class="drop-zone h-100">
                        <?php foreach ($ordenesPorEstado['elaboracion'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- LISTAS -->
            <div class="col-xl-3 col-lg-6">
                <div class="seccion-cocina seccion-terminado p-4 h-100" 
                     data-estado="terminado" onclick="setSeccionActiva(this)">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold text-info">
                            <i class="fas fa-check-circle me-2"></i>
                            Listas
                        </h5>
                        <div class="badge fs-6 bg-info text-dark"><?= $listas ?></div>
                    </div>
                    <div id="drop-terminado" class="drop-zone h-100">
                        <?php foreach ($ordenesPorEstado['terminado'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ENTREGADAS -->
            <div class="col-xl-3 col-lg-6">
                <div class="seccion-cocina seccion-entregado p-4 h-100" 
                     data-estado="entregado" onclick="setSeccionActiva(this)">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold text-success">
                            <i class="fas fa-truck me-2"></i>
                            Entregadas
                        </h5>
                        <div class="badge fs-6 bg-success"><?= $entregadas ?></div>
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
    function renderOrdenCard($orden) {
        $productos = json_decode($orden['Productos'], true) ?: [];
        $estadoClass = $orden['estado'];
    ?>
    <div class="orden-card estado-<?= $estadoClass ?>" data-id="<?= $orden['Id_Venta'] ?>" draggable="true">
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-1 fw-bold">#<?= $orden['Id_Venta'] ?></h6>
                <span class="badge estado-badge estado-<?= $estadoClass ?>">
                    <?= strtoupper($orden['estado']) ?>
                </span>
            </div>
            <div class="mb-2">
                <?php foreach (array_slice($productos, 0, 3) as $p): ?>
                    <div class="small mb-1">
                        <i class="fas fa-circle text-muted me-1" style="font-size: 0.4rem;"></i>
                        <?= $p['cantidad'] ?? 1 ?>x <?= htmlspecialchars(substr($p['nombre'], 0, 25)) ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($productos) > 3): ?>
                    <small class="text-muted">+<?= count($productos)-3 ?> más</small>
                <?php endif; ?>
            </div>
            <div class="d-flex justify-content-between">
                <small class="text-muted"><?= date('H:i', strtotime($orden['Fecha'])) ?></small>
                <strong class="text-primary">$<?= number_format($orden['Total'], 0) ?></strong>
            </div>
        </div>
    </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let seccionActiva = null;
    const estados = ['ingreso', 'elaboracion', 'terminado', 'entregado'];

    // ⭐ DRAG & DROP
    document.querySelectorAll('.orden-card').forEach(card => {
        card.addEventListener('dragstart', e => {
            e.dataTransfer.setData('text/plain', card.dataset.id);
            card.classList.add('dragging');
        });
        
        card.addEventListener('dragend', e => {
            card.classList.remove('dragging');
        });
    });

    // Drop zones
    document.querySelectorAll('.drop-zone').forEach(zone => {
        ['dragover', 'dragenter'].forEach(event => {
            zone.addEventListener(event, e => {
                e.preventDefault();
                e.currentTarget.closest('.seccion-cocina').classList.add('drag-over');
            });
        });
        
        ['dragleave', 'dragexit'].forEach(event => {
            zone.addEventListener(event, e => {
                e.currentTarget.closest('.seccion-cocina').classList.remove('drag-over');
            });
        });
        
        zone.addEventListener('drop', e => {
            e.preventDefault();
            const ordenId = e.dataTransfer.getData('text/plain');
            const nuevoEstado = e.currentTarget.closest('.seccion-cocina').dataset.estado;
            
            cambiarEstado(ordenId, nuevoEstado);
            e.currentTarget.closest('.seccion-cocina').classList.remove('drag-over');
        });
    });

    // ⭐ CLICK CAMBIAR ESTADO
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

    // ⭐ SELECCIÓN SECCIÓN
    function setSeccionActiva(seccion) {
        document.querySelectorAll('.seccion-cocina').forEach(s => s.style.boxShadow = '0 10px 30px rgba(0,0,0,0.2)');
        seccion.style.boxShadow = '0 15px 50px rgba(255,193,7,0.4)';
        seccionActiva = seccion.dataset.estado;
    }

    // ⭐ AUTO-REFRESH 30s
    setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>