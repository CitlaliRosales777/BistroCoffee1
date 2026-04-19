<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Administrador']);

// ✅ INICIALIZAR VARIABLES
$total_productos = 0;
$total_ventas = 0;
$total_ingresos = 0;
$productos = [];
$mensaje = '';

// Obtener datos con protección
try {
    $productos = db_fetch_all($conn, "SELECT * FROM Productos ORDER BY Nombre ASC");
    $total_productos = count($productos);
    
    $ventas_result = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Ventas");
    $total_ventas = $ventas_result['total'] ?? 0;
    
    $ingresos_result = db_fetch_one($conn, "
        SELECT COALESCE(SUM(Total), 0) as total 
        FROM Ventas 
        WHERE estado = 'Completada'
    ");
    $total_ingresos = $ingresos_result['total'] ?? 0;
    
} catch (Exception $e) {
    $mensaje = "⚠️ Error al cargar datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📊 Reportes - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: var(--bg-section-light);">
    
    <?php include 'index.php'; ?>

    <main class="admin-main" style="margin-left: 280px; padding: 2rem; min-height: 100vh;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
            <h1 style="color: var(--text-primary); font-size: 2.2rem; margin: 0;">
                <i class="fas fa-chart-bar" style="color: var(--dusty-taupe); margin-right: 1rem;"></i>
                Reportes & Exportar
            </h1>
            <a href="index.php" class="btn" style="background: var(--jet-black); padding: 1rem 2rem;">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert" style="background: #fff3cd; color: #856404; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 2rem; border-left: 5px solid #ffc107;">
            <?= $mensaje ?>
        </div>
        <?php endif; ?>

        <!-- BOTONES DE EXPORTAR -->
        <div style="display: flex; gap: 20px; margin-bottom: 3rem; flex-wrap: wrap;">
            <a href="reportes-html.php" target="_blank" class="btn-export" style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 20px 40px; text-decoration: none; border-radius: 16px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 25px rgba(0,123,255,0.3); display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-file-pdf" style="font-size: 24px;"></i>
                <span>📄 Reporte PDF<br><small>Imprimir/Guardar</small></span>
            </a>
            
            <a href="exportar-excel.php?tipo=csv" class="btn-export" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 20px 40px; text-decoration: none; border-radius: 16px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 25px rgba(40,167,69,0.3); display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-file-csv" style="font-size: 24px;"></i>
                <span>📊 Exportar CSV<br><small>Abre en Excel</small></span>
            </a>
            
            <a href="exportar-excel.php?tipo=excel" class="btn-export" style="background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529; padding: 20px 40px; text-decoration: none; border-radius: 16px; font-weight: 700; font-size: 16px; box-shadow: 0 8px 25px rgba(255,193,7,0.4); display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-file-excel" style="font-size: 24px;"></i>
                <span>📈 Excel Directo<br><small>Formato .XLS</small></span>
            </a>
        </div>

        <!-- ESTADÍSTICAS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
            <div class="card" style="padding: 2rem; text-align: center; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                <i class="fas fa-utensils" style="font-size: 3rem; color: #f4a261; margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Productos</h3>
                <div style="font-size: 2.5rem; font-weight: 700; color: #264653;"><?= $total_productos ?></div>
            </div>
            <div class="card" style="padding: 2rem; text-align: center; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #e76f51; margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Ventas Totales</h3>
                <div style="font-size: 2.5rem; font-weight: 700; color: #d00000;"><?= number_format($total_ventas) ?></div>
            </div>
            <div class="card" style="padding: 2rem; text-align: center; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                <i class="fas fa-dollar-sign" style="font-size: 3rem; color: #2a9d8f; margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Ingresos</h3>
                <div style="font-size: 2.5rem; font-weight: 700; color: #007f5f;">$<?= number_format($total_ingresos, 2) ?></div>
            </div>
        </div>

        <!-- TABLA PREVIEW -->
        <div class="card">
            <h3 style="color: var(--text-primary); margin-bottom: 1.5rem;">📋 Vista Previa de Productos</h3>
            <div class="table-responsive">
                <table style="width: 100%;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, var(--dusty-taupe), var(--stone-brown)); color: white;">
                            <th style="padding: 1rem;">Producto</th>
                            <th style="padding: 1rem;">Precio</th>
                            <th style="padding: 1rem;">Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($productos, 0, 5) as $p): ?>
                        <tr style="border-bottom: 1px solid rgba(169,146,125,0.1);">
                            <td style="padding: 1rem; font-weight: 600;"><?= htmlspecialchars($p['Nombre']) ?></td>
                            <td style="padding: 1rem; text-align: right; color: var(--dusty-taupe); font-weight: 700;">
                                $<?= number_format($p['Precio_Venta'], 2) ?>
                            </td>
                            <td style="padding: 1rem; color: var(--text-secondary);"><?= htmlspecialchars(substr($p['Descripcion'], 0, 60)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($productos) > 5): ?>
                        <tr>
                            <td colspan="3" style="padding: 2rem; text-align: center; color: var(--text-light);">
                                ... y <?= $total_productos - 5 ?> más. <a href="reportes-html.php" target="_blank" style="color: #007bff;">Ver reporte completo</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>