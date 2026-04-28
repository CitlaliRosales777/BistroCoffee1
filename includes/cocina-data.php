<?php
// includes/cocina-data.php - DATOS UNIFICADOS PARA DASHBOARD Y COCINA
function obtenerOrdenesCocina($conn, $top = 50, $horasAtras = 24) {
    try {
        $stmt = $conn->prepare("
            SELECT TOP (?) Id_Venta, Cajero, Fecha, Total, Moneda, 
                   CAST(Productos AS NVARCHAR(MAX)) as Productos, 
                   Estado_Cocina as estado
            FROM Ventas_Caja 
            WHERE Estado_Cocina IN ('ingreso', 'elaboracion', 'terminado', 'entregado')
            AND CAST(Fecha AS DATE) >= CAST(DATEADD(HOUR, -$horasAtras, GETDATE()) AS DATE)
            ORDER BY 
                CASE Estado_Cocina 
                    WHEN 'ingreso' THEN 1
                    WHEN 'elaboracion' THEN 2
                    WHEN 'terminado' THEN 3
                    WHEN 'entregado' THEN 4
                END, Fecha DESC
        ");
        $stmt->execute([$top]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerOrdenesCocina: " . $e->getMessage());
        return [];
    }
}

function procesarOrdenesCocina($ordenes) {
    $nuevas = array_filter($ordenes, fn($o) => $o['estado'] === 'ingreso');
    $proceso = array_filter($ordenes, fn($o) => $o['estado'] === 'elaboracion');
    $listas = array_filter($ordenes, fn($o) => $o['estado'] === 'terminado');
    $entregadas = array_filter($ordenes, fn($o) => $o['estado'] === 'entregado');
    
    return [
        'ordenes' => $ordenes,
        'nuevas' => $nuevas,
        'proceso' => $proceso,
        'listas' => $listas,
        'entregadas' => $entregadas,
        'total' => count($ordenes)
    ];
}
?>