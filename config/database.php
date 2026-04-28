<?php
$serverName = ".\Terrenal777";  // servidor de tu base de datos en el sqlserver
$database = "CoffeBistro"; //NOMBRE EXACTO de la bd que tu usaras en el sqlserver
$username = "Arirang";           // Tu usuario de la bd
$password = "terrenal777";  //contraseña de la base de datos

// Opciones específicas para SQL Server
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Conexión SQL Server
try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=1", $username, $password, $options);
    
    // ✅ Configuración específica SQL Server
    $conn->exec("SET DATEFORMAT ymd");  // Formato fecha
    $conn->exec("SET ANSI_WARNINGS ON");
    $conn->exec("SET QUOTED_IDENTIFIER ON");
    
    define('DB_CONNECTED', true);
    //echo "<!-- SQL Server conectado exitosamente! -->";
    
} catch(PDOException $e) {
    // Si hay error, NO rompe la página (modo desarrollo)
    error_log("Error SQL Server: " . $e->getMessage());
    define('DB_CONNECTED', false);
    //echo "<!-- Error conexión DB - Página sigue funcionando -->";
}

// ============================================================================
// FUNCIONES HELPER PARA SQL SERVER
// ============================================================================

function db_query($conn, $sql, $params = []) {
    if (!DB_CONNECTED) {
        return ['error' => 'DB no disponible'];
    }
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

function db_fetch_all($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("db_fetch_all error: " . $e->getMessage());
        return [];
    }
}

function db_fetch_one($conn, $sql, $params = []) {
    $stmt = db_query($conn, $sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

// ✅ MODO DEBUG - Quita en producción
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "DB Status: " . (DB_CONNECTED ? 'CONECTADO' : 'FALLÓ') . "\n";
    echo "Driver: sqlsrv\n";
    echo "</pre>";
}

?>