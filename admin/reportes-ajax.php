<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Administrador']);

header('Content-Type: application/json');

$periodo = $_GET['periodo'] ?? 'hoy';
$inicio  = $_GET['inicio'] ?? '';
$fin     = $_GET['fin'] ?? '';

try {
    $response = [
        'hoy'       => 0,
        'semana'    => 0,
        'mes'       => 0,
        'total'     => 0,
        'pedidos'   => 0,
        'productos' => 0
    ];

    // ====================== HOY ======================
    $sql_hoy = "SELECT 
        COUNT(*) as pedidos,
        ISNULL(SUM(Total), 0) as total
    FROM Ventas_Caja 
    WHERE CONVERT(DATE, Fecha) = CONVERT(DATE, GETDATE())
      AND Estado_Cocina != 'cancelado'";

    $res = db_fetch_one($conn, $sql_hoy);
    $response['hoy'] = (float)($res['total'] ?? 0);

    // ====================== ESTA SEMANA ======================
    $sql_semana = "SELECT 
        COUNT(*) as pedidos,
        ISNULL(SUM(Total), 0) as total
    FROM Ventas_Caja 
    WHERE DATEPART(WEEK, Fecha) = DATEPART(WEEK, GETDATE())
      AND YEAR(Fecha) = YEAR(GETDATE())
      AND Estado_Cocina != 'cancelado'";

    $res = db_fetch_one($conn, $sql_semana);
    $response['semana'] = (float)($res['total'] ?? 0);

    // ====================== ESTE MES ======================
    $sql_mes = "SELECT 
        COUNT(*) as pedidos,
        ISNULL(SUM(Total), 0) as total
    FROM Ventas_Caja 
    WHERE MONTH(Fecha) = MONTH(GETDATE())
      AND YEAR(Fecha) = YEAR(GETDATE())
      AND Estado_Cocina != 'cancelado'";

    $res = db_fetch_one($conn, $sql_mes);
    $response['mes'] = (float)($res['total'] ?? 0);

    // ====================== PERIODO PERSONALIZADO O TOTAL ======================
    if ($periodo === 'personalizado' && !empty($inicio) && !empty($fin)) {
        $sql = "SELECT 
            COUNT(*) as pedidos,
            ISNULL(SUM(Total), 0) as total
        FROM Ventas_Caja 
        WHERE Fecha BETWEEN ? AND ?
          AND Estado_Cocina != 'cancelado'";

        $params = [$inicio . ' 00:00:00', $fin . ' 23:59:59'];
        $res = db_fetch_one($conn, $sql, $params);
    } else {
        // Total general
        $sql = "SELECT 
            COUNT(*) as pedidos,
            ISNULL(SUM(Total), 0) as total
        FROM Ventas_Caja 
        WHERE Estado_Cocina != 'cancelado'";
        
        $res = db_fetch_one($conn, $sql);
    }

    $response['total']     = (float)($res['total'] ?? 0);
    $response['pedidos']   = (int)($res['pedidos'] ?? 0);

    // Productos vendidos (aproximado desde JSON)
    $sql_prod = "SELECT ISNULL(SUM(JSON_VALUE(value, '$.cantidad')), 0) as productos
                 FROM Ventas_Caja 
                 CROSS APPLY OPENJSON(Productos)
                 WHERE Estado_Cocina != 'cancelado'";
    $res_prod = db_fetch_one($conn, $sql_prod);
    $response['productos'] = (int)($res_prod['productos'] ?? 0);

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error reportes-ajax: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage(),
        'hoy' => 0, 'semana' => 0, 'mes' => 0, 'total' => 0
    ]);
}