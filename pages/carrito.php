<?php 
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// ✅ MANEJAR ACCIONES - SIEMPRE RECUPERAR DATOS DE BD
if ($_POST['action'] ?? '') {
    $id = $_POST['id'] ?? 0;
    $cantidad = $_POST['cantidad'] ?? 1;
    $notas = $_POST['notas'] ?? '';
    
    // ✅ RECUPERAR PRODUCTO DE BD SIEMPRE
    $producto = null;
    if ($id > 0) {
        $sql = "SELECT Id_Producto as id, Nombre, Precio_Venta as precio, Imagen 
                FROM Productos WHERE Id_Producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $producto = $stmt->fetch();
    }
    
    if ($_POST['action'] == 'add') {
        if ($producto && $producto['precio'] > 0) {
            if (!isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id] = [
                    'cantidad' => 0, 
                    'precio' => 0, 
                    'nombre' => '', 
                    'imagen' => '',
                    'notas' => ''
                ];
            }
            $_SESSION['carrito'][$id]['cantidad'] += (int)$cantidad;
            $_SESSION['carrito'][$id]['precio'] = $producto['precio'];  // ✅
            $_SESSION['carrito'][$id]['nombre'] = $producto['Nombre'];  // ✅
            $_SESSION['carrito'][$id]['imagen'] = $producto['Imagen']; // ✅
            if ($notas) $_SESSION['carrito'][$id]['notas'] = $notas;
        }
    } elseif ($_POST['action'] == 'remove') {
        unset($_SESSION['carrito'][$id]);
    } elseif ($_POST['action'] == 'update') {
        if (isset($_SESSION['carrito'][$id]) && $producto && $producto['precio'] > 0) {
            $_SESSION['carrito'][$id]['cantidad'] = max(1, (int)$cantidad);
            $_SESSION['carrito'][$id]['precio'] = $producto['precio'];     // ✅
            $_SESSION['carrito'][$id]['nombre'] = $producto['Nombre'];     // ✅
            $_SESSION['carrito'][$id]['imagen'] = $producto['Imagen'];    // ✅
            if ($notas) $_SESSION['carrito'][$id]['notas'] = $notas;
        }
    }
    
    header('Location: carrito.php');
    exit;
}

// ✅ OBTENER PRODUCTOS - ACTUALIZAR TODOS LOS DATOS
$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
$productos_info = [];

