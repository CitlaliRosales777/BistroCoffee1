<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Cajero']);

// Verificar que hay datos
if (empty($_SESSION['carrito']) && empty($_POST['datos']) && empty($_GET['datos'])) {
    die('❌ No hay datos para imprimir');
}

// Obtener datos del carrito o POST/GET
$datosTicket = $_POST['datos'] ?? $_GET['datos'] ?? null;
if ($datosTicket) {
    $datosTicket = json_decode($datosTicket, true);
}

if (!$datosTicket || empty($datosTicket['items'])) {
    // Fallback a session
    $datosTicket = [
        'items' => $_SESSION['carrito'] ?? [],
        'subtotal' => $_SESSION['carrito'] ? array_sum(array_column($_SESSION['carrito'], 'subtotal')) : 0,
        'propina' => (float)($_POST['propina'] ?? 0),
        'total' => 0,
        'moneda' => $_POST['moneda'] ?? 'MXN',
        'metodoPago' => $_POST['metodo_pago'] ?? 'Efectivo',
        'cajero' => $usuario['nombre'] ?? $usuario['username'] ?? 'Cajero',
        'fecha' => date('d/m/Y H:i:s'),
        'ticketId' => date('Ymd-His')
    ];
    $datosTicket['total'] = $datosTicket['subtotal'] + $datosTicket['propina'];
}

if (empty($datosTicket['items'])) {
    die('❌ Carrito vacío');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket <?= htmlspecialchars($datosTicket['ticketId']) ?></title>
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
        <div class="info">Ticket #<?= htmlspecialchars($datosTicket['ticketId']) ?></div>
        <div class="info">Cajero: <?= htmlspecialchars($datosTicket['cajero']) ?></div>
        <div class="info"><?= htmlspecialchars($datosTicket['fecha']) ?></div>
        <div class="linea-s"></div>
    </div>

    <!-- ITEMS -->
    <div style="margin:8px 0;">
        <?php foreach($datosTicket['items'] as $item): 
            $nombre = strlen($item['nombre']) > 22 ? substr($item['nombre'], 0, 22).'...' : $item['nombre'];
        ?>
        <div class="item">
            <span class="nombre"><?= htmlspecialchars($nombre) ?></span>
            <span class="cantidad"><?= $item['cantidad'] ?>x</span>
            <span class="precio">$<?= number_format($item['precio'], 0) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- TOTALES -->
    <div class="linea-d"></div>
    <div class="total-line">
        <span>SUBTOTAL:</span>
        <span>$<?= number_format($datosTicket['subtotal'], 2) ?></span>
    </div>
    <?php if($datosTicket['propina'] > 0): ?>
    <div style="font-size:11px; display:flex; justify-content:space-between; margin:2px 0;">
        <span>PROPINA:</span>
        <span>$<?= number_format($datosTicket['propina'], 2) ?></span>
    </div>
    <?php endif; ?>
    
    <div class="total-final">
        <span>TOTAL:</span>
        <span>$<?= number_format($datosTicket['total'], 2) ?> <?= strtoupper($datosTicket['moneda']) ?></span>
    </div>

    <!-- PIE -->
    <div class="linea-d"></div>
    <div class="metodo"><?= htmlspecialchars($datosTicket['metodoPago']) ?></div>
    <div class="linea-s"></div>
    <div class="pie">
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<br>
        ¡Gracias por tu visita!<br>
        Vuelve pronto ✨
    </div>

    <!-- ⭐ SCRIPT PARA AUTO-IMPRIMIR -->
    <script>
        console.log('📄 Ticket cargado: <?= $datosTicket['ticketId'] ?>');
        
        // Auto-imprimir si viene de ventana nueva
        if (window.opener && !window.location.search.includes('preview')) {
            setTimeout(() => {
                console.log('🖨️ Auto-imprimiendo...');
                window.print();
            }, 500);
            
            // Cerrar después de imprimir
            window.onafterprint = function() {
                console.log('✅ Impresión completada');
                window.close();
            };
        }
    </script>

</body>
</html>