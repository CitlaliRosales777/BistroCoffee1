<?php 
session_start();
require_once '../config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Manejar acciones del carrito
if ($_POST['action'] ?? '') {
    $id = $_POST['id'] ?? 0;
    $cantidad = $_POST['cantidad'] ?? 1;
    
    if ($_POST['action'] == 'add') {
        if (!isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id] = ['cantidad' => 0, 'precio' => 0, 'nombre' => ''];
        }
        $_SESSION['carrito'][$id]['cantidad'] += (int)$cantidad;
    } elseif ($_POST['action'] == 'remove') {
        unset($_SESSION['carrito'][$id]);
    } elseif ($_POST['action'] == 'update') {
        $_SESSION['carrito'][$id]['cantidad'] = max(1, (int)$cantidad);
    }
    
    header('Location: carrito.php');
    exit;
}

// Obtener productos del carrito
$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
$productos_info = [];

if (!empty($carrito)) {
    $ids = array_keys($carrito);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "SELECT Id_Producto as id, Nombre, Precio_Venta as precio FROM Productos WHERE Id_Producto IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($ids);
    $productos_info = $stmt->fetchAll();
    
    foreach ($productos_info as $p) {
        $id = $p['id'];
        if (isset($carrito[$id])) {
            $subtotal = $carrito[$id]['cantidad'] * $p['precio'];
            $total += $subtotal;
            $carrito[$id]['nombre'] = $p['Nombre'];
            $carrito[$id]['precio'] = $p['precio'];
        }
    }
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

            <?php if (empty($carrito)): ?>
                <div class="empty-cart text-center py-8" style="color: var(--jet-black);">
                    <i class="fas fa-shopping-cart" style="font-size: 6rem; margin-bottom: 2rem; opacity: 0.5;"></i>
                    <h2>Tu carrito está vacío</h2>
                    <p>Agrega productos desde el menú</p>
                    <a href="menu.php" class="btn" style="margin-top: 2rem;">Ver Menú</a>
                </div>
            <?php else: ?>
                <div class="carrito-contenido" style="display: grid; grid-template-columns: 1fr 380px; gap: 3rem; align-items: start;">
                    
                    <!-- ÍTEMS -->
                    <div class="items">
                        <h2 style="margin-bottom: 2rem;"><i class="fas fa-list"></i> Tus productos (<?= count($carrito) ?>)</h2>
                        
                        <?php foreach ($carrito as $id => $item): if ($item['cantidad'] > 0): ?>
                            <div class="cart-item card" style="display: flex; gap: 2rem; padding: 2rem; margin-bottom: 1.5rem;">
                                <div class="imagen-placeholder" style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--dusty-taupe), var(--stone-brown)); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0;">
                                    🍽️
                                </div>
                                
                                <div class="info" style="flex: 1;">
                                    <h4><?= htmlspecialchars($item['nombre']) ?></h4>
                                    <div class="cantidad-control" style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem;">
                                        <label>Cantidad:</label>
                                        <input type="number" value="<?= $item['cantidad'] ?>" min="1" 
                                               onchange="updateCantidad(<?= $id ?>, this.value)" 
                                               style="width: 70px; padding: 0.5rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 8px; text-align: center;">
                                        <span class="precio-unitario">$<?= number_format($item['precio'], 2) ?></span>
                                    </div>
                                </div>
                                
                                <div class="subtotal" style="font-size: 1.3rem; font-weight: 700; color: var(--dusty-taupe); min-width: 100px; text-align: right;">
                                    $<?= number_format($item['cantidad'] * $item['precio'], 2) ?>
                                </div>
                                
                                <button onclick="removeItem(<?= $id ?>)" class="btn-remove" style="background: none; border: none; color: var(--jet-black); font-size: 1.5rem; cursor: pointer; padding: 0.5rem;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>

                    <!-- RESUMEN -->
                    <div class="resumen card" style="position: sticky; top: 2rem;">
                        <h3>Resumen de pedido</h3>
                        
                        <div class="resumen-item" style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid rgba(169,146,125,0.1);">
                            <span>Subtotal</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                        
                        <div class="resumen-item" style="display: flex; justify-content: space-between; padding: 1rem 0; font-size: 1.1rem;"></div>
                           
                          <!--aqui se puede agregar la opcion de propina-->  
                        
                        <div class="total-final" style="margin-top: 2rem; padding: 2rem; border-radius: 16px; text-align: center;">
                            <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">Total</div>
                            <div style="font-size: 2.5rem;">$<?= number_format($total,2) ?></div> <!--y aqui agregar la operacion de la propina-->
                        </div>
                        
                        <div class="acciones" style="margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem;">
                            <a href="#" class="btn" style="text-align: center; padding: 1.2rem;">
                                <i class="fas fa-credit-card"></i> Pagar con Tarjeta
                            </a>
                            <a href="#" class="btn" style="background: var(--jet-black);">
                                <i class="fas fa-money-bill-wave"></i> Efectivo al Recoger
                            </a>
                            <a href="pedido-confirmacion.php" class="btn" style="background: var(--stone-brown);">
                                <i class="fas fa-receipt"></i> Confirmar Pedido
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
    function updateCantidad(id, cantidad) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update&id=${id}&cantidad=${cantidad}`
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