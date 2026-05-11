<?php 
session_start();
require_once '../config/database.php';

// ✅ VERIFICAR CARRITO Y REFRESCAR DESDE BD
if (empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit;
}

// REFRESCAR DATOS DESDE BD (misma lógica que carrito.php)
$carrito = $_SESSION['carrito'] ?? [];
if (!empty($carrito)) {
    $ids = array_keys($carrito);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "SELECT Id_Producto as id, Nombre, Precio_Venta as precio, Imagen 
            FROM Productos WHERE Id_Producto IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($ids);
    $productos_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($productos_info as $p) {
        $id = $p['id'];
        if (isset($carrito[$id])) {
            $carrito[$id]['nombre'] = $p['Nombre'];
            $carrito[$id]['precio'] = (float)$p['precio'];
            $carrito[$id]['imagen'] = $p['Imagen'] ?? '';
        }
    }
    $_SESSION['carrito'] = $carrito; // Actualizar sesión
}

// CALCULAR TOTALES PARA MOSTRAR
$subtotal = 0;
$items_confirmacion = [];
foreach ($carrito as $id => $item) {
    if (isset($item['cantidad']) && $item['cantidad'] > 0 && 
        isset($item['precio']) && $item['precio'] > 0 &&
        isset($item['nombre']) && !empty($item['nombre'])) {
        
        $subtotal_item = $item['cantidad'] * $item['precio'];
        $subtotal += $subtotal_item;
        $items_confirmacion[$id] = $item;
    }
}

// SI NO HAY ÍTEMS VÁLIDOS, REDIRIGIR
if (empty($items_confirmacion)) {
    $_SESSION['error'] = 'El carrito está vacío o los productos no tienen precios/nombres válidos.';
    header('Location: carrito.php');
    exit;
}

