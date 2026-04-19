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

<?php
function getReservas($conn, $filtros = []) {
    $sql = "SELECT * FROM Reservas WHERE 1=1";
    $params = [];
    
    if (!empty($filtros['fecha'])) {
        $sql .= " AND Fecha = ?";
        $params[] = $filtros['fecha'];
    }
    if (!empty($filtros['estado'])) {
        $sql .= " AND Estado = ?";
        $params[] = $filtros['estado'];
    }
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (Nombre LIKE ? OR Email LIKE ? OR Telefono LIKE ?)";
        $buscar = "%" . $filtros['busqueda'] . "%";
        $params[] = $buscar; $params[] = $buscar; $params[] = $buscar;
    }
    
    $sql .= " ORDER BY Fecha ASC, Hora ASC";
    return db_fetch_all($conn, $sql, $params);
}

function actualizarEstadoReserva($conn, $id, $estado) {
    return db_query($conn, "UPDATE Reservas SET Estado = ? WHERE Id_Reserva = ?", [$estado, $id]);
}

function eliminarReserva($conn, $id) {
    return db_query($conn, "DELETE FROM Reservas WHERE Id_Reserva = ?", [$id]);
}

function statsReservas($conn) {
    $total = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Reservas")['total'] ?? 0;
    $pendientes = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Reservas WHERE Estado = 'Pendiente'")['total'] ?? 0;
    $hoy = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Reservas WHERE CONVERT(DATE, Created_At) = CONVERT(DATE, GETDATE())")['total'] ?? 0;
    
    return [
        'total' => $total,
        'pendientes' => $pendientes,
        'hoy' => $hoy
    ];
}
?>

//AQUI SOLO SIRVE DE AYUDA EN LAS RESERVAS NO HACE MUCHO SOLO LANZAR MENSAJES DE CONFIRMACION