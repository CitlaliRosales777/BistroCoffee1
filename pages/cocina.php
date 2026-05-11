<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Chef', 'Administrador']);

// ⭐ QUERY por ESTADO - AGREGADO 'cancelado'
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
        /* ⭐ PALETA DE COLORES DEL PROYECTO */
        :root { 
            --black: #0a0908ff;
            --jet-black: #22333bff;
            --white-smoke: #f2f4f3ff;
            --dusty-taupe: #a9927dff;
            --stone-brown: #5e503fff;
            --logo-cream: #F0EBE3;
            --logo-gray: #8C8C8C;
            --text-primary: var(--stone-brown);
            --text-secondary: #4a4035;
            --text-light: #8c7d6f;
            --bg-card: var(--white-smoke);
            --bg-section-light: #f8f7f5;
            --shadow-light: rgba(94, 80, 63, 0.08);
            --shadow-medium: rgba(94, 80, 63, 0.15);
            --shadow-heavy: rgba(10, 9, 8, 0.25);
            --status-disponible-bg: #e6f0e0;
            --status-disponible-text: #2f4a2a;
            --status-ocupado-bg: #f0e4e4;
            --status-ocupado-text: #5c2a2a;
            
            /* Colores semánticos personalizados para cocina */
            --bg-ingreso: linear-gradient(135deg, #fdf8ed, #f8f1dc);
            --border-ingreso: #a9927d;
            --bg-elaboracion: linear-gradient(135deg, #f2f4f3, #e8ebe7);
            --border-elaboracion: #5e503f;
            --bg-terminado: linear-gradient(135deg, #f8f7f5, #f0ede8);
            --border-terminado: #8c7d6f;
            --bg-entregado: linear-gradient(135deg, #f2f4f3, #e8ebe7);
            --border-entregado: #a9927d;
            --bg-cancelado: linear-gradient(135deg, #f8f7f5, #f0ede8);
            --border-cancelado: #8c8c8c;
        }

        /* ⭐ FONDO PRINCIPAL CON LA PALETA */
        body { 
            background: linear-gradient(135deg, var(--jet-black) 0%, var(--stone-brown) 50%, var(--dusty-taupe) 100%);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .seccion-cocina { 
            display: flex; flex-direction: column;
            min-height: 450px; border-radius: 20px; position: relative; 
            transition: all 0.3s; cursor: pointer; 
            background: var(--bg-card);
            border: 3px solid transparent;
            box-shadow: 0 10px 30px var(--shadow-medium);
        }
        
        /* ⭐ CONTENEDOR DE CARDS CENTRADO */
        .drop-zone {
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            justify-content: center;
            align-items: center;
            padding: 10px; 
            overflow-y: auto;
            gap: 12px;
        }
        
        /* ⭐ SECCIONES CON PALETA PERSONALIZADA */
        .seccion-ingreso { 
            background: var(--bg-ingreso); 
            border-color: var(--border-ingreso); 
            color: var(--text-primary);
        }
        .seccion-elaboracion { 
            background: var(--bg-elaboracion); 
            border-color: var(--border-elaboracion); 
            color: var(--text-primary);
        }
        .seccion-terminado { 
            background: var(--bg-terminado); 
            border-color: var(--border-terminado); 
            color: var(--text-primary);
        }
        .seccion-entregado { 
            background: var(--bg-entregado); 
            border-color: var(--border-entregado); 
            color: var(--text-secondary);
        }
        .seccion-cancelado { 
            background: var(--bg-cancelado); 
            border-color: var(--border-cancelado); 
            color: var(--text-light);
        }
        
        .orden-card { 
            background: var(--logo-cream); 
            border-radius: 12px; 
            box-shadow: 0 4px 15px var(--shadow-light); 
            transition: all 0.3s; 
            cursor: pointer;
            border-left: 4px solid var(--dusty-taupe);
            width: 100%; max-width: 220px;
            height: 120px; 
            display: flex; flex-direction: column;
            justify-content: center; align-items: center; text-align: center; 
            padding: 15px; position: relative;
            color: var(--text-primary);
        }
        .orden-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px var(--shadow-medium); 
            border-left-color: var(--stone-brown);
        }
        .orden-card.dragging { opacity: 0.5; transform: rotate(5deg); }
        .orden-card:focus { outline: 3px solid var(--dusty-taupe); outline-offset: 2px; }
        
        /* CONTENIDO CENTRADO */
        .orden-card h6 { 
            margin-bottom: 8px !important; 
            font-size: 1.1rem; 
            color: var(--text-primary);
            font-weight: 600;
        }
        .orden-card .productos-list { 
            min-height: 40px; display: flex; flex-direction: column; justify-content: center; 
            margin: 8px 0; font-size: 0.85rem; line-height: 1.3; width: 100%;
            color: var(--text-secondary);
        }
        .orden-card .productos-list .producto-item {
            margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .orden-card .info-footer {
            margin-top: auto; padding-top: 8px; border-top: 1px solid var(--bg-section-light); width: 100%;
            display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem;
            color: var(--text-light);
        }
        .orden-card .badge.estado-badge {
            position: absolute; top: 8px; right: 8px; font-size: 0.7rem; padding: 4px 8px;
            background: var(--dusty-taupe); color: var(--logo-cream);
            font-weight: 600;
        }
        
        .header-seccion {
            padding: 20px 20px 10px 20px;
            border-radius: 20px 20px 0 0;
            background: rgba(242, 244, 243, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .header-seccion h5 {
            color: var(--text-primary) !important;
            font-weight: 700;
        }
        
        .btn-estado { 
            border-radius: 25px; 
            font-size: 0.8rem; 
            padding: 6px 12px; 
            margin: 1px; 
            min-width: 90px;
            background: var(--dusty-taupe);
            border-color: var(--stone-brown);
            color: var(--logo-cream);
        }
        .btn-estado.active { 
            box-shadow: 0 0 0 3px rgba(169, 146, 125, 0.3); 
            transform: scale(1.05);
            background: var(--stone-brown);
        }
        
        .drag-over { 
            transform: scale(1.02) !important; 
            box-shadow: 0 15px 40px var(--shadow-heavy) !important; 
            border-color: var(--stone-brown) !important;
        }
        
        /* HEADER */
        .text-white { color: var(--logo-cream) !important; }
        .text-white-50 { color: rgba(240, 235, 227, 0.7) !important; }
        
        /* BADGES PERSONALIZADOS */
        .badge {
            background: var(--dusty-taupe) !important;
            color: var(--logo-cream) !important;
            font-weight: 600;
        }
        
        /* Scrollbar personalizada */
        .drop-zone::-webkit-scrollbar {
            width: 6px;
        }
        .drop-zone::-webkit-scrollbar-track {
            background: transparent;
        }
        .drop-zone::-webkit-scrollbar-thumb {
            background: var(--dusty-taupe);
            border-radius: 3px;
        }
        
        /* BOTONES HEADER */
        .btn-outline-light {
            border-color: var(--logo-cream) !important;
            color: var(--logo-cream) !important;
        }
        .btn-outline-light:hover {
            background: var(--logo-cream) !important;
            color: var(--stone-brown) !important;
        }
        .btn-warning {
            background: var(--dusty-taupe) !important;
            border-color: var(--dusty-taupe) !important;
            color: var(--logo-cream) !important;
        }
        
        @media (max-width: 768px) { 
            .seccion-cocina { margin-bottom: 20px; } 
            .orden-card { height: 110px; padding: 12px; max-width: 200px; }
        }
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
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-ingreso h-100" data-estado="ingreso" onclick="setSeccionActiva(this)">
                    <div class="header-seccion">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-clock me-2" style="color: var(--dusty-taupe);"></i>
                                En Espera
                            </h5>
                            <div class="badge fs-6"><?= $nuevas ?></div>
                        </div>
                    </div>
                    <div id="drop-ingreso" class="drop-zone" tabindex="-1">
                        <?php foreach ($ordenesPorEstado['ingreso'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- EN PREPARACIÓN -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-elaboracion h-100" data-estado="elaboracion" onclick="setSeccionActiva(this)">
                    <div class="header-seccion">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-hammer me-2" style="color: var(--stone-brown);"></i>
                                Preparación
                            </h5>
                            <div class="badge fs-6"><?= $proceso ?></div>
                        </div>
                    </div>
                    <div id="drop-elaboracion" class="drop-zone" tabindex="-1">
                        <?php foreach ($ordenesPorEstado['elaboracion'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- LISTAS -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-terminado h-100" data-estado="terminado" onclick="setSeccionActiva(this)">
                    <div class="header-seccion">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-check-circle me-2" style="color: var(--text-light);"></i>
                                Listas
                            </h5>
                            <div class="badge fs-6"><?= $listas ?></div>
                        </div>
                    </div>
                    <div id="drop-terminado" class="drop-zone" tabindex="-1">
                        <?php foreach ($ordenesPorEstado['terminado'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ENTREGADAS -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-entregado h-100" data-estado="entregado" onclick="setSeccionActiva(this)">
                    <div class="header-seccion">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-truck me-2" style="color: var(--dusty-taupe);"></i>
                                Entregadas
                            </h5>
                            <div class="badge fs-6"><?= $entregadas ?></div>
                        </div>
                    </div>
                    <div id="drop-entregado" class="drop-zone" tabindex="-1">
                        <?php foreach ($ordenesPorEstado['entregado'] as $orden): ?>
                            <?= renderOrdenCard($orden) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- CANCELADAS -->
            <div class="col-xl-2 col-lg-3 col-md-6">
                <div class="seccion-cocina seccion-cancelado h-100" data-estado="cancelado" onclick="setSeccionActiva(this)">
                    <div class="header-seccion">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-times-circle me-2" style="color: var(--logo-gray);"></i>
                                Canceladas
                            </h5>
                            <div class="badge fs-6"><?= $canceladas ?></div>
                        </div>
                    </div>
                    <div id="drop-cancelado" class="drop-zone" tabindex="-1">
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
        $estadoClass = $orden['estado'];
    ?>
    <div class="orden-card estado-<?= $estadoClass ?>" 
         data-id="<?= $orden['Id_Venta'] ?>" 
         draggable="true"
         tabindex="0"
         role="button"
         aria-label="Orden #<?= $orden['Id_Venta'] ?>">
        <span class="badge estado-badge estado-<?= $estadoClass ?>">
            <?= strtoupper(substr($orden['estado'], 0, 3)) ?>
        </span>
        <h6>#<?= $orden['Id_Venta'] ?></h6>
        <div class="productos-list">
            <?php foreach (array_slice($productos, 0, 2) as $p): ?>
                <div class="producto-item">
                    <i class="fas fa-circle" style="font-size: 0.5rem; color: var(--dusty-taupe);"></i>
                    <?= $p['cantidad'] ?? 1 ?>x <?= htmlspecialchars(substr($p['nombre'], 0, 20)) ?>
                </div>
            <?php endforeach; ?>
            <?php if (count($productos) > 2): ?>
                <div class="producto-item" style="color: var(--logo-gray);">+<?= count($productos)-2 ?> más</div>
            <?php endif; ?>
        </div>
        <div class="info-footer">
            <span><?= date('H:i', strtotime($orden['Fecha'])) ?></span>
            <strong>$<?= number_format($orden['Total'], 0) ?></strong>
        </div>
    </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let seccionActiva = null;
    const estados = ['ingreso', 'elaboracion', 'terminado', 'entregado', 'cancelado'];
    let columnaActual = 0;

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

    // ⭐ NAVEGACIÓN CON ENTER Y CLICK
    document.querySelectorAll('.orden-card').forEach((card, index) => {
        card.addEventListener('click', function(e) {
            e.stopPropagation();
            const nuevoEstado = document.querySelector(`[data-estado="${estados[columnaActual]}"]`).dataset.estado;
            cambiarEstado(this.dataset.id, nuevoEstado);
        });

        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const nuevoEstado = document.querySelector(`[data-estado="${estados[columnaActual]}"]`).dataset.estado;
                cambiarEstado(this.dataset.id, nuevoEstado);
            }
        });
    });

    // ⭐ NAVEGACIÓN ENTRE COLUMNAS
    document.addEventListener('keydown', function(e) {
        const secciones = document.querySelectorAll('.seccion-cocina');
        
        if (e.key === 'ArrowRight' && columnaActual < estados.length - 1) {
            columnaActual++;
            setSeccionActiva(secciones[columnaActual]);
            e.preventDefault();
        } else if (e.key === 'ArrowLeft' && columnaActual > 0) {
            columnaActual--;
            setSeccionActiva(secciones[columnaActual]);
            e.preventDefault();
        }
    });

    // Drop zones
    document.querySelectorAll('.drop-zone').forEach((zone, index) => {
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

        if (index === 0) {
            setSeccionActiva(zone.closest('.seccion-cocina'));
        }
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

    function setSeccionActiva(seccion) {
        document.querySelectorAll('.seccion-cocina').forEach(s => {
            s.style.boxShadow = '0 10px 30px var(--shadow-medium)';
        });
        seccion.style.boxShadow = '0 15px 50px rgba(169, 146, 125, 0.4)';
        seccionActiva = seccion.dataset.estado;
        columnaActual = estados.indexOf(seccion.dataset.estado);
    }

    setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>