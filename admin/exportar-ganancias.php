<?php
// BUFFER CLEAN
while(ob_get_level()) ob_end_clean();

require_once '../config/database.php';
require_once '../includes/auth.php';

$periodo = $_GET['periodo'] ?? 'mes';
$fechaInicio = $_GET['inicio'] ?? '';
$fechaFin = $_GET['fin'] ?? '';

// Determinar fechas
switch($periodo) {
    case 'hoy':
        $fechaInicio = date('Y-m-d');
        $fechaFin = date('Y-m-d');
        $titulo = 'Ganancias Hoy';
        break;
    case 'semana':
        $fechaInicio = date('Y-m-d', strtotime('monday this week'));
        $fechaFin = date('Y-m-d');
        $titulo = 'Ganancias Semana';
        break;
    case 'mes':
        $fechaInicio = date('Y-m-01');
        $fechaFin = date('Y-m-d');
        $titulo = 'Ganancias Mes';
        break;
    case 'anio':
        $fechaInicio = date('Y-01-01');
        $fechaFin = date('Y-m-d');
        $titulo = 'Ganancias Año';
        break;
    default:
        $titulo = 'Ganancias Personalizado';
}

// TIPO de archivo
$tipo = $_GET['tipo'] ?? 'excel';
$filename = "ganancias-{$periodo}-" . date('Y-m-d');

if($tipo === 'csv') {
    // CSV Simple
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    
    fputcsv($output, [
        'Período', 'Pedidos', 'Productos Vendidos', 'Ganancias Totales', 'Promedio Pedido'
    ]);
    
    // Datos del período
    $datos = gananciasPeriodo($conn, $fechaInicio, $fechaFin);
    fputcsv($output, [
        "$fechaInicio al $fechaFin",
        $datos['pedidos'],
        $datos['productos'],
        '$' . number_format($datos['total'], 2),
        $datos['pedidos'] ? '$' . number_format($datos['total'] / $datos['pedidos'], 2) : '$0.00'
    ]);
    
    fclose($output);
    exit;
}

// HTML con DISEÑO para Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?> - Bistro Coffee</title>
    <style>
        * { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px; }
        .header { 
            text-align: center; 
            background: white; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 { 
            color: #2c3e50; 
            font-size: 32px; 
            margin-bottom: 10px; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .header .periodo { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            padding: 12px 25px; 
            border-radius: 50px; 
            display: inline-block; 
            font-weight: 600;
            font-size: 16px;
        }
        .header .fecha { color: #7f8c8d; font-size: 18px; margin-top: 10px; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white; 
            border-radius: 15px; 
            overflow: hidden; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        th { 
            background: linear-gradient(135deg, #f093fb, #f5576c); 
            color: white; 
            padding: 20px 15px; 
            text-align: center; 
            font-weight: 700; 
            font-size: 16px; 
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        td { 
            padding: 18px 15px; 
            border-bottom: 1px solid #ecf0f1; 
            text-align: center;
            font-size: 16px;
        }
        .ganancia { 
            font-weight: bold; 
            color: #27ae60; 
            font-size: 20px; 
        }
        .total-row { 
            background: linear-gradient(135deg, #2ecc71, #27ae60) !important; 
            color: white !important; 
            font-weight: bold; 
            font-size: 18px;
        }
        tr:nth-child(even) { background: #fdfefe; }
        tr:hover { background: #ecf0f1 !important; transform: scale(1.01); transition: all 0.3s; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 Reporte de Ganancias</h1>
        <div class="periodo"><?= $titulo ?></div>
        <div class="fecha">Desde <?= date('d/m/Y', strtotime($fechaInicio)) ?> hasta <?= date('d/m/Y', strtotime($fechaFin)) ?></div>
        <div style="margin-top: 15px; font-size: 14px; color: #7f8c8d;">
            Generado: <?= date('d/m/Y H:i') ?>
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
            $promedio = $datos['pedidos'] ? $datos['total'] / $datos['pedidos'] : 0;
            ?>
            <tr>
                <td style="font-weight: 600; text-align: left;">
                    <?= date('d/m/Y', strtotime($fechaInicio)) ?> - <?= date('d/m/Y', strtotime($fechaFin)) ?>
                </td>
                <td><strong><?= number_format($datos['pedidos']) ?></strong></td>
                <td><strong><?= number_format($datos['productos']) ?></strong></td>
                <td class="ganancia">$<?= number_format($datos['total'], 2) ?></td>
                <td class="ganancia">$<?= number_format($promedio, 2) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="3"><strong>TOTAL GANANCIAS</strong></td>
                <td colspan="2" class="ganancia">$<?= number_format($datos['total'], 2) ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>

<?php
function gananciasPeriodo($conn, $inicio, $fin) {
    $sql = "
        SELECT 
            COUNT(DISTINCT p.Id_Pedido) as pedidos,
            ISNULL(SUM(dp.cantidad), 0) as productos,
            ISNULL(SUM(dp.cantidad * dp.precio_unitario), 0) as total
        FROM Pedidos p 
        JOIN Detalle_Pedidos dp ON p.Id_Pedido = dp.pedido_id 
        WHERE p.estado IN ('Completada', 'Confirmada')
    ";
    
    $params = [];
    if($inicio && $fin) {
        $sql .= " AND p.fecha BETWEEN ? AND ?";
        $params = [$inicio, $fin];
    }
    
    return db_fetch_one($conn, $sql, $params) ?: ['pedidos' => 0, 'productos' => 0, 'total' => 0];
}
?>