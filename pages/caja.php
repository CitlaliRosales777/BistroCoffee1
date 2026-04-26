<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Cajero']);

// Carrito session
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Acciones POST
if ($_POST) {
    if (isset($_POST['agregar'])) {
        $id = (int)$_POST['producto_id'];
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
        
        $producto = db_fetch_one($conn, "SELECT Id_Producto, Nombre, Precio_Venta FROM Productos WHERE Id_Producto = ? AND Precio_Venta > 0", [$id]);
        
        if ($producto) {
            // Sumar si ya existe
            $existe = false;
            foreach ($_SESSION['carrito'] as &$item) {
                if ($item['id'] == $id) {
                    $item['cantidad'] += $cantidad;
                    $item['subtotal'] = $item['precio'] * $item['cantidad'];
                    $existe = true;
                    break;
                }
            }
            
            if (!$existe) {
                $_SESSION['carrito'][] = [
                    'id' => $producto['Id_Producto'],
                    'nombre' => $producto['Nombre'],
                    'precio' => (float)$producto['Precio_Venta'],
                    'cantidad' => $cantidad,
                    'subtotal' => $producto['Precio_Venta'] * $cantidad
                ];
            }
            $_SESSION['mensaje'] = "✅ Agregado: {$producto['Nombre']} x{$cantidad}";
        }
        
    } elseif (isset($_POST['quitar_item'])) {
        $index = (int)$_POST['index'];
        if (isset($_SESSION['carrito'][$index])) {
            $nombre = $_SESSION['carrito'][$index]['nombre'];
            unset($_SESSION['carrito'][$index]);
            $_SESSION['carrito'] = array_values($_SESSION['carrito']); // Reindexar
            $_SESSION['mensaje'] = "🗑️ Removido: $nombre";
        }
        
    } elseif (isset($_POST['limpiar_carrito'])) {
        $_SESSION['carrito'] = [];
        $_SESSION['mensaje'] = "🛒 Carrito vaciado";
        
    } elseif (isset($_POST['procesar_venta'])) {
        if (!empty($_SESSION['carrito'])) {
            try {
                $total = array_sum(array_column($_SESSION['carrito'], 'subtotal'));
                $propina = (float)($_POST['propina'] ?? 0);
                $totalFinal = $total + $propina;
                $metodoPago = $_POST['metodo_pago'] ?? 'Efectivo';
                $moneda = $_POST['moneda'] ?? 'MXN';
                
                // 🔥 VENTA SIMPLE EN TABLA AUXILIAR (SIN FK)
                $productosJSON = json_encode($_SESSION['carrito'], JSON_UNESCAPED_UNICODE);
                $cajero = $usuario['nombre'] ?? $usuario['username'] ?? 'Cajero';
                
                $stmt = $conn->prepare("
                    INSERT INTO Ventas_Caja (Cajero, Total, Propina, Metodo_Pago, Moneda, Productos) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$cajero, $total, $propina, $metodoPago, $moneda, $productosJSON]);
                
                $ventaId = $conn->lastInsertId();
                $_SESSION['carrito'] = [];
                $_SESSION['mensaje'] = "✅ Venta #$ventaId procesada!<br>Total: $$totalFinal $moneda<br>🧾 Guardada en Ventas_Caja";
                
            } catch (Exception $e) {
                $_SESSION['mensaje'] = "❌ Error: " . $e->getMessage();
            }
        }
    }
}

// Cálculos
$totalCarrito = array_sum(array_column($_SESSION['carrito'], 'subtotal'));
$propinaSession = $_POST['propina'] ?? 0;
$monedaSession = $_POST['moneda'] ?? 'MXN';
$totalFinal = $totalCarrito + (float)$propinaSession;

