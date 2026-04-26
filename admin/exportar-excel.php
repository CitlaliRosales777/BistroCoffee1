<?php
// BUFFER CLEAN
while(ob_get_level()) ob_end_clean();

require_once '../config/database.php';
require_once '../includes/auth.php';

$tipo = $_GET['tipo'] ?? 'excel';

if($tipo === 'csv') {
    // CSV Puro (sin diseño)
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="menu-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    
    fputcsv($output, ['ID','Producto','Descripción','Precio','Vendidos','Ingresos']);
    
    $stmt = db_query($conn, "SELECT Id_Producto,Nombre,Descripcion,Precio_Venta FROM Productos ORDER BY Nombre");
    while($row = $stmt->fetch()){
        $ventas = db_fetch_one($conn, "SELECT COUNT(*) t,ISNULL(SUM(cantidad*precio_unitario),0) i FROM Detalle_Pedidos WHERE producto_id=?",[$row['Id_Producto']]);
        fputcsv($output, [
            $row['Id_Producto'],
            $row['Nombre'],
            substr($row['Descripcion'],0,50),
            '$'.number_format($row['Precio_Venta'],2),
            $ventas['t']??0,
            '$'.number_format($ventas['i']??0,2)
        ]);
    }
    fclose($output);
    exit;
}

// EXCEL con DISEÑO SIMPLE (HTML)
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="menu-' . date('Y-m-d') . '.xls"');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Menu Bistro&Coffee</title>
    <style>
        * { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; color: black; }
        .header h1 { font-size: 28px; margin-bottom: 5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .header p { font-size: 14px; opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        th { 
            background: linear-gradient(135deg, #F4A261, #E76F51); 
            color: black; 
            padding: 15px 10px; 
            text-align: center; 
            font-weight: 600; 
            font-size: 14px; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td { 
            padding: 12px 10px; 
            border-bottom: 1px solid #eee; 
            text-align: center;
        }
        tr:hover { background: #FFF3E0; }
        .precio { font-weight: bold; color: #2A9D8F; font-size: 15px; }
        .total-row { background: linear-gradient(135deg, #E9C46A, #F4A261) !important; font-weight: bold; color: black; }
        .total-row td { font-size: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BISTRO&COFFEE</h1>
        <p>Reporte de Productos - <?= date('d/m/Y H:i') ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Vendidos</th>
                <th>Ingresos</th>
            </tr>
        </thead>
        <tbody>
<?php
$totalIngresos = 0;
$stmt = db_query($conn, "SELECT Id_Producto,Nombre,Descripcion,Precio_Venta FROM Productos ORDER BY Nombre");
while($row = $stmt->fetch()){
    $ventas = db_fetch_one($conn, "SELECT COUNT(*) t,ISNULL(SUM(cantidad*precio_unitario),0) i FROM Detalle_Pedidos WHERE producto_id=?",[$row['Id_Producto']]);
    $ingresos = $ventas['i'] ?? 0;
    $totalIngresos += $ingresos;
?>
            <tr>
                <td><?= $row['Id_Producto'] ?></td>
                <td style="text-align:left; font-weight:500;"><?= htmlspecialchars($row['Nombre']) ?></td>
                <td style="text-align:left; font-size:13px;"><?= htmlspecialchars(substr($row['Descripcion'],0,40)) ?></td>
                <td class="precio">$<?= number_format($row['Precio_Venta'],2) ?></td>
                <td><?= $ventas['t'] ?? 0 ?></td>
                <td class="precio">$<?= number_format($ingresos,2) ?></td>
            </tr>
<?php } ?>
            <tr class="total-row">
                <td colspan="5"><strong>TOTAL GENERAL</strong></td>
                <td><strong>$<?= number_format($totalIngresos,2) ?></strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>