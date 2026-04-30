<?php
require_once '../config/database.php';

// Obtener productos del MENÚ (tabla Productos) - AGREGADA COLUMNA IMAGEN
function getProductos($conn, $busqueda = '') {
    if ($busqueda) {
        $sql = "SELECT Id_Producto as id, Nombre, Descripcion, Precio_Venta as precio, Imagen 
                FROM Productos 
                WHERE Nombre LIKE ? OR Descripcion LIKE ?
                ORDER BY Nombre";
        return db_fetch_all($conn, $sql, ["%$busqueda%", "%$busqueda%"]);
    }
    
    $sql = "SELECT Id_Producto as id, Nombre, Descripcion, Precio_Venta as precio, Imagen 
            FROM Productos 
            ORDER BY Nombre";
    return db_fetch_all($conn, $sql);
}

// Insertar datos de prueba CON IMÁGENES
function insertarProductosPrueba($conn) {
    $sql = "SELECT COUNT(*) as total FROM Productos";
    $count = db_fetch_one($conn, $sql)['total'];
    
    if ($count == 0) {
        $productos = [
            ['Pancakes Clásicos', 'Pancakes esponjosos con maple syrup y frutas frescas', 85.00, 'pancakes.jpg'],
            ['Café Especial Casa', '100% Arábica tostado artesanalmente', 45.00, 'cafe.jpg'],
            ['Filete Bistro', 'Corte premium con salsa de hongos', 285.00, 'filete.jpg'],
            ['Croissant Francés', 'Recién horneado con mantequilla', 35.00, 'croissant.jpg'],
            ['Latte Macchiato', 'Leche vaporizada con espresso doble', 55.00, 'latte.jpg'],
            ['Tiramisú Italiano', 'Clásico con mascarpone y café', 75.00, 'tiramisu.jpg']
        ];
        
        $sql = "INSERT INTO Productos (Nombre, Descripcion, Precio_Venta, Imagen) VALUES (?, ?, ?, ?)";
        foreach ($productos as $p) {
            db_query($conn, $sql, $p);
        }
        return true;
    }
    return false;
}
?>