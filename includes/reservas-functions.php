<?php
/**
 * reservas-functions.php - SQL Server + Tabla 'Reservas' CORREGIDO + DEBUG
 */

function getDisponibilidades($conn, $fecha) {
    $sql = "
        SELECT Hora, Personas, Nombre, Telefono, Estado 
        FROM Reservas 
        WHERE CONVERT(DATE, Fecha) = CONVERT(DATE, :fecha)
        AND Estado IN ('Pendiente', 'Confirmada')
        AND LEN(LTRIM(RTRIM(Hora))) = 5
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
    $hora_limpia = trim($hora);
    return count(array_filter($disponibilidades, function($disp) use ($hora_limpia) {
        return trim($disp['hora']) === $hora_limpia;
    }));
}

function validarReserva($conn, $fecha, $hora) {
    $disponibilidades = getDisponibilidades($conn, $fecha);
    return contarReservasPorHora($disponibilidades, $hora) === 0;
}

function guardarReserva($conn, $datos) {
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
        'hora' => trim($datos['hora']),
        'duracion' => 90,
        'notas' => $datos['notas'] ?? null
    ]);
}

function estaHorarioOcupado($disponibilidades, $hora) {
    return contarReservasPorHora($disponibilidades, $hora) >= 1;
}

function getReservasRecientes($conn, $limit = 10) {
    $sql = "SELECT TOP " . (int)$limit . " Id_Reserva, Nombre, Telefono, Fecha, Hora, Personas, Estado 
            FROM Reservas 
            WHERE Estado IN ('Pendiente', 'Confirmada')
            ORDER BY Fecha DESC, Hora DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReservas($conn, $filtros = []) {
    error_log("🔍 DEBUG getReservas - Filtros: " . json_encode($filtros));
    
    $sql = "
        SELECT 
            Id_Reserva, Nombre, Telefono, Personas, 
            Fecha, Hora, Estado, Notas
        FROM Reservas 
        WHERE 1=1
    ";
    
    $params = [];

    if (!empty($filtros['fecha'])) {
        $sql .= " AND CONVERT(DATE, Fecha) = :fecha";
        $params['fecha'] = $filtros['fecha'];
    }
    if (!empty($filtros['estado'])) {
        $sql .= " AND Estado = :estado";
        $params['estado'] = $filtros['estado'];
    }
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (Nombre LIKE :busqueda OR Telefono LIKE :busqueda)";
        $params['busqueda'] = "%{$filtros['busqueda']}%";
    }

    $sql .= " ORDER BY Fecha DESC, Hora ASC";
    
    error_log("🔍 DEBUG SQL: " . $sql);
    error_log("🔍 DEBUG Params: " . json_encode($params));

    $stmt = $conn->prepare($sql);
    $success = $stmt->execute($params);
    
    if (!$success) {
        error_log("🔍 DEBUG ERROR execute: " . print_r($stmt->errorInfo(), true));
    }
    
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("🔍 DEBUG Reservas encontradas: " . count($reservas));
    
    // Formateo
    foreach ($reservas as &$reserva) {
        $reserva['FechaFmt'] = !empty($reserva['Fecha']) ? 
            date('d/m/Y', strtotime($reserva['Fecha'])) : 'Sin fecha';
        $reserva['Hora'] = !empty($reserva['Hora']) ? 
            trim(substr($reserva['Hora'], 0, 5)) : '00:00';
    }
    
    error_log("🔍 DEBUG Reservas formateadas: " . count($reservas));
    return $reservas;
}

// ✅ NUEVA FUNCIÓN OPTIMIZADA SQL Server
function statsReservas($conn) {
    error_log("🔍 DEBUG statsReservas INICIADO");
    
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN Estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN Estado = 'Confirmada' THEN 1 ELSE 0 END) as confirmadas,
            SUM(CASE WHEN CONVERT(DATE, Fecha) = CAST(GETDATE() AS DATE) THEN 1 ELSE 0 END) as hoy
        FROM Reservas
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats = [
        'total' => (int)($result['total'] ?? 0),
        'pendientes' => (int)($result['pendientes'] ?? 0),
        'confirmadas' => (int)($result['confirmadas'] ?? 0),
        'hoy' => (int)($result['hoy'] ?? 0)
    ];
    
    error_log("🔍 DEBUG statsReservas FINAL: " . json_encode($stats));
    return $stats;
}

