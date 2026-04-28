<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Cajero']);

// Carrito session
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// TASA DE CAMBIO (actualizable)
$TASA_USD_MXN = 18.50; // Cambia aquí la tasa actual

// Acciones POST
if ($_POST) {
    if (isset($_POST['agregar'])) {
        $id = (int)$_POST['producto_id'];
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
        
        $producto = db_fetch_one($conn, "SELECT Id_Producto, Nombre, Precio_Venta FROM Productos WHERE Id_Producto = ? AND Precio_Venta > 0", [$id]);
        
        if ($producto) {
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
            $_SESSION['mensaje'] = "Agregado: {$producto['Nombre']} x{$cantidad}";
        }
        
    } elseif (isset($_POST['quitar_item'])) {
        $index = (int)$_POST['index'];
        if (isset($_SESSION['carrito'][$index])) {
            $nombre = $_SESSION['carrito'][$index]['nombre'];
            unset($_SESSION['carrito'][$index]);
            $_SESSION['carrito'] = array_values($_SESSION['carrito']);
            $_SESSION['mensaje'] = "Removido: $nombre";
        }
        
    } elseif (isset($_POST['limpiar_carrito'])) {
        $_SESSION['carrito'] = [];
        $_SESSION['mensaje'] = "Carrito vaciado";
        
    } elseif (isset($_POST['procesar_venta'])) {
        if (!empty($_SESSION['carrito'])) {
            try {
                $total = array_sum(array_column($_SESSION['carrito'], 'subtotal'));
                $propina = (float)($_POST['propina'] ?? 0);
                $totalFinal = $total + $propina;
                $metodoPago = $_POST['metodo_pago'] ?? 'Efectivo';
                $moneda = $_POST['moneda'] ?? 'MXN';
                
                $productosJSON = json_encode($_SESSION['carrito'], JSON_UNESCAPED_UNICODE);
                $cajero = $usuario['nombre'] ?? $usuario['username'] ?? 'Cajero';
                
                $stmt = $conn->prepare("
                    INSERT INTO Ventas_Caja (Cajero, Total, Propina, Metodo_Pago, Moneda, Productos) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$cajero, $total, $propina, $metodoPago, $moneda, $productosJSON]);
                
                $ventaId = $conn->lastInsertId();
                $_SESSION['carrito'] = [];
                $_SESSION['mensaje'] = "✅ Venta #$ventaId procesada!<br>Total: $$totalFinal $moneda";
                
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
        .btn-accion-carrito { opacity: 0; transition: all 0.2s; }
        .carrito-item:hover .btn-accion-carrito { opacity: 1; }
        .buscador-input { border-radius: 25px; }
        
        /* ⭐ CONTROL COMPACTO DE CANTIDAD */
        .control-cantidad-compact {
            position: sticky;
            top: 20px;
            z-index: 10;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .input-cantidad-compact {
            width: 80px;
            height: 50px;
            font-size: 1.4rem;
            font-weight: 700;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            text-align: center;
        }
        .input-cantidad-compact:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(244,162,97,0.25);
        }
        .btn-cant-compact {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        /* DIVISA EN TIEMPO REAL */
        .divisa-control {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            padding: 8px 12px;
        }
        .total-live {
            transition: all 0.3s ease;
            font-size: 2.2rem;
            font-weight: 800;
        }
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
            <!-- ⭐ CONTROL COMPACTO FIJO -->
            <div class="col-12">
                <div class="control-cantidad-compact">
                    <div class="row align-items-center g-2">
                        <div class="col-auto">
                            <label class="mb-1 small text-muted fw-bold">Cant:</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="cantidadGlobal" class="form-control input-cantidad-compact" 
                                       value="1" min="1" max="99">
                                <button class="btn btn-outline-secondary btn-cant-compact" onclick="setCant(1)">1</button>
                                <button class="btn btn-outline-primary btn-cant-compact" onclick="setCant(2)">2</button>
                                <button class="btn btn-outline-primary btn-cant-compact" onclick="setCant(3)">3</button>
                                <button class="btn btn-outline-success btn-cant-compact" onclick="setCant(5)">5</button>
                                <button class="btn btn-outline-danger btn-cant-compact" onclick="clearCant()">C</button>
                            </div>
                        </div>
                        <div class="col-auto ms-auto">
                            <small class="text-muted">Tasa USD/MXN: <strong id="tasaDisplay"><?= $TASA_USD_MXN ?></strong></small>
                        </div>
                    </div>
                </div>
            </div>

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
                        
                        <!-- Productos -->
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
                                                <input type="hidden" name="cantidad" id="cant-<?= $producto['id'] ?>" value="1">
                                                <button type="submit" name="agregar" class="btn btn-outline-primary w-100 h-100 p-2 rounded-3 shadow-sm producto-btn"
                                                        title="Cantidad actual: 1 | Cambia arriba 👆">
                                                    <div class="fw-bold mb-1 text-truncate d-block" style="font-size: 13px; line-height: 1.2;">
                                                        <?= htmlspecialchars($producto['nombre']) ?>
                                                    </div>
                                                    <div class="text-success fw-bold fs-5" id="precio-<?= $producto['id'] ?>">
                                                        $<span class="precio-val"><?= number_format($producto['precio'], 0) ?></span>
                                                    </div>
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
            
            <!-- CARRITO MEJORADO -->
            <div class="col-lg-4">
                <div class="card shadow-xl border-0 h-100" style="border-radius: 20px;">
                    <div class="card-header bg-success text-white rounded-top p-3 position-relative">
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
                        
                        <!-- ⭐ CONTROL DE DIVISA EN CARRITO -->
                        <div class="divisa-control mt-2 p-2">
                            <div class="row align-items-center g-2">
                                <div class="col-auto">
                                    <label class="mb-0 small fw-bold text-white">Divisa:</label>
                                </div>
                                <div class="col-auto">
                                    <select id="monedaSelect" class="form-select form-select-sm">
                                        <option value="MXN">MXN</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <input type="number" id="propinaInput" class="form-control form-control-sm" 
                                           placeholder="Propina" style="width: 90px;" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-0 flex-grow-1 d-flex flex-column">
                        <!-- Items -->
                        <div class="flex-grow-1 p-3" style="overflow-y: auto; max-height: 320px;">
                            <?php if (empty($_SESSION['carrito'])): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-50"></i>
                                    <p class="fs-5">Carrito vacío</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($_SESSION['carrito'] as $i => $item): ?>
                                <div class="list-group-item carrito-item px-3 py-2 mb-2 rounded shadow-sm">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <small class="text-muted fw-semibold"><?= htmlspecialchars($item['nombre']) ?></small>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Quitar?')">
                                            <input type="hidden" name="index" value="<?= $i ?>">
                                            <button type="submit" name="quitar_item" class="btn btn-sm btn-outline-danger rounded-circle p-1 btn-accion-carrito">
                                                <i class="fas fa-times fs-6"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary fs-6 px-2"><?= $item['cantidad'] ?>x</span>
                                        <span class="fw-bold small text-muted" id="item-subtotal-<?= $i ?>">
                                            $<span class="sub-val"><?= number_format($item['subtotal'], 2) ?></span>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- TOTALES LIVE -->
                        <div class="p-4 border-top bg-light">
                            <div class="divisa-control mb-3">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Subtotal:</small><br>
                                        <span class="total-live fw-bold fs-4" id="subtotalLive">$0.00</span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <small class="text-muted">Total c/ propina:</small><br>
                                        <span class="total-live fw-bold fs-2 text-success" id="totalLive">$0.00</span>
                                        <small class="d-block" id="monedaLive">MXN</small>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" id="formPago">
                                <input type="hidden" name="procesar_venta" value="1">
                                <input type="hidden" name="moneda" id="monedaHidden">
                                <input type="hidden" name="propina" id="propinaHidden">
                                
                                <select name="metodo_pago" class="form-select form-select-lg mb-3" required>
                                    <option value="Efectivo">💵 Efectivo</option>
                                    <option value="Tarjeta">💳 Tarjeta</option>
                                    <option value="Transferencia">📱 Transferencia</option>
                                    <option value="Mixto">💳💵 Mixto</option>
                                </select>
                                
                                <button type="submit" class="btn btn-success w-100 py-3 fs-5 fw-bold rounded-3 shadow-lg"
                                        id="btnProcesar" <?= empty($_SESSION['carrito']) ? 'disabled' : '' ?>>
                                    <i class="fas fa-check-circle me-2"></i> PROCESAR VENTA
                                </button>
                            </form>
                            
                            <?php if (!empty($_SESSION['carrito'])): ?>
                            <!-- Reemplaza el botón IMPRIMIR existente en el carrito -->
<div class="mt-2">
    <button type="button" onclick="previewTicket()" class="btn btn-info w-100 py-2 fs-6 fw-bold rounded-3 shadow me-1">
        <i class="fas fa-eye me-2"></i> 👁️ PREVIEW TICKET
    </button>
    <button type="button" onclick="imprimirTicket()" class="btn btn-warning w-100 py-2 fs-6 fw-bold rounded-3 shadow">
        <i class="fas fa-print me-2"></i> 🖨️ IMPRIMIR DIRECTO
    </button>
</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <script>
// 🔥 DATOS GLOBALES
const TASA_USD_MXN = <?= $TASA_USD_MXN ?>;
const itemsCarrito = <?= json_encode($_SESSION['carrito']) ?>;
let monedaActual = 'MXN';

// ⭐ CONTROL DE CANTIDAD (sin cambios)
function setCant(num) {
    document.getElementById('cantidadGlobal').value = num;
    syncCantidad();
}

function clearCant() {
    document.getElementById('cantidadGlobal').value = 1;
    syncCantidad();
}

function syncCantidad() {
    const cant = parseInt(document.getElementById('cantidadGlobal').value) || 1;
    document.querySelectorAll('input[name="cantidad"]').forEach(input => {
        input.value = cant;
    });
}

// ⭐ EVENT LISTENERS
document.addEventListener('DOMContentLoaded', function() {
    const monedaSelect = document.getElementById('monedaSelect');
    const propinaInput = document.getElementById('propinaInput');
    
    // Inicializar
    syncCantidad();
    monedaSelect.value = '<?= $monedaSession ?>';
    propinaInput.value = '<?= $propinaSession ?>';
    
    // Cargar precios originales
    document.querySelectorAll('.precio-val').forEach(el => {
        el.dataset.precioMxn = el.textContent.replace(/[^\d.]/g, '');
    });
    
    // Listeners
    monedaSelect.addEventListener('change', function() {
        monedaActual = this.value;
        convertirPropinaAlCambio();
        actualizarDivisa();
    });
    
    propinaInput.addEventListener('input', function() {
        // ✅ LIBRE: permite cualquier número (sin restricciones)
        let valor = this.value.replace(/[^0-9.]/g, '');
        if (valor.split('.').length > 2) {
            valor = valor.replace(/\.+$/, '');
        }
        this.value = valor;
        actualizarTotales();
    });
    
    propinaInput.addEventListener('blur', function() {
        if (this.value === '') this.value = '0.00';
    });
});

// ⭐ PROPINA: CONVERSIÓN AL CAMBIAR DIVISA
function convertirPropinaAlCambio() {
    const propinaInput = document.getElementById('propinaInput');
    let propinaActual = parseFloat(propinaInput.value) || 0;
    
    if (propinaActual > 0) {
        const factorAnterior = monedaActual === 'USD' ? TASA_USD_MXN : (1 / TASA_USD_MXN);
        const propinaMxn = propinaActual * factorAnterior;
        const nuevaPropina = propinaMxn * (monedaActual === 'USD' ? (1 / TASA_USD_MXN) : 1);
        
        propinaInput.value = nuevaPropina.toFixed(2);
    }
}

// 🔥 ACTUALIZAR DIVISA (precios solamente)
function actualizarDivisa() {
    const factor = monedaActual === 'USD' ? (1 / TASA_USD_MXN) : 1;
    
    // Precios productos
    document.querySelectorAll('.precio-val').forEach(el => {
        const precioMxn = parseFloat(el.dataset.precioMxn);
        el.textContent = (precioMxn * factor).toFixed(2);
    });

    // Items del carrito
    itemsCarrito.forEach((item, i) => {
        const subtotalMxn = item.subtotal;
        const subtotalNew = (subtotalMxn * factor).toFixed(2);
        const el = document.getElementById(`item-subtotal-${i}`);
        if (el) el.querySelector('.sub-val').textContent = subtotalNew;
    });

    actualizarTotales();
}

// ⭐ TOTALES (propina LIBRE en moneda actual)
function actualizarTotales() {
    const subtotalMxn = itemsCarrito.reduce((sum, item) => sum + parseFloat(item.subtotal), 0);
    const factor = monedaActual === 'USD' ? (1 / TASA_USD_MXN) : 1;
    
    // Subtotal en moneda actual
    const subtotalActual = subtotalMxn * factor;
    
    // ✅ PROPINA LIBRE: lo que escribas es lo que vale
    const propinaActual = parseFloat(document.getElementById('propinaInput').value) || 0;
    
    // Total
    const totalActual = subtotalActual + propinaActual;
    
    // Mostrar
    document.getElementById('subtotalLive').textContent = `$${subtotalActual.toFixed(2)}`;
    document.getElementById('totalLive').textContent = `$${totalActual.toFixed(2)}`;
    document.getElementById('monedaLive').textContent = monedaActual;
    
    // Para el form (guardar propina en MXN)
    document.getElementById('propinaHidden').value = (propinaActual / factor).toFixed(2);
    document.getElementById('monedaHidden').value = monedaActual;
    
    // Animación
    const totalEl = document.getElementById('totalLive');
    totalEl.style.transform = 'scale(1.05)';
    totalEl.style.color = '#28a745';
    setTimeout(() => {
        totalEl.style.transform = 'scale(1)';
        totalEl.style.color = '';
    }, 300);
}

// ⭐ TECLADO PARA PROPINA (opcional - bonus)
document.addEventListener('keydown', function(e) {
    if (e.target.id === 'propinaInput') {
        // Permitir: números, punto, backspace, delete, tab, escape, enter
        if (e.key === 'Enter') {
            e.target.blur();
        }
    }
});

function imprimirTicket() {
    if (itemsCarrito.length === 0) return alert('❌ Carrito vacío');
    
    const subtotalMxn = itemsCarrito.reduce((sum, item) => sum + parseFloat(item.subtotal), 0);
    const factor = monedaActual === 'USD' ? (1 / TASA_USD_MXN) : 1;
    const propinaActual = parseFloat(document.getElementById('propinaInput').value) || 0;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.target = '_blank';
    form.action = 'impresion-ticket.php';
    form.style.display = 'none';
    
    const datosTicket = {
        items: itemsCarrito.map(item => ({
            ...item,
            precio_show: (parseFloat(item.precio) * factor).toFixed(2),
            subtotal_show: (parseFloat(item.subtotal) * factor).toFixed(2)
        })),
        subtotal_mxn: subtotalMxn.toFixed(2),
        subtotal_show: (subtotalMxn * factor).toFixed(2),
        propina_show: propinaActual.toFixed(2),
        propina_mxn: (propinaActual / factor).toFixed(2),
        total_show: ((subtotalMxn * factor) + propinaActual).toFixed(2),
        total_mxn: (subtotalMxn + (propinaActual / factor)).toFixed(2),
        moneda: monedaActual,
        metodoPago: document.querySelector('select[name="metodo_pago"]').value,
        cajero: '<?= addslashes($usuario["nombre"] ?? $usuario["username"] ?? "Cajero") ?>',
        fecha: new Date().toLocaleString('es-MX'),
        tasa: TASA_USD_MXN,
        ticketId: '<?= date("Ymd-His") ?>'
    };
    const inputDatos = document.createElement('input');
    inputDatos.name = 'datos';
    inputDatos.value = JSON.stringify(datosTicket);
    form.appendChild(inputDatos);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
// ⭐ PREVIEW TICKET EN MODAL
function previewTicket() {
    if (itemsCarrito.length === 0) {
        alert('❌ Carrito vacío');
        return;
    }
    
    const subtotalMxn = itemsCarrito.reduce((sum, item) => sum + parseFloat(item.subtotal), 0);
    const factor = monedaActual === 'USD' ? (1 / TASA_USD_MXN) : 1;
    const propinaActual = parseFloat(document.getElementById('propinaInput').value) || 0;
    
    const datosPreview = {
        items: itemsCarrito.map(item => ({
            ...item,
            precio_show: (parseFloat(item.precio) * factor).toFixed(2),
            subtotal_show: (parseFloat(item.subtotal) * factor).toFixed(2)
        })),
        subtotal_mxn: subtotalMxn.toFixed(2),
        subtotal_show: (subtotalMxn * factor).toFixed(2),
        propina_show: propinaActual.toFixed(2),
        propina_mxn: (propinaActual / factor).toFixed(2),
        total_show: ((subtotalMxn * factor) + propinaActual).toFixed(2),
        total_mxn: (subtotalMxn + (propinaActual / factor)).toFixed(2),
        moneda: monedaActual,
        metodoPago: document.querySelector('select[name="metodo_pago"]').value,
        cajero: '<?= addslashes($usuario["nombre"] ?? $usuario["username"] ?? "Cajero") ?>',
        fecha: new Date().toLocaleString('es-MX'),
        tasa: TASA_USD_MXN,
        ticketId: '<?= date("Ymd-His") ?>'
    };
    
    // Abrir preview en nueva ventana
    const previewUrl = `impresion-ticket.php?datos=${encodeURIComponent(JSON.stringify(datosPreview))}&preview=1`;
    const previewWindow = window.open(previewUrl, 'ticketPreview', 
        'width=400,height=700,scrollbars=yes,resizable=yes,toolbar=no,menubar=no');
    
    if (previewWindow) {
        previewWindow.focus();
        console.log('👁️ Preview abierto:', previewUrl);
    } else {
        alert('❌ Bloqueador de popups activado');
    }
}
</script>
</body>
</html>