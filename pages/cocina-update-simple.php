<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idVenta = (int)$_POST['id_venta'];
    $nuevoEstado = $_POST['estado'];
    
    $estadosValidos = ['ingreso', 'elaboracion', 'terminado', 'entregado'];
    
    if (in_array($nuevoEstado, $estadosValidos)) {
        try {
            $stmt = $conn->prepare("
                UPDATE Ventas_Caja 
                SET Estado_Cocina = ?, 
                    Fecha = GETDATE()
                WHERE Id_Venta = ?
            ");
            $stmt->execute([$nuevoEstado, $idVenta]);
            
            echo json_encode(['success' => true, 'estado' => $nuevoEstado]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

header('Location: cocina.php');
exit;
?>