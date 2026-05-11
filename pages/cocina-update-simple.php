<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idVenta = (int)$_POST['id_venta'];
    $nuevoEstado = $_POST['estado'];
    
    // ⭐ CAMBIO 1: AGREGAR 'cancelado' a estados válidos
    $estadosValidos = ['ingreso', 'elaboracion', 'terminado', 'entregado', 'cancelado'];
    
    if (in_array($nuevoEstado, $estadosValidos)) {
        try {
            // ⭐ CAMBIO 2: Opcional - Obtener estado actual para log
            $stmtActual = $conn->prepare("SELECT Estado_Cocina FROM Ventas_Caja WHERE Id_Venta = ?");
            $stmtActual->execute([$idVenta]);
            $estadoAnterior = $stmtActual->fetchColumn() ?: 'ingreso';
            
            $stmt = $conn->prepare("
                UPDATE Ventas_Caja 
                SET Estado_Cocina = ?, 
                    Fecha = GETDATE()
                WHERE Id_Venta = ?
            ");
            $stmt->execute([$nuevoEstado, $idVenta]);
            
            // ⭐ LOG simple (opcional)
            error_log("Cocina: Orden #$idVenta cambió de '$estadoAnterior' a '$nuevoEstado' por " . $_SESSION['usuario']['Nombre']);
            
            echo json_encode([
                'success' => true, 
                'estado' => $nuevoEstado,
                'mensaje' => "Orden #$idVenta → " . strtoupper($nuevoEstado)
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Estado inválido: ' . $nuevoEstado]);
    }
}

header('Location: cocina.php');
exit;
?>