// 🔥 PRODUCTOS (CORREGIDO PARA TU TABLA)
$productos = db_fetch_all($conn, "
    SELECT 
        Id_Producto as id, 
        Nombre as nombre, 
        Precio_Venta as precio,
        CASE 
            WHEN LOWER(Nombre) LIKE '%café%' OR LOWER(Nombre) LIKE '%latte%' THEN 'café'
            WHEN LOWER(Nombre) LIKE '%pancake%' OR LOWER(Nombre) LIKE '%hot cake%' OR LOWER(Nombre) LIKE '%croissant%' THEN 'desayuno'
            WHEN LOWER(Nombre) LIKE '%filete%' THEN 'platos'
            WHEN LOWER(Nombre) LIKE '%tiramisú%' THEN 'postres'
            ELSE 'otros'
        END as categoria
    FROM Productos 
    WHERE Precio_Venta > 0
    ORDER BY Nombre ASC
");
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja - Bistro Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #F4A261; --success: #2A9D8F; --danger: #E76F51; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .caja-container { max-width: 1600px; margin: 0 auto; padding: 20px; }
        .producto-btn { height: 110px; border-radius: 15px; font-size: 13px; transition: all 0.3s; border: 2px solid #eee; }
        .producto-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); border-color: var(--primary); }
        .carrito-item { border-left: 4px solid var(--primary); }
        .total-display { font-size: 2.5rem; font-weight: 800; }
        .keyboard { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .tecla { height: 55px; border-radius: 12px; font-size: 18px; font-weight: 700; }
        .buscador-input { border-radius: 25px; }
        .btn-accion-carrito { opacity: 0; transition: all 0.2s; }
        .carrito-item:hover .btn-accion-carrito { opacity: 1; }
    </style>
</head>
<body>
    <div class="caja-container">
        <!-- HEADER -->
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h1 class="text-white mb-2">
                    <i class="fas fa-cash-register fa-2x me-3"></i>
                    Caja Registradora (<?= count($productos) ?> productos)
                </h1>
                <p class="text-white-50 mb-0 fs-6">
                    Cajero: <strong><?= htmlspecialchars($usuario['nombre'] ?? $usuario['username'] ?? 'Cajero') ?></strong>
                    | Ticket #<?= date('Ymd-His') ?>
                </p>
            </div>
            <div class="col-md-6 text-end">
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-pill shadow mb-3" role="alert">
                        <?= htmlspecialchars($_SESSION['mensaje']) ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['mensaje']); ?>
                <?php endif; ?>
                <div class="mt-2">
                    <a href="caja-debug.php" class="btn btn-outline-warning btn-sm me-2">
                        <i class="fas fa-bug"></i> Debug
                    </a>
                    <a href="../admin/index.php" class="btn btn-outline-light btn-sm me-2">
                        <i class="fas fa-home"></i> Admin
                    </a>
                    <a href="../logout.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Cerrar
                    </a>
                </div>
            </div>
        </div>
                <div class="row g-4">
            <!-- PRODUCTOS -->
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center p-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-utensils me-2"></i>
                            Productos (<?= count($productos) ?> disponibles)
                        </h6>
                        <div class="d-flex gap-2">
                            <select name="categoria" form="formProductos" class="form-select form-select-sm" style="width: 140px;">
                                <option value="">Todas</option>
                                <option value="café">Café</option>
                                <option value="desayuno">Desayuno</option>
                                <option value="platos">Platos</option>
                                <option value="postres">Postres</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Buscador -->
                        <form id="formProductos" method="GET" class="p-3 border-bottom">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-magnifying-glass text-muted"></i>
                                </span>
                                <input type="text" name="buscar" class="form-control border-start-0 fs-6 buscador-input py-2" 
                                       placeholder="🔍 Buscar café, pancakes..." 
                                       value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Productos filtrados -->
                        <div class="p-3">
                            <?php
                            $busqueda = strtolower(trim($_GET['buscar'] ?? ''));
                            $categoria = strtolower(trim($_GET['categoria'] ?? ''));
                            
                            $productosFiltrados = $productos;
                            if ($busqueda) {
                                $productosFiltrados = array_filter($productos, fn($p) => stripos($p['nombre'], $busqueda) !== false);
                            }
                            if ($categoria) {
                                $productosFiltrados = array_filter($productosFiltrados, fn($p) => stripos($p['categoria'], $categoria) !== false);
                            }
                            ?>
                            
                            <?php if (empty($productosFiltrados)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-utensils fa-3x mb-4 opacity-50"></i>
                                    <h5>No se encontraron productos</h5>
                                </div>
                            <?php else: ?>
                                <div class="row g-2 g-md-3">
                                    <?php foreach (array_slice(array_values($productosFiltrados), 0, 24) as $producto): ?>
                                        <div class="col-6 col-sm-4 col-md-3 col-lg-3 col-xl-2">
                                            <form method="POST" class="h-100">
                                                <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                                                <input type="hidden" name="cantidad" value="1">
                                                <button type="submit" name="agregar" class="btn btn-outline-primary w-100 h-100 p-2 rounded-3 shadow-sm producto-btn">
                                                    <div class="fw-bold mb-1 text-truncate d-block" style="font-size: 13px; line-height: 1.2;">
                                                        <?= htmlspecialchars($producto['nombre']) ?>
                                                    </div>
                                                    <div class="text-success fw-bold fs-5">$<?= number_format($producto['precio'], 0) ?></div>
                                                    <small class="badge bg-light text-dark"><?= htmlspecialchars($producto['categoria']) ?></small>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
                        <!-- CARRITO -->
            <div class="col-lg-4">
                <div class="card shadow-xl border-0 h-100" style="border-radius: 20px;">
                    <div class="card-header bg-success text-white rounded-top p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= count($_SESSION['carrito']) ?>)</span>
                            <?php if (!empty($_SESSION['carrito'])): ?>
                            <form method="POST" style="display: inline;">
                                <button name="limpiar_carrito" class="btn btn-sm btn-outline-light rounded-pill px-3" 
                                        onclick="return confirm('¿Vaciar carrito?')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0 flex-grow-1 d-flex flex-column">
                        <!-- Items del carrito -->
                        <div class="flex-grow-1 p-3" style="overflow-y: auto; max-height: 350px;">
                            <?php if (empty($_SESSION['carrito'])): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-50"></i>
                                    <p class="fs-5">Carrito vacío</p>
                                    <small>Selecciona productos</small>
                                </div>
                            <?php else: ?>
                                <?php foreach($_SESSION['carrito'] as $i => $item): ?>
                                <div class="list-group-item carrito-item px-3 py-3 mb-2 rounded shadow-sm">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <small class="text-muted fw-semibold"><?= htmlspecialchars($item['nombre']) ?></small>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Quitar <?= htmlspecialchars($item['nombre']) ?>?')">
                                            <input type="hidden" name="index" value="<?= $i ?>">
                                            <button type="submit" name="quitar_item" class="btn btn-sm btn-outline-danger rounded-circle p-1 btn-accion-carrito">
                                                <i class="fas fa-times fs-6"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary fs-6 px-2 py-1">
                                            <?= $item['cantidad'] ?> x $<span class="small"><?= number_format($item['precio'], 0) ?></span>
                                        </span>
                                        <span class="badge bg-success fs-6 px-3 py-1 fw-bold">
                                            $<span id="subtotal-<?= $i ?>"><?= number_format($item['subtotal'], 2) ?></span>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <!-- TOTALES + FORM PAGO -->
                       <div class="p-4 border-top bg-light rounded-bottom" style="border-radius: 0 0 20px 20px;">
    <form method="POST" id="formPago">
        <input type="hidden" name="procesar_venta" value="1">
        
        <!-- PROPINA + MONEDA -->
        <div class="row g-2 mb-4">
            <div class="col-7">
                <label class="form-label fw-bold small text-muted mb-1">Propina:</label>
                <input type="number" name="propina" class="form-control form-control-lg" 
                       value="<?= htmlspecialchars($propinaSession) ?>" min="0" step="0.01" required>
            </div>
            <div class="col-5 align-self-end">
                <label class="form-label fw-bold small text-muted mb-1 d-block">Moneda:</label>
                <select name="moneda" class="form-select form-select-lg" required>
                    <option value="MXN" <?= $monedaSession=='MXN'?'selected':'' ?>>MXN</option>
                    <option value="USD" <?= $monedaSession=='USD'?'selected':'' ?>>USD</option>
                </select>
            </div>
        </div>
        
        <!-- SUBTOTAL -->
        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm">
            <span class="fs-4 fw-bold text-muted">SUBTOTAL:</span>
            <span class="fs-3 fw-bold text-primary">$<?= number_format($totalCarrito, 2) ?></span>
        </div>
        
        <!-- TOTAL FINAL -->
        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-gradient text-white rounded shadow-lg" 
             style="background: linear-gradient(135deg, var(--success), #219653); border-radius: 15px;">
            <span class="fs-2 fw-bold">TOTAL:</span>
            <span class="fs-1 fw-bold">$<?= number_format($totalFinal, 2) ?> <?= strtoupper($monedaSession) ?></span>
        </div>
        
        <!-- MÉTODO PAGO -->
        <div class="mb-4">
            <label class="form-label fw-bold mb-2">Método de Pago:</label>
            <select name="metodo_pago" class="form-select form-select-lg" required>
                <option value="Efectivo">💵 Efectivo</option>
                <option value="Tarjeta">💳 Tarjeta</option>
                <option value="Transferencia">📱 Transferencia</option>
                <option value="Mixto">💳💵 Mixto</option>
            </select>
        </div>
        
        <!-- ✅ BOTONES CORREGIDOS -->
        <!-- BOTÓN PROCESAR -->
        <button type="submit" class="btn btn-success w-100 py-4 fs-4 fw-bold rounded-3 shadow-lg <?= empty($_SESSION['carrito']) ? 'disabled opacity-50' : '' ?>"
                style="background: linear-gradient(135deg, var(--success), #219653); border: none;">
            <i class="fas fa-check-circle me-3"></i>
            <span class="me-3">PROCESAR VENTA</span>
            <span class="badge bg-light text-success fs-6 px-3 py-2 rounded-pill">ENTER</span>
        </button>

        <!-- BOTÓN IMPRIMIR TICKET -->
        <?php if (!empty($_SESSION['carrito'])): ?>
        <div class="mt-3">
            <button type="button" onclick="imprimirTicket()" class="btn btn-warning w-100 py-3 fs-5 fw-bold rounded-3 shadow-lg">
                <i class="fas fa-print me-3"></i>
                <span>🖨️ IMPRIMIR TICKET</span>
            </button>
        </div>
        <?php endif; ?>  <!-- ⭐ CLAVE: Este endif cierra el if -->
        
    </form>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Fin row principal -->
                <!-- TECLADO NUMÉRICO -->
        <div class="card shadow-lg mt-4 border-0 rounded-3 overflow-hidden">
            <div class="card-header bg-dark text-white p-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-keyboard me-2"></i>Teclado Rápido</h6>
            </div>
            <div class="card-body p-4">
                <div class="keyboard">
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(7)">7</button>
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(8)">8</button>
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(9)">9</button>
                    <button class="tecla btn btn-outline-primary fw-bold" onclick="setCantidad(1)">×1</button>
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(4)">4</button>
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(5)">5</button>
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(6)">6</button>
                    <button class="tecla btn btn-outline-primary fw-bold" onclick="setCantidad(2)">×2</button>
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(1)">1</button>
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(2)">2</button>
                    <button class="tecla btn btn-outline-secondary" onclick="addCantidad(3)">3</button>
                    <button class="tecla btn btn-outline-primary fw-bold" onclick="setCantidad(3)">×3</button>
                    <button class="tecla btn btn-outline-secondary fw-bold" onclick="addCantidad(0)">0</button>
                    <button class="tecla btn btn-warning fw-bold" onclick="limpiarCantidad()">C</button>
                    <button class="tecla btn btn-success fw-bold fs-5" onclick="agregarPrimero()">+</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
     let modalTicketInst = null;

function imprimirTicket() {
    console.log('🖨️ Imprimiendo ticket...');
    
    if (<?= json_encode(empty($_SESSION['carrito'])) ?>) {
        alert('❌ Carrito vacío');
        return;
    }
    
    // Crear form POST invisible
    const form = document.createElement('form');
    form.method = 'POST';
    form.target = '_blank';
    form.action = 'impresion-ticket.php';
    form.style.display = 'none';
    
    // Datos del ticket
    const datosTicket = {
        items: <?= json_encode($_SESSION['carrito']) ?>,
        subtotal: <?= $totalCarrito ?>,
        propina: parseFloat(document.querySelector('input[name="propina"]')?.value || 0),
        total: <?= $totalFinal ?>,
        moneda: document.querySelector('select[name="moneda"]')?.value || '<?= $monedaSession ?>',
        metodoPago: document.querySelector('select[name="metodo_pago"]')?.value || 'Efectivo',
        cajero: '<?= addslashes($usuario['nombre'] ?? $usuario['username'] ?? 'Cajero') ?>',
        fecha: new Date().toLocaleString('es-MX'),
        ticketId: '<?= date('Ymd-His') ?>'
    };
    
    // Input con datos JSON
    const inputDatos = document.createElement('input');
    inputDatos.name = 'datos';
    inputDatos.value = JSON.stringify(datosTicket);
    form.appendChild(inputDatos);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    console.log('✅ Ticket enviado!', datosTicket);
}

// Teclado numérico (sin cambios)
let cantidadActual = 1;
function addCantidad(num) {
    if (num >= 0 && num <= 9) cantidadActual = cantidadActual * 10 + num;
    document.querySelectorAll('input[name="cantidad"]').forEach(el => el.value = cantidadActual);
}
function setCantidad(num) { 
    cantidadActual = num; 
    document.querySelectorAll('input[name="cantidad"]').forEach(el => el.value = num); 
}
function limpiarCantidad() { 
    cantidadActual = 1; 
    document.querySelectorAll('input[name="cantidad"]').forEach(el => el.value = 1); 
}
function agregarPrimero() { 
    document.querySelector('form input[name="producto_id"]')?.closest('form')?.submit(); 
}

// Tus funciones de teclado existentes

function setCantidad(num) { 
    cantidadActual = num; 
    document.querySelectorAll('input[name="cantidad"]').forEach(el => el.value = num); 
}
function limpiarCantidad() { 
    cantidadActual = 1; 
    document.querySelectorAll('input[name="cantidad"]').forEach(el => el.value = 1); 
}
function agregarPrimero() { 
    document.querySelector('form input[name="producto_id"]')?.closest('form')?.submit(); 
}
    
function addCantidad(num) {
    if (num >= 0 && num <= 9) cantidadActual = cantidadActual * 10 + num;
    document.querySelectorAll('input[name="cantidad"]').forEach(el => el.value = cantidadActual);
}
function setCantidad(num) { 
    cantidadActual = num; 
    document.querySelectorAll('input[name="cantidad"]').forEach(el => el.value = num); 
}
function limpiarCantidad() { 
    cantidadActual = 1; 
    document.querySelectorAll('input[name="cantidad"]').forEach(el => el.value = 1); 
}
function agregarPrimero() { 
    document.querySelector('form input[name="producto_id"]')?.closest('form')?.submit(); 
} 
</script>
    
</body>
</html>