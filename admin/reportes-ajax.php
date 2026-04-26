<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$periodo = $_GET['periodo'] ?? 'hoy';
$inicio = $_GET['inicio'] ?? '';
$fin = $_GET['fin'] ?? '';

function gananciasPeriodo($conn, $inicio, $fin) {
    $sql = "SELECT 
        COUNT(*) as pedidos,
        ISNULL(SUM(dp.cantidad * dp.precio_unitario), 0) as total,
        ISNULL(SUM(dp.cantidad), 0) as productos
    FROM Pedidos p 
    JOIN Detalle_Pedidos dp ON p.Id_Pedido = dp.pedido_id 
    WHERE p.estado != 'Cancelado'";
    
    $params = [];
    
    if($inicio && $fin) {
        $sql .= " AND p.fecha BETWEEN ? AND ?";
        $params = [$inicio, $fin];
    } elseif($periodo === 'hoy') {
        $sql .= " AND CAST(p.fecha AS DATE) = CAST(GETDATE() AS DATE)";
    } elseif($periodo === 'semana') {
        $sql .= " AND DATEPART(WEEK, p.fecha) = DATEPART(WEEK, GETDATE()) 
                  AND YEAR(p.fecha) = YEAR(GETDATE())";
    } elseif($periodo === 'mes') {
        $sql .= " AND MONTH(p.fecha) = MONTH(GETDATE()) 
                  AND YEAR(p.fecha) = YEAR(GETDATE())";
    } elseif($periodo === 'anio') {
        $sql .= " AND YEAR(p.fecha) = YEAR(GETDATE())";
    }
    
    $result = db_fetch_one($conn, $sql, $params);
    return [
        'total' => (float)($result['total'] ?? 0),
        'pedidos' => (int)($result['pedidos'] ?? 0),
        'productos' => (int)($result['productos'] ?? 0)
    ];
}

$response = [];

// Calcular por períodos
$response['hoy'] = gananciasPeriodo($conn, '', '')->total;
$response['semana'] = gananciasPeriodo($conn, '', 'semana')->total;
$response['mes'] = gananciasPeriodo($conn, '', 'mes')->total;
$response['total'] = gananciasPeriodo($conn, $inicio, $fin)['total'];
$response['pedidos'] = gananciasPeriodo($conn, $inicio, $fin)['pedidos'];
$response['productos'] = gananciasPeriodo($conn, $inicio, $fin)['productos'];

echo json_encode($response);
?>