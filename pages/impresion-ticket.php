<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Cajero']);

// Verificar que hay datos
if (empty($_SESSION['carrito']) && empty($_POST['datos']) && empty($_GET['datos'])) {
    die('No hay datos para imprimir');
}

// Obtener datos del POST/GET
$datosTicket = $_POST['datos'] ?? $_GET['datos'] ?? null;
if ($datosTicket) {
    $datosTicket = json_decode($datosTicket, true);
}

// ⭐ FALLBACK MEJORADO CON VERIFICACIONES
if (!$datosTicket || !is_array($datosTicket)) {
    // Fallback a session (viejo formato)
    $items = $_SESSION['carrito'] ?? [];
    $datosTicket = [
        'items' => $items,
        'subtotal_mxn' => !empty($items) ? array_sum(array_column($items, 'subtotal')) : 0,
        'subtotal_show' => !empty($items) ? array_sum(array_column($items, 'subtotal')) : 0,
        'propina_show' => (float)($_POST['propina'] ?? 0),
        'propina_mxn' => (float)($_POST['propina'] ?? 0),
        'total_show' => 0,
        'total_mxn' => 0,
        'moneda' => $_POST['moneda'] ?? 'MXN',
        'metodoPago' => $_POST['metodo_pago'] ?? 'Efectivo',
        'cajero' => $usuario['nombre'] ?? $usuario['username'] ?? 'Cajero',
        'fecha' => date('d/m/Y H:i:s'),
        'ticketId' => date('Ymd-His'),
        'tasa' => 18.50 // default
    ];
    
    // Calcular totales
    $subtotal = $datosTicket['subtotal_mxn'];
    $propina = $datosTicket['propina_mxn'];
    $datosTicket['total_mxn'] = $subtotal + $propina;
    
    if ($datosTicket['moneda'] === 'USD') {
        $datosTicket['subtotal_show'] = $subtotal / $datosTicket['tasa'];
        $datosTicket['propina_show'] = $propina / $datosTicket['tasa'];
        $datosTicket['total_show'] = $datosTicket['total_mxn'] / $datosTicket['tasa'];
    }
}

if (empty($datosTicket['items'])) {
    die('❌ Carrito vacío');
}

// ⭐ FUNCIÓN HELPER PARA VALORES SEGUROS
function valorSeguro($datos, $clave, $defecto = 0) {
    return isset($datos[$clave]) && is_numeric($datos[$clave]) ? (float)$datos[$clave] : $defecto;
}

// Extraer valores con seguridad
$subtotal_show = valorSeguro($datosTicket, 'subtotal_show', valorSeguro($datosTicket, 'subtotal', 0));
$propina_show = valorSeguro($datosTicket, 'propina_show', valorSeguro($datosTicket, 'propina', 0));
$total_show = valorSeguro($datosTicket, 'total_show', valorSeguro($datosTicket, 'total', 0));
$moneda = $datosTicket['moneda'] ?? 'MXN';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket <?= htmlspecialchars($datosTicket['ticketId'] ?? 'N/A') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            width: 78mm !important; 
            font-family: 'Courier New', monospace !important;
            font-size: 12px; line-height: 1.1; 
            padding: 8px 5px; color: black; background: white;
        }
        .header { text-align: center; margin-bottom: 8px; }
        .logo { font-size: 18px; font-weight: bold; margin-bottom: 2px; }
        .info { font-size: 10px; margin: 2px 0; }
        .linea-s { border-bottom: 1px solid #000; margin: 4px 0; }
        .linea-d { border-bottom: 2px dashed #000; margin: 6px 0; }
        .item { display: flex; justify-content: space-between; padding: 1px 0; font-size: 11px; }
        .nombre { width: 58%; }
        .cantidad { width: 15%; text-align: right; }
        .precio { width: 27%; text-align: right; }
        .total-line { font-size: 13px; font-weight: bold; padding: 4px 0; display: flex; justify-content: space-between; }
        .total-final { font-size: 18px; font-weight: bold; padding: 8px 0; display: flex; justify-content: space-between; }
        .metodo { text-align: center; font-weight: bold; margin: 6px 0; }
        .pie { font-size: 9px; margin-top: 12px; text-align: center; }
        .divisa-info { font-size: 9px; text-align: right; margin: 2px 0; opacity: 0.8; }
        @media print { body { margin: 0 !important; padding: 5px !important; } @page { size: 80mm auto !important; margin: 0 !important; } }
        @media screen { body { box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin: 20px auto; max-width: 300px; } }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div class="logo">BISTRO&COFFEE </div>
        <div style="font-size:11px;">Buen Sabor Buen Momento</div>
        <div class="linea-d"></div>
        <div class="info">Ticket #<?= htmlspecialchars($datosTicket['ticketId'] ?? 'N/A') ?></div>
        <div class="info">Cajero: <?= htmlspecialchars($datosTicket['cajero'] ?? 'N/A') ?></div>
        <div class="info"><?= htmlspecialchars($datosTicket['fecha'] ?? date('d/m/Y H:i:s')) ?></div>
        <?php if (isset($datosTicket['tasa']) && $datosTicket['moneda'] === 'USD'): ?>
        <div class="divisa-info">Tasa: 1 USD = $<?= number_format($datosTicket['tasa'], 2) ?> MXN</div>
        <?php endif; ?>
        <div class="linea-s"></div>
    </div>

    <!-- ITEMS -->
    <div style="margin:8px 0;">
        <?php foreach($datosTicket['items'] as $item): 
            // Precio seguro (nuevo formato o viejo)
            $precio = valorSeguro($item, 'precio_show', valorSeguro($item, 'precio', 0));
            $nombre = strlen($item['nombre']) > 22 ? substr($item['nombre'], 0, 22).'...' : $item['nombre'];
        ?>
        <div class="item">
            <span class="nombre"><?= htmlspecialchars($nombre) ?></span>
            <span class="cantidad"><?= intval($item['cantidad']) ?>x</span>
            <span class="precio">$<?= number_format($precio, 2) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- TOTALES -->
    <div class="linea-d"></div>
    <div class="total-line">
        <span>SUBTOTAL:</span>
        <span>$<?= number_format($subtotal_show, 2) ?></span>
    </div>
    <?php if($propina_show > 0): ?>
    <div style="font-size:11px; display:flex; justify-content:space-between; margin:2px 0;">
        <span>PROPINA:</span>
        <span>$<?= number_format($propina_show, 2) ?></span>
    </div>
    <?php endif; ?>
    
    <div class="total-final">
        <span>TOTAL:</span>
        <span>$<?= number_format($total_show, 2) ?> <?= strtoupper($moneda) ?></span>
    </div>

    <!-- PIE -->
    <div class="linea-d"></div>
    <div class="metodo"><?= htmlspecialchars($datosTicket['metodoPago'] ?? 'Efectivo') ?></div>
    <div class="linea-s"></div>
    <div class="pie">
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>
        ¡Gracias por tu visita!<br>
        Vuelve pronto ✨
    </div>

    <!-- AUTO-IMPRIMIR -->
    <script>
        console.log('Ticket cargado: <?= htmlspecialchars($datosTicket['ticketId'] ?? 'N/A') ?>');
        
        if (window.opener && !window.location.search.includes('preview')) {
            setTimeout(() => {
                console.log('Auto-imprimiendo...');
                window.print();
            }, 500);
            
            window.onafterprint = function() {
                console.log('Impresión completada');
                window.close();
            };
        }
    </script>

</body>
</html>