// PROCESAR CONFIRMACIÓN DE PAGO
if ($_POST) {
    $metodoPago = $_POST['metodo_pago'] ?? 'Efectivo';
    $moneda = $_POST['moneda'] ?? 'MXN';
    $propina = (float)($_POST['propina'] ?? 0);
    
    // Preparar datos para ticket
    $items = [];
    $subtotal_mxn = 0;
    foreach ($items_confirmacion as $id => $item) {
        $subtotal_item = $item['cantidad'] * $item['precio'];
        $items[] = [
            'nombre' => $item['nombre'],
            'cantidad' => $item['cantidad'],
            'precio' => $item['precio'],
            'precio_show' => $moneda === 'USD' ? $item['precio'] / 18.50 : $item['precio'],
            'subtotal' => $subtotal_item
        ];
        $subtotal_mxn += $subtotal_item;
    }
    
    $total_mxn = $subtotal_mxn + $propina;
    
    $datosTicket = [
        'items' => $items,
        'subtotal_mxn' => $subtotal_mxn,
        'subtotal_show' => $moneda === 'USD' ? $subtotal_mxn / 18.50 : $subtotal_mxn,
        'propina_mxn' => $propina,
        'propina_show' => $moneda === 'USD' ? $propina / 18.50 : $propina,
        'total_mxn' => $total_mxn,
        'total_show' => $moneda === 'USD' ? $total_mxn / 18.50 : $total_mxn,
        'moneda' => $moneda,
        'metodoPago' => $metodoPago,
        'cajero' => 'Cliente Web',
        'fecha' => date('d/m/Y H:i:s'),
        'ticketId' => 'WEB-' . date('Ymd-His'),
        'tasa' => 18.50
    ];
    
    // GUARDAR EN BD
    try {
        $productos_json = json_encode($items, JSON_UNESCAPED_UNICODE);
        $sql = "INSERT INTO Ventas_Caja (Cajero, Total, Propina, Metodo_Pago, Moneda, Productos, Estado_Cocina) 
                VALUES (?, ?, ?, ?, ?, ?, 'ingreso')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $datosTicket['cajero'],
            $datosTicket['total_mxn'],
            $datosTicket['propina_mxn'],
            $metodoPago,
            $moneda,
            $productos_json
        ]);
        
        // LIMPIAR CARRITO
        $_SESSION['carrito'] = [];
        $_SESSION['mensaje'] = '¡Pedido confirmado! Ticket generado.';
        
        // REDIRIGIR A TICKET
        $datosTicketJson = json_encode($datosTicket);
        header("Location: ticket-cliente.php?datos=" . urlencode($datosTicketJson));
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al procesar: ' . $e->getMessage();
        header('Location: carrito.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pedido - Bistro & Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <section class="confirmacion-section" style="padding: 4rem 2rem; background: linear-gradient(135deg, #f8ede3 0%, #e9d5c1 100%); min-height: 100vh;">
        <div class="container">
            <div class="breadcrumb" style="margin-bottom: 2rem;">
                <a href="../index.php" style="color: var(--dusty-taupe);"><i class="fas fa-home"></i> Inicio</a> 
                <span style="color: var(--jet-black);">›</span>
                <a href="carrito.php" style="color: var(--dusty-taupe);">Carrito</a>
                <span style="color: var(--jet-black);">›</span>
                <span>Confirmación</span>
            </div>

            <div class="confirmacion-contenido" style="max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 1fr 380px; gap: 3rem; align-items: start;">
                
                <!-- DETALLES DEL PEDIDO -->
                <div class="detalles-pedido card" style="padding: 2.5rem;">
                    <h2 style="margin-bottom: 2rem; color: var(--jet-black);">
                        <i class="fas fa-receipt"></i> Revisar tu pedido
                        <span style="background: var(--stone-brown); color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; margin-left: 1rem;">
                            <?= count($items_confirmacion) ?> producto<?= count($items_confirmacion) !== 1 ? 's' : '' ?>
                        </span>
                    </h2>
                    
                    <?php if (empty($items_confirmacion)): ?>
                        <div style="text-align: center; padding: 4rem; color: var(--dusty-taupe);">
                            <i class="fas fa-exclamation-triangle" style="font-size: 4rem; margin-bottom: 1rem; color: #ffc107;"></i>
                            <h3>Carrito vacío o inválido</h3>
                            <p>Los productos no tienen precios válidos. <a href="carrito.php">Volver al carrito</a></p>
                        </div>
                    <?php else: ?>
                        <div class="items-confirmacion">
                            <?php foreach ($items_confirmacion as $id => $item): ?>
                                <?php 
                                $precio_valido = isset($item['precio']) && $item['precio'] > 0;
                                $subtotal_item = $precio_valido ? ($item['cantidad'] * $item['precio']) : 0;
                                ?>
                                <div class="item-confirmacion" style="display: flex; gap: 1.5rem; padding: 1.5rem 0; border-bottom: 1px solid rgba(169,146,125,0.1);">
                                    <!-- Mini imagen -->
                                    <div class="mini-imagen" style="width: 60px; height: 60px; border-radius: 12px; overflow: hidden; background: #f8f9fa; flex-shrink: 0;">
                                        <?php 
                                        $rutaImagen = "../assets/images/productos/" . ($item['imagen'] ?? '');
                                        if (!empty($item['imagen']) && file_exists($rutaImagen)): 
                                        ?>
                                            <img src="<?= $rutaImagen ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="<?= htmlspecialchars($item['nombre']) ?>">
                                        <?php else: ?>
                                            <div style="display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: linear-gradient(135deg, var(--dusty-taupe), var(--stone-brown)); height: 100%;">
                                                🍽️
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 0.3rem 0; font-size: 1.2rem;"><?= htmlspecialchars($item['nombre']) ?></h4>
                                        <div style="color: #666; margin-bottom: 0.5rem;">
                                            <?= $item['cantidad'] ?> x 
                                            <?php if ($precio_valido): ?>
                                                $<span><?= number_format($item['precio'], 2) ?></span>
                                            <?php else: ?>
                                                <span style="color: #dc3545;">Sin precio</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($item['notas'])): ?>
                                            <div class="nota-confirmacion" style="background: rgba(255,193,7,0.1); color: #856404; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.9rem; border-left: 3px solid #ffc107; margin-top: 0.5rem;">
                                                📝 <?= htmlspecialchars($item['notas']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="text-align: right; font-weight: 700; min-width: 80px;">
                                        <?php if ($precio_valido): ?>
                                            <span style="color: var(--dusty-taupe);">$<?= number_format($subtotal_item, 2) ?></span>
                                        <?php else: ?>
                                            <span style="color: #dc3545;">Sin precio</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- SUBTOTAL -->
                            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid var(--dusty-taupe);">
                                <div style="display: flex; justify-content: space-between; font-size: 1.3rem; font-weight: 700; color: var(--jet-black);">
                                    <span>Subtotal:</span>
                                    <span>$<?= number_format($subtotal, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FORMULARIO DE PAGO -->
                <div class="form-pago card" style="position: sticky; top: 2rem; padding: 2.5rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--jet-black);">Finalizar pedido</h3>
                    
                    <?php if (!empty($items_confirmacion)): ?>
                    <div class="resumen-final" style="background: linear-gradient(135deg, var(--stone-brown), var(--dusty-taupe)); color: white; padding: 2rem; border-radius: 16px; text-align: center; margin-bottom: 2rem;">
                        <div style="font-size: 1.6rem; opacity: 0.9; margin-bottom: 0.5rem;">Total</div>
                        <div style="font-size: 2.8rem; font-weight: 800;">$<?= number_format($subtotal, 2) ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">MXN</div>
                    </div>

                    <form method="POST" id="formConfirmar">
                        <!-- PROPINA -->
                        <div class="propina-section" style="background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.8rem; font-weight: 600; color: rgba(255,255,255,0.9);">
                                <i class="fas fa-hand-holding-dollar" style="margin-right: 0.5rem;"></i>
                                Propina opcional
                            </label>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="number" name="propina" step="0.01" min="0" value="0" 
                                       style="flex: 1; padding: 0.8rem; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.9); text-align: right; color: var(--jet-black);" 
                                       onchange="calcularTotal()" onkeyup="calcularTotal()">
                                <span style="font-weight: 600; color: rgba(38, 21, 21, 0.9);">MXN</span>
                            </div>
                        </div>

                        <!-- MÉTODO DE PAGO -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.8rem; font-weight: 600; color: rgba(255,255,255,0.9);">
                                <i class="fas fa-credit-card" style="margin-right: 0.5rem;"></i>
                                Método de pago:
                            </label>
                            <select name="metodo_pago" style="width: 100%; padding: 0.8rem; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.9); color: var(--jet-black);" required>
                                <option value="Efectivo">Efectivo al recoger</option>
                                <option value="Tarjeta">Tarjeta de crédito/débito</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                        </div>

                        <input type="hidden" name="moneda" value="MXN">

                        <button type="submit" class="btn" style="width: 100%; padding: 1.4rem; background: var(--stone-brown); color: white; font-size: 1.2rem; font-weight: 700; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-check-circle" style="margin-right: 0.8rem;"></i>
                            Confirmar Pedido e Imprimir Ticket
                        </button>
                    </form>
                    <?php else: ?>
                        <a href="carrito.php" class="btn" style="width: 100%; padding: 1.4rem; background: var(--dusty-taupe); color: white; text-decoration: none; display: block; text-align: center; border-radius: 12px;">
                            <i class="fas fa-arrow-left" style="margin-right: 0.8rem;"></i>
                            Volver al Carrito
                        </a>
                    <?php endif; ?>

                    <div style="text-align: center; margin-top: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; font-size: 0.9rem; color: rgba(42, 23, 23, 0.72);">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                        Recibirás tu ticket automáticamente. ¡Gracias por tu compra!
                    </div>

                </div>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
    let subtotalBase = <?= $subtotal ?>;
    
    function calcularTotal() {
        const propinaInput = document.querySelector('input[name="propina"]');
        const propina = parseFloat(propinaInput.value) || 0;
        const total = subtotalBase + propina;
        
        // Actualizar visualmente el total si quieres
        console.log('Subtotal:', subtotalBase, 'Propina:', propina, 'Total:', total);
    }
    </script>
</body>
</html>