if (!empty($carrito)) {
    $ids = array_keys($carrito);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "SELECT Id_Producto as id, Nombre, Precio_Venta as precio, Imagen 
            FROM Productos WHERE Id_Producto IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($ids);
    $productos_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ✅ ACTUALIZAR CADA ITEM CON DATOS FREScos DE BD
    foreach ($productos_info as $p) {
        $id = $p['id'];
        if (isset($carrito[$id])) {
            $carrito[$id]['nombre'] = $p['Nombre'];
            $carrito[$id]['precio'] = (float)$p['precio'];
            $carrito[$id]['imagen'] = $p['Imagen'];
            
            $subtotal = $carrito[$id]['cantidad'] * $carrito[$id]['precio'];
            $total += $subtotal;
        }
    }
    
    // ✅ IMPORTANTE: Actualizar sesión con datos frescos
    $_SESSION['carrito'] = $carrito;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - Bistro & Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <section class="carrito-section" style="padding: 4rem 2rem;">
        <div class="container">
            <div class="breadcrumb" style="margin-bottom: 2rem;">
                <a href="../index.php" style="color: var(--dusty-taupe);"><i class="fas fa-home"></i> Inicio</a> 
                <span style="color: var(--jet-black);">›</span>
                <span>Carrito</span>
            </div>

            <div class="carrito-contenido" style="display: grid; grid-template-columns: 1fr 380px; gap: 3rem; align-items: start;">
                
                <!-- ÍTEMS -->
                <div class="items">
                    <h2 style="margin-bottom: 2rem;">
                        <i class="fas fa-list"></i> 
                        Tus productos (<?= count($carrito) ?>)
                    </h2>
                    
                    <?php if (empty($carrito)): ?>
                        <div class="empty-cart-placeholder card" style="min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 2rem; text-align: center; color: var(--jet-black);">
                            <i class="fas fa-shopping-cart" style="font-size: 6rem; margin-bottom: 2rem; opacity: 0.5; color: var(--dusty-taupe);"></i>
                            <h3 style="margin-bottom: 1rem; color: var(--jet-black);">Tu carrito está vacío</h3>
                            <p style="margin-bottom: 2rem; color: var(--dusty-taupe);">Agrega productos desde el menú para verlos aquí</p>
                            <a href="menu.php" class="btn" style="padding: 1rem 2rem;">Ver Menú</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($carrito as $id => $item): if ($item['cantidad'] > 0): ?>
                            <div class="cart-item card" style="display: flex; gap: 2rem; padding: 2rem; margin-bottom: 1.5rem;">
                                
                                <!-- IMAGEN -->
                                <div class="imagen-producto" style="width: 80px; height: 80px; border-radius: 16px; overflow: hidden; flex-shrink: 0; background: #f8f9fa;">
                                    <?php 
                                    $rutaImagen = "../assets/images/productos/" . ($item['imagen'] ?? '');
                                    if (!empty($item['imagen']) && file_exists($rutaImagen)): 
                                    ?>
                                        <img src="<?= $rutaImagen ?>" alt="<?= htmlspecialchars($item['nombre']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--dusty-taupe), var(--stone-brown)); display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                                            🍽️
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="info" style="flex: 1;">
                                    <h4 style="margin: 0 0 0.5rem 0;"><?= htmlspecialchars($item['nombre']) ?></h4>
                                    
                                    <!-- NOTAS DEL PRODUCTO -->
                                    <?php if (!empty($item['notas'])): ?>
                                        <div class="notas-producto" style="background: rgba(169,146,125,0.1); padding: 0.5rem 0.8rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; color: var(--dusty-taupe); border-left: 3px solid var(--stone-brown);">
                                            <i class="fas fa-note-sticky" style="margin-right: 0.5rem;"></i>
                                            <?= htmlspecialchars($item['notas']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="cantidad-control" style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem;">
                                        <label>Cantidad:</label>
                                        <input type="number" value="<?= $item['cantidad'] ?>" min="1" 
                                               onchange="updateCantidad(<?= $id ?>, this.value, '<?= htmlspecialchars($item['notas'] ?? '') ?>')" 
                                               style="width: 70px; padding: 0.5rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 8px; text-align: center;">
                                        <span class="precio-unitario">$<?= number_format($item['precio'], 2) ?></span>
                                    </div>
                                    
                                    <!-- CAMPO DE NOTAS -->
                                    <div class="notas-input" style="margin-top: 1rem;">
                                        <label style="display: block; font-size: 0.9rem; color: #666; margin-bottom: 0.3rem;">
                                            <i class="fas fa-edit" style="margin-right: 0.3rem;"></i>Notas especiales:
                                        </label>
                                        <input type="text" 
                                               value="<?= htmlspecialchars($item['notas'] ?? '') ?>" 
                                               placeholder="Ej: sin miel, descafeinado, extra queso..."
                                               onchange="updateNotas(<?= $id ?>, this.value)"
                                               style="width: 100%; padding: 0.6rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 8px; font-size: 0.9rem; transition: border-color 0.3s;">
                                    </div>
                                </div>
                                
                                <div class="subtotal" style="font-size: 1.3rem; font-weight: 700; color: var(--dusty-taupe); min-width: 100px; text-align: right; margin-top: auto;">
                                    $<?= number_format($item['cantidad'] * $item['precio'], 2) ?>
                                </div>
                                
                                <button onclick="removeItem(<?= $id ?>)" class="btn-remove" style="background: none; border: none; color: var(--jet-black); font-size: 1.5rem; cursor: pointer; padding: 0.5rem; margin-top: auto;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endif; endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- RESUMEN (SIN CAMBIOS) -->
                <div class="resumen card" style="position: sticky; top: 2rem;">
                    <h3>Resumen de pedido</h3>
                    <?php if (!empty($carrito)): ?>
                        <div class="resumen-item" style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid rgba(169,146,125,0.1);">
                            <span>Subtotal</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="total-final" style="margin-top: 2rem; padding: 2rem; border-radius: 16px; text-align: center;">
                        <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">Total</div>
                        <div style="font-size: 2.5rem; color: <?= empty($carrito) ? 'var(--dusty-taupe)' : 'var(--stone-brown)' ?>">
                            $<?= empty($carrito) ? '0.00' : number_format($total, 2) ?>
                        </div>
                    </div>
                    
                    <div class="acciones" style="margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem;">
                        <?php if (!empty($carrito)): ?>
                            <a href="pedido-confirmacion.php" class="btn" style="background: var(--stone-brown);">
                                <i class="fas fa-receipt"></i> Confirmar Pedido
                            </a>
                        <?php else: ?>
                            <a href="menu.php" class="btn" style="background: var(--stone-brown); text-align: center; padding: 1.2rem;">
                                <i class="fas fa-utensils"></i> Ir al Menú
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
    function updateCantidad(id, cantidad, notas = '') {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update&id=${id}&cantidad=${cantidad}&notas=${encodeURIComponent(notas)}`
        }).then(() => location.reload());
    }

    function updateNotas(id, notas) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update&id=${id}&notas=${encodeURIComponent(notas)}&cantidad=1`
        }).then(() => location.reload());
    }

    function removeItem(id) {
        if (confirm('¿Eliminar este producto?')) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=remove&id=${id}`
            }).then(() => location.reload());
        }
    }
    </script>
</body>
</html>