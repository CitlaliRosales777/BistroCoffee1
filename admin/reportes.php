<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Administrador']);

// INICIALIZAR VARIABLES (solo para primera carga)
$total_productos = 0;
$mensaje = '';

// Obtener datos generales iniciales
try {
    $productos = db_fetch_all($conn, "SELECT * FROM Productos ORDER BY Nombre ASC");
    $total_productos = count($productos);
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
        :root { 
            --dusty-taupe: #A9927D; 
            --stone-brown: #8D7B68; 
            --text-primary: #2C1810; 
        }
        .hover-shadow:hover { 
            transform: translateY(-5px); 
            transition: all 0.3s; 
        }
        .card-ganancias { 
            background: linear-gradient(135deg, #f8f9fa, #e9ecef); 
        }
    </style>
</head>

<body style="background: var(--bg-section-light);">
   
    <main class="admin-main" style="margin-left: 50px; padding: 2rem; min-height: 100vh;">
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

        <!-- FILTRO GANANCIAS -->
        <div class="row mb-4 g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold small">Filtrar Ganancias:</label>
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
                <label class="form-label fw-bold small mt-2">Hasta:</label>
                <input type="date" id="fechaFin" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end gap-2">
                <button onclick="cargarReporte()" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                </button>
                <button onclick="exportarReporte()" class="btn btn-success btn-sm">
                    <i class="fas fa-download me-1"></i>Exportar
                </button>
            </div>
        </div>

        <!-- CARD GANANCIAS DINÁMICA -->
        <div class="row mb-5">
            <div class="col-12">
                <div id="cardGanancias" class="card shadow-lg border-0 card-ganancias"></div>
            </div>
        </div>

        <!-- ESTADÍSTICAS DINÁMICAS -->
        <div class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow border-0 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-utensils fa-3x text-warning mb-3"></i>
                        <h3 class="display-4 fw-bold text-primary" id="totalProductos"><?= $total_productos ?></h3>
                        <p class="mb-0 fw-semibold text-muted fs-5">Productos Total</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow border-0 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-shopping-cart fa-3x text-danger mb-3"></i>
                        <h3 class="display-4 fw-bold text-success" id="totalVentas">0</h3>
                        <p class="mb-0 fw-semibold text-muted fs-5">Pedidos</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card h-100 shadow border-0 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                        <h3 class="display-4 fw-bold" id="totalIngresos">$0</h3>
                        <p class="mb-0 fw-semibold text-muted fs-5">Ingresos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTONES EXPORTAR + TABLA PREVIEW -->
        <div class="row mb-5 g-3">
            <div class="col-md-6">
                <a href="exportar-excel.php?tipo=csv" class="btn btn-outline-success w-100 h-100 p-4 text-center" style="border-radius: 12px; font-size: 16px;">
                    <i class="fas fa-file-csv fa-2x mb-2 d-block"></i>
                    <strong>CSV de Productos</strong><br><small>Datos puros</small>
                </a>
            </div>
            <div class="col-md-6">
                <a href="exportar-excel.php?tipo=excel" class="btn btn-primary w-100 h-100 p-4 text-center" style="border-radius: 12px; font-size: 16px;">
                    <i class="fas fa-file-excel fa-2x mb-2 d-block"></i>
                    <strong>Excel de Productos</strong><br><small>Con diseño</small>
                </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function cargarReporte() {
        const periodo = document.getElementById('filtroPeriodo').value;
        const inicio = document.getElementById('fechaInicio').value;
        const fin = document.getElementById('fechaFin').value;

        document.getElementById('fechaPersonalizada').style.display = 
            (periodo === 'personalizado') ? 'block' : 'none';

        // Loader en la card principal
        document.getElementById('cardGanancias').innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                <p class="text-muted">Cargando reporte...</p>
            </div>`;

        fetch(`reportes-ajax.php?periodo=${periodo}&inicio=${inicio}&fin=${fin}`)
            .then(r => r.json())
            .then(data => {
                // ==================== CARD PRINCIPAL ====================
                let titulo = '', icono = '', subtitulo = '';
                let valor = 0;

                switch(periodo) {
                    case 'hoy': titulo = 'Hoy'; icono = 'fa-calendar-day'; valor = data.hoy || 0; subtitulo = 'Ventas del día'; break;
                    case 'semana': titulo = 'Esta Semana'; icono = 'fa-calendar-week'; valor = data.semana || 0; subtitulo = 'Ventas de la semana'; break;
                    case 'mes': titulo = 'Este Mes'; icono = 'fa-calendar-month'; valor = data.mes || 0; subtitulo = 'Ventas del mes'; break;
                    case 'anio': titulo = 'Este Año'; icono = 'fa-calendar-alt'; valor = data.anio || data.total || 0; subtitulo = 'Ventas del año'; break;
                    case 'personalizado': titulo = 'Período Personalizado'; icono = 'fa-calendar-check'; valor = data.total || 0; subtitulo = `${inicio} al ${fin}`; break;
                }

                document.getElementById('cardGanancias').innerHTML = `
                    <div class="card-body p-5">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <span class="badge bg-light text-dark px-3 py-2 fs-6">${titulo}</span>
                            <i class="fas ${icono} fa-3x opacity-75" style="color: #6c757d;"></i>
                        </div>
                        <h2 class="display-3 fw-bold text-center mb-2">$${Number(valor).toLocaleString()}</h2>
                        <p class="text-center text-muted mb-4">${subtitulo}</p>
                        <div class="pt-4 border-top text-center">
                            <strong class="fs-3">${data.pedidos || 0}</strong><br>
                            <small class="text-muted">Pedidos realizados</small>
                        </div>
                    </div>`;

                // ==================== ACTUALIZAR CARDS INFERIORES ====================
                document.getElementById('totalVentas').textContent = Number(data.pedidos || 0).toLocaleString();
                document.getElementById('totalIngresos').textContent = '$' + Number(valor).toLocaleString();
                // Productos total se mantiene global
            })
            .catch(err => {
                console.error(err);
                document.getElementById('cardGanancias').innerHTML = `
                    <div class="alert alert-danger text-center m-4">Error al cargar los datos.</div>`;
            });
    }

    function exportarReporte() {
        const periodo = document.getElementById('filtroPeriodo').value;
        const inicio = document.getElementById('fechaInicio').value;
        const fin = document.getElementById('fechaFin').value;
        window.open(`exportar-ganancias.php?periodo=${periodo}&inicio=${inicio}&fin=${fin}`, '_blank');
    }

    // Cargar al iniciar
    document.addEventListener('DOMContentLoaded', () => {
        cargarReporte();
        document.getElementById('filtroPeriodo').addEventListener('change', cargarReporte);
    });
    </script>
</body>
</html>