function actualizarEstadoReserva($conn, $id, $estado) {
    error_log("🔍 DEBUG actualizarEstadoReserva: ID=$id, Estado=$estado");
    $sql = "UPDATE Reservas SET Estado = ? WHERE Id_Reserva = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$estado, (int)$id]);
    error_log("🔍 DEBUG Result: " . ($result ? 'OK' : 'FAIL'));
    return $result;
}

function eliminarReserva($conn, $id) {
    error_log("🔍 DEBUG eliminarReserva: ID=$id");
    $sql = "DELETE FROM Reservas WHERE Id_Reserva = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([(int)$id]);
    error_log("🔍 DEBUG Result: " . ($result ? 'OK' : 'FAIL'));
    return $result;
}

function debugTablaReservas($conn) {
    error_log("🔍 DEBUG - Tablas:");
    $stmt = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%reserva%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("🔍 DEBUG Tablas encontradas: " . json_encode($tables));
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM Reservas");
    $total = $stmt->fetchColumn();
    error_log("🔍 DEBUG Total Reservas: $total");
    
    $stmt = $conn->query("SELECT TOP 3 * FROM Reservas ORDER BY Id_Reserva DESC");
    error_log("🔍 DEBUG Últimas 3 reservas: " . json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)));
}


function insertarProductosPrueba($conn) {
    // Solo insertar si no hay productos
    $result = $conn->query("SELECT COUNT(*) as total FROM Productos");
    if ($result && $result->fetch_assoc()['total'] == 0) {
        
        $productos = [
            ['Americano', 'Café de origen 100% arábica', 35.00, 'americano.jpg'],
            ['Latte', 'Leche vaporizada con espresso doble', 45.00, 'latte.jpg'],
            ['Capuccino', 'Espresso, leche y espuma perfecta', 42.00, 'capuccino.jpg'],
            ['Croissant', 'Hojaldre francés recién horneado', 28.00, 'croissant.jpg'],
            ['Pan Integral', 'Pan artesanal con semillas', 25.00, 'pan-integral.jpg'],
            ['Muffin Chocolate', 'Muffin esponjoso con chips', 32.00, 'muffin.jpg'],
            ['Tostada Francesa', 'Pan brioche con caramelo', 48.00, 'tostada-francesa.jpg'],
            ['Jugo Natural', 'Naranja fresca exprimida', 38.00, 'jugo-naranja.jpg']
        ];
        
        $stmt = $conn->prepare("INSERT INTO Productos (Nombre, Descripcion, Precio_Venta, Imagen) VALUES (?, ?, ?, ?)");
        foreach ($productos as $p) {
            $stmt->bind_param("ssds", $p[0], $p[1], $p[2], $p[3]);
            $stmt->execute();
        }
        echo "<!-- ✅ Insertados " . count($productos) . " productos de prueba -->";
    }
}

function getProductos($conn, $busqueda = '', $limit = 20) {
    $busqueda = trim($busqueda);
    $where = $busqueda ? "WHERE Nombre LIKE ? OR Descripcion LIKE ?" : "";
    $params = $busqueda ? ["%$busqueda%", "%$busqueda%"] : [];
    
    $sql = "SELECT TOP $limit 
                Id_Producto as id,
                Nombre, 
                Descripcion, 
                Precio_Venta as precio,
                Imagen 
            FROM Productos 
            $where 
            ORDER BY Id_Producto ASC";
    
    $stmt = $conn->prepare($sql);
    
    if ($busqueda) {
        $stmt->bind_param("ss", ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    return $productos;
}
?>
