<?php
require_once '../config/database.php';

//AYUDA CON LA SIMULACION YA QUE NO HAY PRODUCTOS AUN EN EL SQL SERVER

// Obtener productos del MENÚ (tabla Productos)
function getProductos($conn, $busqueda = '') {
    if ($busqueda) {
        $sql = "SELECT Id_Producto as id, Nombre, Descripcion, Precio_Venta as precio 
                FROM Productos 
                WHERE Nombre LIKE ? OR Descripcion LIKE ?
                ORDER BY Nombre";
        return db_fetch_all($conn, $sql, ["%$busqueda%", "%$busqueda%"]);
    }
    
    $sql = "SELECT Id_Producto as id, Nombre, Descripcion, Precio_Venta as precio 
            FROM Productos 
            ORDER BY Nombre";
    return db_fetch_all($conn, $sql);
}

// Insertar datos de prueba si no hay productos
function insertarProductosPrueba($conn) {
    $sql = "SELECT COUNT(*) as total FROM Productos";
    $count = db_fetch_one($conn, $sql)['total'];
    
    if ($count == 0) {
        $productos = [
            ['Pancakes Clásicos', 'Pancakes esponjosos con maple syrup y frutas frescas', 85.00],
            ['Café Especial Casa', '100% Arábica tostado artesanalmente', 45.00],
            ['Filete Bistro', 'Corte premium con salsa de hongos', 285.00],
            ['Croissant Francés', 'Recién horneado con mantequilla', 35.00],
            ['Latte Macchiato', 'Leche vaporizada con espresso doble', 55.00],
            ['Tiramisú Italiano', 'Clásico con mascarpone y café', 75.00]
        ];
        
        $sql = "INSERT INTO Productos (Nombre, Descripcion, Precio_Venta) VALUES (?, ?, ?)";
        foreach ($productos as $p) {
            db_query($conn, $sql, $p);
        }
        return true;
    }
    return false;
}
?>