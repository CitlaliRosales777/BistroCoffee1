<?php
// BUFFER CLEAN
while(ob_get_level()) ob_end_clean();

require_once '../config/database.php';
require_once '../includes/auth.php';

$tipo = $_GET['tipo'] ?? 'excel';
$periodo = $_GET['periodo'] ?? 'mes';
$inicio = $_GET['inicio'] ?? null;
$fin = $_GET['fin'] ?? null;

// ====================== CALCULAR FECHAS SEGÚN FILTRO ======================
$where = "";
$params = [];

switch($periodo) {
    case 'hoy':
        $where = "WHERE DATE(v.fecha) = CURDATE()";
        break;
    case 'semana':
        $where = "WHERE v.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'mes':
        $where = "WHERE v.fecha >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    case 'anio':
        $where = "WHERE YEAR(v.fecha) = YEAR(CURDATE())";
        break;
    case 'personalizado':
        if($inicio && $fin) {
            $where = "WHERE DATE(v.fecha) BETWEEN ? AND ?";
            $params = [$inicio, $fin];
        }
        break;
    default:
        $where = ""; // sin filtro
}

// ====================== CSV ======================
if($tipo === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte-ganancias-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8
    
    fputcsv($output, ['ID','Producto','Descripción','Precio','Vendidos','Ingresos']);
    
    $sql = "
        SELECT p.Id_Producto, p.Nombre, p.Descripcion, p.Precio_Venta,
               COUNT(dp.id) as vendidos,
               SUM(dp.cantidad * dp.precio_unitario) as ingresos
        FROM Productos p
        LEFT JOIN Detalle_Pedidos dp ON dp.producto_id = p.Id_Producto
        LEFT JOIN Ventas v ON v.id = dp.venta_id
        $where
        GROUP BY p.Id_Producto, p.Nombre, p.Descripcion, p.Precio_Venta
        ORDER BY p.Nombre ASC";
    
    $stmt = db_query($conn, $sql, $params);
    while($row = $stmt->fetch()){
        fputcsv($output, [
            $row['Id_Producto'],
            $row['Nombre'],
            substr($row['Descripcion'], 0, 50),
            number_format($row['Precio_Venta'], 2),
            $row['vendidos'] ?? 0,
            number_format($row['ingresos'] ?? 0, 2)
        ]);
    }
    fclose($output);
    exit;
}

// ====================== EXCEL CON DISEÑO ======================
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="reporte-ganancias-' . date('Y-m-d') . '.xls"');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ganancias</title>
    <style>
        * { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; color: black; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { font-size: 16px; font-weight: 500; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        th { 
            background: linear-gradient(135deg, #F4A261, #E76F51); 
            color: black; 
            padding: 15px; 
            text-align: center; 
            font-weight: 600; 
        }
        td { 
            padding: 12px 10px; 
            border-bottom: 1px solid #eee; 
            text-align: center;
        }
        tr:hover { background: #FFF3E0; }
        .precio { font-weight: bold; color: #2A9D8F; }
        .total-row { background: linear-gradient(135deg, #E9C46A, #F4A261) !important; font-weight: bold; font-size: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BISTRO & COFFEE</h1>
        <p>Reporte de Ganancias - <?= ucfirst($periodo) ?> 
           <?php if($periodo === 'personalizado') echo "($inicio al $fin)"; ?>
           <br><small><?= date('d/m/Y H:i') ?></small>
        </p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Descripción</th>
                <th>Precio Unitario</th>
                <th>Vendidos</th>
                <th>Ingresos</th>
            </tr>
        </thead>
        <tbody>
<?php
$totalVendidos = 0;
$totalIngresos = 0;

$sql = "
    SELECT p.Id_Producto, p.Nombre, p.Descripcion, p.Precio_Venta,
           COUNT(dp.id) as vendidos,
           SUM(dp.cantidad * dp.precio_unitario) as ingresos
    FROM Productos p
    LEFT JOIN Detalle_Pedidos dp ON dp.producto_id = p.Id_Producto
    LEFT JOIN Ventas v ON v.id = dp.venta_id
    $where
    GROUP BY p.Id_Producto, p.Nombre, p.Descripcion, p.Precio_Venta
    ORDER BY p.Nombre ASC";

$stmt = db_query($conn, $sql, $params);

while($row = $stmt->fetch()){
    $vendidos = $row['vendidos'] ?? 0;
    $ingresos = $row['ingresos'] ?? 0;
    $totalVendidos += $vendidos;
    $totalIngresos += $ingresos;
?>
            <tr>
                <td><?= $row['Id_Producto'] ?></td>
                <td style="text-align:left; font-weight:500;"><?= htmlspecialchars($row['Nombre']) ?></td>
                <td style="text-align:left; font-size:13px;"><?= htmlspecialchars(substr($row['Descripcion'],0,45)) ?>...</td>
                <td class="precio">$<?= number_format($row['Precio_Venta'],2) ?></td>
                <td><?= number_format($vendidos) ?></td>
                <td class="precio">$<?= number_format($ingresos,2) ?></td>
            </tr>
<?php } ?>
            <tr class="total-row">
                <td colspan="4"><strong>TOTAL GENERAL</strong></td>
                <td><strong><?= number_format($totalVendidos) ?></strong></td>
                <td><strong>$<?= number_format($totalIngresos,2) ?></strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>