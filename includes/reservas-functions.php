<?php
/**
 * reservas-functions.php - SQL Server + Tabla 'Reservas' CORREGIDO
 */

function getDisponibilidades($conn, $fecha) {
    // ✅ SQL Server: CONVERT(DATE) + Hora exacta 5 chars
    $sql = "
        SELECT Hora, Personas, Nombre, Telefono, Estado 
        FROM Reservas 
        WHERE CONVERT(DATE, Fecha) = CONVERT(DATE, :fecha)
        AND Estado IN ('Pendiente', 'Confirmada')
        AND LEN(LTRIM(RTRIM(Hora))) = 5  -- '14:00' exacto
        ORDER BY Hora ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['fecha' => $fecha]);
    
    $disponibilidades = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hora_limpia = trim($row['Hora']);
        $disponibilidades[] = [
            'hora' => $hora_limpia,
            'personas' => (int)$row['Personas'],
            'nombre' => $row['Nombre'],
            'telefono' => $row['Telefono']
        ];
    }
    return $disponibilidades;
}

function contarReservasPorHora($disponibilidades, $hora) {
    // ✅ PHP 7.4+ compatible (sin arrow function)
    $hora_limpia = trim($hora);
    return count(array_filter($disponibilidades, function($disp) use ($hora_limpia) {
        return trim($disp['hora']) === $hora_limpia;
    }));
}

function validarReserva($conn, $fecha, $hora) {
    $disponibilidades = getDisponibilidades($conn, $fecha);
    $libre = contarReservasPorHora($disponibilidades, $hora) === 0;
    return $libre;
}

function guardarReserva($conn, $datos) {
    // ✅ INSERT compatible SQL Server
    $sql = "
        INSERT INTO Reservas (Nombre, Telefono, Correo, Personas, Fecha, Hora, Duracion_Minutos, Notas, Estado) 
        VALUES (:nombre, :telefono, :correo, :personas, :fecha, :hora, :duracion, :notas, 'Pendiente')
    ";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        'nombre' => $datos['nombre'],
        'telefono' => $datos['telefono'],
        'correo' => $datos['correo'] ?? null,
        'personas' => (int)$datos['personas'],
        'fecha' => $datos['fecha'],
        'hora' => trim($datos['hora']),  // '14:00' limpio
        'duracion' => 90,
        'notas' => $datos['notas'] ?? null
    ]);
}

function estaHorarioOcupado($disponibilidades, $hora) {
    return contarReservasPorHora($disponibilidades, $hora) >= 1;
}

function getReservasRecientes($conn, $limit = 10) {
    // ✅ SQL Server: TOP sin bindValue
    $sql = "SELECT TOP " . (int)$limit . " Id_Reserva, Nombre, Telefono, Fecha, Hora, Personas, Estado, created_at 
            FROM Reservas 
            WHERE Estado IN ('Pendiente', 'Confirmada')
            ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ DEBUG function
function debugReservas($conn, $fecha) {
    $disp = getDisponibilidades($conn, $fecha);
    error_log("DEBUG $fecha: " . json_encode($disp));
    return $disp;
}

function getReservas($conn, $filtros = []) {
    $sql = "
        SELECT Id_Reserva, Nombre, Telefono, Personas, 
               CONVERT(VARCHAR(10), Fecha, 103) as FechaFmt,  -- ✅ dd/MM/yyyy
               LEFT(Hora, 5) as Hora, 
               Estado, Notas, created_at
        FROM Reservas 
        WHERE 1=1
    ";
    $params = [];

    if (!empty($filtros['fecha'])) {
        $sql .= " AND CONVERT(DATE, Fecha) = ?";
        $params[] = $filtros['fecha'];
    }
    if (!empty($filtros['estado'])) {
        $sql .= " AND Estado = ?";
        $params[] = $filtros['estado'];
    }
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (Nombre LIKE ? OR Telefono LIKE ?)";
        $busqueda = "%{$filtros['busqueda']}%";
        $params[] = $busqueda;
        $params[] = $busqueda;
    }

    $sql .= " ORDER BY Fecha DESC, Hora ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function statsReservas($conn) {
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN Estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN Estado = 'Confirmada' THEN 1 ELSE 0 END) as confirmadas,
            SUM(CASE WHEN CONVERT(DATE, Fecha) = CONVERT(DATE, GETDATE()) THEN 1 ELSE 0 END) as hoy
        FROM Reservas
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'pendientes' => 0, 'confirmadas' => 0, 'hoy' => 0];
}
?>
