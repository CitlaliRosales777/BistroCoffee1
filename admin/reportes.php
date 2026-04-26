<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Administrador']);

// INICIALIZAR VARIABLES
$total_productos = 0;
$total_ventas = 0;
$total_ingresos = 0;
$productos = [];
$mensaje = '';

// Obtener datos
try {
    $productos = db_fetch_all($conn, "SELECT * FROM Productos ORDER BY Nombre ASC");
    $total_productos = count($productos);
    
    $ventas_result = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Ventas");
    $total_ventas = $ventas_result['total'] ?? 0;
    
    $ingresos_result = db_fetch_one($conn, "
        SELECT ISNULL(SUM(Total), 0) as total 
        FROM Ventas WHERE estado = 'Completada'
    ");
    $total_ingresos = $ingresos_result['total'] ?? 0;
    
} catch (Exception $e) {
    $mensaje = "Error al cargar datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --dusty-taupe: #A9927D; --stone-brown: #8D7B68; --text-primary: #2C1810; }
        .hover-shadow:hover { transform: translateY(-5px); transition: all 0.3s; }
        .spinner-border { width: 3rem; height: 3rem; }
        .card-ganancias { background: linear-gradient(135deg, #f8f9fa, #e9ecef); }
    </style>
</head>

<body style="background: var(--bg-section-light);">
    <?php include 'index.php'; ?>

    <main class="admin-main" style="margin-left: 280px; padding: 2rem; min-height: 100vh;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
            <h1 style="color: var(--text-primary); font-size: 2.2rem; margin: 0;">
                <i class="fas fa-chart-bar" style="color: var(--dusty-taupe); margin-right: 1rem;"></i>
                Reportes & Exportar
            </h1>
            <a href="index.php" class="btn" style="background: var(--jet-black); color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 8px;">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-warning" style="border-radius: 12px; margin-bottom: 2rem;">
            <?= $mensaje ?>
        </div>
        <?php endif; ?>

        <!-- 🔥 FILTRO GANANCIAS NUEVO -->
        <div class="row mb-4 g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold small">📅 Filtrar Ganancias:</label>
                <select id="filtroPeriodo" class="form-select form-select-sm">
                    <option value="hoy">Hoy</option>
                    <option value="semana">Esta Semana</option>
                    <option value="mes">Este Mes</option>
                    <option value="anio">Este Año</option>
                    <option value="personalizado">Personalizado</option>
                </select>
            </div>
            <div class="col-md-3" id="fechaPersonalizada" style="display:none;">
                <label class="form-label fw-bold small">Desde:</label>
                <input type="date" id="fechaInicio" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                <input type="date" id="fechaFin" class="form-control form-control-sm mt-1" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button onclick="cargarReporte()" class="btn btn-outline-primary btn-sm me-2">
                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                </button>
                <button onclick="exportarReporte()" class="btn btn-success btn-sm">
                    <i class="fas fa-download me-1"></i>Exportar
                </button>
            </div>
        </div>

        <!-- 🔥 CARD GANANCIAS DINÁMICA -->
        <div class="row mb-5">
            <div class="col-12">
                <div id="cardGanancias" class="card shadow-lg border-0 card-ganancias">
                    <div class="card-body text-center p-4">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <h5 class="text-muted">Selecciona un período arriba</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTONES EXPORTAR -->
        <div class="row mb-5 g-3">
            <div class="col-md-6">
                <a href="exportar-excel.php?tipo=csv" class="btn btn-outline-success w-100 h-100 p-4 text-center" style="border-radius: 12px; font-size: 16px;">
                    <i class="fas fa-file-csv fa-2x mb-2 d-block"></i>
                    <strong>CSV</strong><br><small>Datos puros</small>
                </a>
            </div>
            <div class="col-md-6">
                <a href="exportar-excel.php?tipo=excel" class="btn btn-primary w-100 h-100 p-4 text-center" style="border-radius: 12px; font-size: 16px;">
                    <i class="fas fa-file-excel fa-2x mb-2 d-block"></i>
                    <strong>Excel</strong><br><small>Con diseño</small>
                </a>
            </div>
        </div>

        <!-- ESTADÍSTICAS GENERALES -->
        <div class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow border-0 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-utensils fa-3x text-warning mb-3"></i>
                        <h3 class="display-4 fw-bold text-primary"><?= $total_productos ?></h3>
                        <p class="mb-0 fw-semibold text-muted fs-5">Productos Total</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow border-0 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-shopping-cart fa-3x text-danger mb-3"></i>
                        <h3 class="display-4 fw-bold text-success"><?= number_format($total_ventas) ?></h3>
                        <p class="mb-0 fw-semibold text-muted fs-5">Ventas Totales</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card h-100 shadow border-0 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                        <h3 class="display-4 fw-bold">$<?= number_format($total_ingresos, 2) ?></h3>
                        <p class="mb-0 fw-semibold text-muted fs-5">Ingresos Totales</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA PREVIEW -->
        <div class="card shadow">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="mb-3"><i class="fas fa-table me-2 text-primary"></i>Vista Previa Productos</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">Precio</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($productos, 0, 5) as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['Nombre']) ?></strong></td>
                            <td class="text-end fw-bold text-success">$<?= number_format($p['Precio_Venta'], 2) ?></td>
                            <td><?= htmlspecialchars(substr($p['Descripcion'], 0, 60)) ?>...</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($productos) > 5): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                ... y <?= count($productos) - 5 ?> más productos
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- 🔥 JAVASCRIPT al FINAL -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let timeout;
    function cargarReporte() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const periodo = document.getElementById('filtroPeriodo').value;
            const inicio = document.getElementById('fechaInicio').value;
            const fin = document.getElementById('fechaFin').value;
            
            document.getElementById('fechaPersonalizada').style.display = 
                periodo === 'personalizado' ? 'block' : 'none';
            
            fetch(`reportes-ajax.php?periodo=${periodo}&inicio=${inicio}&fin=${fin}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('cardGanancias').innerHTML = `
                        <div class="row g-3 text-center">
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body bg-primary text-white">
                                        <i class="fas fa-calendar-day fa-2x mb-2 opacity-75"></i>
                                        <h3 class="display-6 fw-bold">$${data.hoy?.toLocaleString() || '0'}</h3>
                                        <small class="fw-bold">Hoy</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body bg-success text-white">
                                        <i class="fas fa-calendar-week fa-2x mb-2 opacity-75"></i>
                                        <h3 class="display-6 fw-bold">$${data.semana?.toLocaleString() || '0'}</h3>
                                        <small class="fw-bold">Semana</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body bg-warning text-dark">
                                        <i class="fas fa-calendar-month fa-2x mb-2 opacity-75"></i>
                                        <h3 class="display-6 fw-bold">$${data.mes?.toLocaleString() || '0'}</h3>
                                        <small class="fw-bold">Mes</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card h-100 border-0 shadow-sm hover-shadow">
                                    <div class="card-body bg-info text-white">
                                        <i class="fas fa-dollar-sign fa-2x mb-2 opacity-75"></i>
                                        <h3 class="display-6 fw-bold">$${data.total?.toLocaleString() || '0'}</h3>
                                        <small class="fw-bold">Período</small>
                                        <div class="mt-1">${data.pedidos || 0} pedidos • ${data.productos || 0} items</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).catch(() => {
                    document.getElementById('cardGanancias').innerHTML = 
                        '<div class="alert alert-warning text-center">No hay datos disponibles</div>';
                });
        }, 300);
    }
    
    function exportarReporte() {
        const params = new URLSearchParams({
            periodo: document.getElementById('filtroPeriodo').value,
            inicio: document.getElementById('fechaInicio').value,
            fin: document.getElementById('fechaFin').value
        });
        window.open('exportar-ganancias.php?' + params, '_blank');
    }
    
    // Cargar al iniciar
    document.addEventListener('DOMContentLoaded', () => {
        cargarReporte();
        document.getElementById('filtroPeriodo').addEventListener('change', cargarReporte);
    });
    </script>
</body>
</html>