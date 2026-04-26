<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Cajero', 'Admin']); // Permite cajeros y admins

// DIAGNÓSTICO COMPLETO
$debugInfo = [];
$errores = [];

// 1. INFO CONEXIÓN
$debugInfo['🔌 Conexión'] = $conn ? '✅ OK' : '❌ FALLÓ';

// 2. TABLA PRODUCTOS
try {
    $totalProductos = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Productos");
    $debugInfo['📊 Total Productos'] = $totalProductos['total'] ?? 0;
    
    $columnas = db_fetch_all($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Productos'");
    $debugInfo['🏗️ Columnas'] = array_column($columnas, 'COLUMN_NAME');
    
} catch (Exception $e) {
    $errores[] = "Tabla Productos: " . $e->getMessage();
}

// 3. PRODUCTOS ACTIVOS
try {
    $activos = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Productos WHERE activo = 1");
    $debugInfo['✅ Activos'] = $activos['total'] ?? 0;
    
    $precios = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Productos WHERE activo = 1 AND Precio_Venta > 0");
    $debugInfo['💰 Con Precio'] = $precios['total'] ?? 0;
} catch (Exception $e) {
    $errores[] = "Consulta activos: " . $e->getMessage();
}

// 4. PRIMEROS 10 PRODUCTOS (CRUDO)
try {
    $primeros = db_fetch_all($conn, "
        SELECT TOP 10 
            Id_Producto, Nombre, Precio_Venta, activo,
            CASE WHEN activo = 1 THEN 'SÍ' ELSE 'NO' END as activo_txt
        FROM Productos 
        ORDER BY Id_Producto
    ");
    $debugInfo['📋 Primeros 10'] = $primeros;
} catch (Exception $e) {
    $errores[] = "Primeros productos: " . $e->getMessage();
}

// 5. CONSULTA FINAL DE CAJA
try {
    $productosCaja = db_fetch_all($conn, "
        SELECT 
            Id_Producto as id, 
            Nombre as nombre, 
            Precio_Venta as precio,
            CASE 
                WHEN LOWER(Nombre) LIKE '%café%' THEN 'café'
                ELSE 'otros'
            END as categoria
        FROM Productos 
        WHERE activo = 1 
          AND Precio_Venta IS NOT NULL 
          AND Precio_Venta > 0
        ORDER BY Nombre
    ");
    $debugInfo['🎯 Productos para Caja'] = count($productosCaja);
} catch (Exception $e) {
    $errores[] = "Consulta caja: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 DEBUG Caja - Bistro Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .debug-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .debug-card { border-left: 5px solid #ffc107; }
        .debug-section { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .debug-key { font-weight: 700; color: #495057; }
        .debug-value { background: #e9ecef; padding: 8px 12px; border-radius: 6px; font-family: monospace; }
        pre { white-space: pre-wrap; word-break: break-all; }
        .btn-copy { position: absolute; top: 10px; right: 10px; }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="row align-items-center mb-4">
            <div class="col">
                <h1 class="text-warning mb-2">
                    <i class="fas fa-bug fa-2x me-3"></i>
                    DEBUG CAJA REGISTRADORA
                </h1>
                <p class="text-muted mb-0">
                    Usuario: <strong><?= htmlspecialchars($usuario['nombre'] ?? $usuario['username']) ?></strong>
                </p>
            </div>
            <div class="col-auto">
                <a href="caja.php" class="btn btn-success">
                    <i class="fas fa-cash-register me-2"></i>Caja
                </a>
                <a href="../admin/index.php" class="btn btn-outline-secondary">Admin</a>
            </div>
        </div>

        <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle me-2"></i>ERRORES:</h6>
            <ul class="mb-0">
                <?php foreach($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <!-- INFO GENERAL -->
                <div class="debug-section debug-card">
                    <h5><i class="fas fa-info-circle text-info me-2"></i>Información General</h5>
                    <?php foreach($debugInfo as $key => $value): ?>
                        <div class="mb-3">
                            <div class="debug-key"><?= htmlspecialchars($key) ?></div>
                            <div class="debug-value"><?= htmlspecialchars(is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-6">
                <!-- ACCIONES RÁPIDAS -->
                <div class="debug-section">
                    <h5><i class="fas fa-tools text-primary me-2"></i>Acciones Rápidas</h5>
                    
                    <div class="mb-3">
                        <button class="btn btn-outline-primary btn-sm me-2" onclick="copiarDebug()">
                            📋 Copiar Todo Debug
                        </button>
                        <a href="caja.php" class="btn btn-success btn-sm">
                            <i class="fas fa-cash-register"></i> Ir a Caja
                        </a>
                    </div>

                    <?php if (($debugInfo['📊 Total Productos'] ?? 0) == 0): ?>
                    <div class="alert alert-warning">
                        <strong>¡TABLA VACÍA!</strong><br>
                        <button class="btn btn-sm btn-danger" onclick="insertarPrueba()">
                            🧪 Insertar Productos de Prueba
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if (($debugInfo['✅ Activos'] ?? 0) == 0): ?>
                    <div class="alert alert-warning">
                        <strong>¡NINGÚN PRODUCTO ACTIVO!</strong><br>
                        <button class="btn btn-sm btn-warning" onclick="activarTodos()">
                            ⚡ Activar Todos
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PRODUCTOS DETALLADOS -->
        <?php if (!empty($debugInfo['📋 Primeros 10'])): ?>
        <div class="debug-section">
            <h5><i class="fas fa-list text-success me-2"></i>Primeros Productos (Tabla)</h5>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($debugInfo['📋 Primeros 10'] as $prod): ?>
                        <tr>
                            <td><strong><?= $prod['Id_Producto'] ?></strong></td>
                            <td><?= htmlspecialchars($prod['Nombre']) ?></td>
                            <td>$<?= number_format($prod['Precio_Venta'] ?? 0, 2) ?></td>
                            <td>
                                <span class="badge <?= $prod['activo'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $prod['activo_txt'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function copiarDebug() {
            const debugText = document.body.innerText;
            navigator.clipboard.writeText(debugText).then(() => {
                alert('✅ Debug copiado al portapapeles');
            });
        }

        function insertarPrueba() {
            if(confirm('¿Insertar 5 productos de prueba?')) {
                window.location.href = 'caja-debug.php?action=insertar_prueba';
            }
        }

        function activarTodos() {
            if(confirm('¿Activar TODOS los productos?')) {
                window.location.href = 'caja-debug.php?action=activar_todos';
            }
        }
    </script>

    <?php
// 🔥 ACCIONES DEBUG CORREGIDAS PARA SQL SERVER
if (isset($_GET['action'])) {
    try {
        if ($_GET['action'] == 'insertar_prueba') {
            // Eliminar existentes primero
            $conn->exec("DELETE FROM Productos WHERE Nombre LIKE 'Café%' OR Nombre LIKE 'Pancakes%'");
            
            // Insertar nuevos
            $conn->exec("
                INSERT INTO Productos (Nombre, Precio_Venta, activo) VALUES
                ('Café Americano', 35.00, 1),
                ('Pancakes', 65.00, 1),
                ('Tacos al Pastor', 85.00, 1),
                ('Cheesecake', 55.00, 1),
                ('Expreso', 45.00, 1)
            ");
            echo "<script>alert('✅ 5 productos de prueba INSERTADOS'); window.location.href='caja-debug.php';</script>";
            exit;
        }
        
        if ($_GET['action'] == 'activar_todos') {
            $actualizados = $conn->exec("UPDATE Productos SET activo = 1 WHERE Precio_Venta IS NOT NULL AND Precio_Venta > 0");
            echo "<script>alert('✅ Productos ACTIVADOS: $actualizados'); window.location.href='caja-debug.php';</script>";
            exit;
        }
        
        if ($_GET['action'] == 'ver_todos') {
            header('Location: caja.php');
            exit;
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>ERROR: " . $e->getMessage() . "</div>";
    }
}
?>
</body>
</html>