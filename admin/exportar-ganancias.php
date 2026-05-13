<?php
// BUFFER CLEAN
while (ob_get_level()) ob_end_clean();

require_once '../config/database.php';
require_once '../includes/auth.php';

// ====================== PARÁMETROS ======================
$periodo     = $_GET['periodo'] ?? 'mes';
$fechaInicio = $_GET['inicio'] ?? '';
$fechaFin    = $_GET['fin'] ?? '';
$tipo        = $_GET['tipo'] ?? 'excel';

$titulo = '';

// ====================== DETERMINAR FECHAS ======================
switch ($periodo) {
    case 'hoy':
        $fechaInicio = date('Y-m-d');
        $fechaFin    = date('Y-m-d');
        $titulo      = 'Hoy';
        break;

    case 'semana':
        $fechaInicio = date('Y-m-d', strtotime('monday this week'));
        $fechaFin    = date('Y-m-d');
        $titulo      = 'Esta Semana';
        break;

    case 'mes':
        $fechaInicio = date('Y-m-01');
        $fechaFin    = date('Y-m-d');
        $titulo      = 'Este Mes';
        break;

    case 'anio':
        $fechaInicio = date('Y-01-01');
        $fechaFin    = date('Y-m-d');
        $titulo      = 'Este Año';
        break;

    case 'personalizado':
        $titulo = 'Personalizado';
        // Validación estricta
        if (empty($fechaInicio) || empty($fechaFin)) {
            $fechaInicio = date('Y-m-01');
            $fechaFin    = date('Y-m-d');
        }
        break;

    default:
        $fechaInicio = date('Y-m-01');
        $fechaFin    = date('Y-m-d');
        $titulo      = 'Este Mes';
        break;
}

// ====================== FUNCIÓN CORREGIDA ======================
function gananciasPeriodo($conn, $inicio, $fin) {
    $sql = "
        SELECT 
            COUNT(DISTINCT v.id) as pedidos,
            COALESCE(SUM(dp.cantidad), 0) as productos,
            COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) as total
        FROM Ventas v
        LEFT JOIN Detalle_Pedidos dp ON v.id = dp.venta_id 
        WHERE v.estado = 'Completada'
    ";

    $params = [];
    
    if (!empty($inicio) && !empty($fin)) {
        $sql .= " AND DATE(v.fecha) BETWEEN ? AND ?";
        $params = [$inicio, $fin];
    }

    $resultado = db_fetch_one($conn, $sql, $params);
    return $resultado ?: ['pedidos' => 0, 'productos' => 0, 'total' => 0];
}

// ====================== CSV ======================
if ($tipo === 'csv') {
    $filename = "ganancias-{$periodo}-" . date('Y-m-d_His');

    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8

    fputcsv($output, ['REPORTE DE GANANCIAS - ' . strtoupper($titulo)]);
    fputcsv($output, ['Período', "$fechaInicio al $fechaFin"]);
    fputcsv($output, []);
    fputcsv($output, ['MÉTRICA', 'CANTIDAD', 'VALOR']);

    $datos = gananciasPeriodo($conn, $fechaInicio, $fechaFin);
    $promedio = $datos['pedidos'] > 0 ? $datos['total'] / $datos['pedidos'] : 0;

    fputcsv($output, ['Total Pedidos', $datos['pedidos'], '']);
    fputcsv($output, ['Productos Vendidos', $datos['productos'], '']);
    fputcsv($output, ['Ganancias Totales', '', '$' . number_format($datos['total'], 2)]);
    fputcsv($output, ['Promedio por Pedido', '', '$' . number_format($promedio, 2)]);

    fclose($output);
    exit;
}

// ====================== EXCEL (HTML) ======================
$filename = "ganancias-{$periodo}-" . date('Y-m-d_His');

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo) ?> - Bistro Coffee</title>
    <style>
        * { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px; }
        .header { text-align: center; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header h1 { color: #2c3e50; font-size: 32px; margin-bottom: 10px; }
        .header .periodo { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 25px; border-radius: 50px; display: inline-block; font-weight: 600; }
        .header .fecha { color: #7f8c8d; font-size: 18px; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        th { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; padding: 20px 15px; text-align: center; font-weight: 700; }
        td { padding: 18px 15px; border-bottom: 1px solid #ecf0f1; text-align: center; font-size: 16px; }
        .ganancia { font-weight: bold; color: #27ae60; font-size: 20px; }
        .total-row { background: linear-gradient(135deg, #2ecc71, #27ae60) !important; color: white !important; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 Reporte de Ganancias</h1>
        <div class="periodo"><?= htmlspecialchars($titulo) ?></div>
        <div class="fecha">
            Desde <?= date('d/m/Y', strtotime($fechaInicio)) ?> 
            hasta <?= date('d/m/Y', strtotime($fechaFin)) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Período Analizado</th>
                <th>Total Pedidos</th>
                <th>Productos Vendidos</th>
                <th>Ganancias Totales</th>
                <th>Promedio por Pedido</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $datos = gananciasPeriodo($conn, $fechaInicio, $fechaFin);
            $promedio = $datos['pedidos'] > 0 ? $datos['total'] / $datos['pedidos'] : 0;
            ?>
            <tr class="total-row">
                <td><?= date('d/m/Y', strtotime($fechaInicio)) ?> - <?= date('d/m/Y', strtotime($fechaFin)) ?></td>
                <td><strong><?= number_format($datos['pedidos']) ?></strong></td>
                <td><strong><?= number_format($datos['productos']) ?></strong></td>
                <td class="ganancia">$<?= number_format($datos['total'], 2) ?></td>
                <td class="ganancia">$<?= number_format($promedio, 2) ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>