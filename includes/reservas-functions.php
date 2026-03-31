<?php
require_once '../config/database.php';

// Crear tabla Reservas si no existe
function crearTablaReservas($conn) {
    $sql = "
    IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Reservas' AND xtype='U')
    CREATE TABLE Reservas (
        Id_Reserva INT PRIMARY KEY IDENTITY(1,1),
        Nombre VARCHAR(120) NOT NULL,
        Telefono VARCHAR(20),
        Correo VARCHAR(120),
        Personas INT NOT NULL,
        Fecha DATE NOT NULL,
        Hora TIME NOT NULL,
        Duracion_Minutos INT DEFAULT 90,
        Notas TEXT,
        Estado VARCHAR(20) DEFAULT 'Pendiente',
        Fecha_Creacion DATETIME DEFAULT GETDATE()
    )";
    $conn->exec($sql);
}

// Obtener disponibilidades
function getDisponibilidades($conn, $fecha) {
    crearTablaReservas($conn);
    
    $sql = "SELECT 
                CAST(Hora AS TIME) as hora,
                COUNT(*) as ocupadas
            FROM Reservas 
            WHERE Fecha = ? AND Estado IN ('Pendiente', 'Confirmada')
            GROUP BY CAST(Hora AS TIME)
            HAVING COUNT(*) >= 1";
    
    $ocupadas = db_fetch_all($conn, $sql, [$fecha]);
    return $ocupadas;
}

// Guardar reserva
function guardarReserva($conn, $datos) {
    crearTablaReservas($conn);
    
    $sql = "INSERT INTO Reservas (Nombre, Telefono, Correo, Personas, Fecha, Hora, Duracion_Minutos, Notas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    return db_query($conn, $sql, [
        $datos['nombre'],
        $datos['telefono'],
        $datos['correo'],
        $datos['personas'],
        $datos['fecha'],
        $datos['hora'],
        $datos['duracion'] ?? 90,
        $datos['notas'] ?? ''
    ]);
}

// Reservas recientes (para admin)
function getReservasRecientes($conn, $limit = 10) {
    crearTablaReservas($conn);
    $sql = "SELECT TOP (?) * FROM Reservas ORDER BY Fecha_Creacion DESC";
    return db_fetch_all($conn, $sql, [$limit]);
}
?>

//AQUI SOLO SIRVE DE AYUDA EN LAS RESERVAS NO HACE MUCHO SOLO LANZAR MENSAJES DE CONFIRMACION