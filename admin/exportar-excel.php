<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Administrador']);

// Obtener datos
$productos = db_fetch_all($conn, "SELECT * FROM Productos ORDER BY Nombre ASC");

// Detectar tipo de archivo
$tipo = $_GET['tipo'] ?? 'csv';

if ($tipo === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="menu-' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF"; // BOM para Excel
} else {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="menu-' . date('Y-m-d') . '.csv"');
}

$output = fopen('php://output', 'w');

// Encabezados
fputcsv($output, [
    'ID', 'Producto', 'Descripción', 'Precio', 'Imagen', 
    'Total Vendidos', 'Ingresos Estimados', 'Fecha Creación'
]);

// Datos
foreach ($productos as $producto) {
    // Calcular ventas por producto (opcional)
    $ventas_producto = db_fetch_one($conn, "
        SELECT COUNT(*) as ventas, COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) as ingresos 
        FROM Detalle_Pedidos dp 
        JOIN Productos p ON dp.producto_id = p.Id_Producto 
        WHERE p.Id_Producto = ?
    ", [$producto['Id_Producto']]);
    
    fputcsv($output, [
        $producto['Id_Producto'],
        $producto['Nombre'],
        $producto['Descripcion'],
        '$' . number_format($producto['Precio_Venta'], 2),
        $producto['Imagen'] ?: 'Sin imagen',
        $ventas_producto['ventas'] ?? 0,
        '$' . number_format($ventas_producto['ingresos'], 2),
        date('d/m/Y')
    ]);
}

fclose($output);
exit;
?>