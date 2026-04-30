<?php
require_once '../config/database.php';

// ⭐ NO REQUIERE AUTENTICACIÓN - PÚBLICO

// Verificar que hay datos
if (empty($_GET['datos'])) {
    die('❌ No hay datos de ticket. <a href="carrito.php">Volver al carrito</a>');
}

// Obtener datos del GET
$datosTicketRaw = $_GET['datos'];
$datosTicket = json_decode(urldecode($datosTicketRaw), true);

if (!$datosTicket || !is_array($datosTicket) || empty($datosTicket['items'])) {
    die('❌ Datos de ticket inválidos. <a href="carrito.php">Volver al carrito</a>');
}

// ⭐ FUNCIÓN HELPER PARA VALORES SEGUROS (igual que caja)
function valorSeguro($datos, $clave, $defecto = 0) {
    return isset($datos[$clave]) && is_numeric($datos[$clave]) ? (float)$datos[$clave] : $defecto;
}

// Extraer valores seguros
$subtotal_show = valorSeguro($datosTicket, 'subtotal_show', valorSeguro($datosTicket, 'subtotal_mxn', 0));
$propina_show = valorSeguro($datosTicket, 'propina_show', valorSeguro($datosTicket, 'propina_mxn', 0));
$total_show = valorSeguro($datosTicket, 'total_show', valorSeguro($datosTicket, 'total_mxn', 0));
$moneda = $datosTicket['moneda'] ?? 'MXN';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Ticket #<?= htmlspecialchars($datosTicket['ticketId'] ?? 'N/A') ?> - Bistro & Coffee</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8ede3 0%, #e9d5c1 100%);
            min-height: 100vh; padding: 2rem 1rem;
        }
        .ticket-container {
            max-width: 350px; margin: 0 auto; background: white;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15); border-radius: 20px;
            overflow: hidden;
        }
        .ticket-header {
            background: linear-gradient(135deg, #8B7355, #A9927D);
            color: white; padding: 2rem 1.5rem 1.5rem; text-align: center;
        }
        .logo { font-size: 2.2rem; font-weight: 900; margin-bottom: 0.5rem; }
        .slogan { font-size: 1rem; opacity: 0.95; margin-bottom: 1.5rem; }
        .ticket-id { 
            background: rgba(255,255,255,0.2); padding: 0.8rem 1.5rem; 
            border-radius: 25px; font-size: 1.1rem; font-weight: 700; 
            backdrop-filter: blur(10px);
        }
        .ticket-body { padding: 2rem 1.5rem; }
        .info-line { 
            display: flex; justify-content: space-between; 
            padding: 0.5rem 0; border-bottom: 1px solid #eee; font-size: 0.95rem;
        }
        .info-line:last-child { border-bottom: none; }
        .items-section { margin: 1.5rem 0; }
        .item-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 0; border-bottom: 1px solid #f0f0f0;
        }
        .item-row:last-child { border-bottom: none; }
        .item-name { font-weight: 600; flex: 1; }
        .item-qty { margin: 0 1rem; font-weight: 700; color: #8B7355; }
        .item-price { font-weight: 700; color: #8B7355; min-width: 60px; text-align: right; }
        .totals-section { background: #f8f9fa; padding: 1.5rem; border-radius: 12px; margin: 1.5rem 0; }
        .total-line {
            display: flex; justify-content: space-between; 
            font-size: 1.1rem; margin-bottom: 0.8rem; font-weight: 600;
        }
        .total-line:last-child { margin-bottom: 0; font-size: 1.4rem; color: #8B7355; }
        .total-final { 
            font-size: 2rem; font-weight: 900; color: #8B7355; 
            text-align: center; margin-top: 1rem; padding-top: 1rem;
            border-top: 3px solid #8B7355;
        }
        .metodo-pago { 
            text-align: center; margin: 1.5rem 0; padding: 1rem; 
            background: rgba(139,115,85,0.1); border-radius: 12px; 
            font-weight: 700; font-size: 1.1rem;
        }
        .ticket-footer {
            background: linear-gradient(135deg, #f8ede3, #e9d5c1);
            padding: 2rem 1.5rem; text-align: center; color: #666;
        }
        .btn-print { 
            background: linear-gradient(135deg, #8B7355, #A9927D); 
            color: white; border: none; padding: 1rem 2rem; 
            border-radius: 25px; font-weight: 700; font-size: 1.1rem;
            cursor: pointer; transition: all 0.3s; box-shadow: 0 10px 25px rgba(139,115,85,0.3);
        }
        .btn-print:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(139,115,85,0.4); }
        .btn-download { 
            background: rgba(169,146,125,0.2); color: #8B7355; 
            border: 2px solid rgba(169,146,125,0.3); margin-left: 1rem;
        }
        @media print {
            body { background: white !important; padding: 0 !important; }
            .ticket-container { box-shadow: none !important; max-width: none !important; }
            .no-print { display: none !important; }
        }
        @media (max-width: 400px) {
            .ticket-container { margin: 0 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <!-- HEADER -->
        <header class="ticket-header">
            <div class="logo">🍽️ BISTRO & COFFEE</div>
            <div class="slogan">Buen Sabor, Buen Momento</div>
            <div class="ticket-id">
                <i class="fas fa-ticket-alt me-2"></i>
                #<?= htmlspecialchars($datosTicket['ticketId'] ?? 'N/A') ?>
            </div>
        </header>

        <!-- BODY -->
        <div class="ticket-body">
            <!-- Info -->
            <div class="info-line">
                <span>📅 Fecha/Hora:</span>
                <span><?= htmlspecialchars($datosTicket['fecha'] ?? date('d/m/Y H:i:s')) ?></span>
            </div>
            <div class="info-line">
                <span>👤 Cajero:</span>
                <span><?= htmlspecialchars($datosTicket['cajero'] ?? 'Cliente Web') ?></span>
            </div>
            <?php if (isset($datosTicket['tasa']) && $moneda === 'USD'): ?>
            <div class="info-line">
                <span>💱 Tasa USD:</span>
                <span>$<?= number_format($datosTicket['tasa'], 2) ?> MXN</span>
            </div>
            <?php endif; ?>

            <!-- Items -->
            <div class="items-section">
                <div style="font-weight: 700; color: #8B7355; margin-bottom: 1rem; font-size: 1.1rem;">
                    Tus productos:
                </div>
                <?php foreach($datosTicket['items'] as $item): 
                    $precio = valorSeguro($item, 'precio_show', valorSeguro($item, 'precio', 0));
                    $nombre = htmlspecialchars(strlen($item['nombre']) > 25 ? substr($item['nombre'], 0, 25).'...' : $item['nombre']);
                ?>
                    <div class="item-row">
                        <span class="item-name"><?= $nombre ?></span>
                        <span class="item-qty"><?= $item['cantidad'] ?>x</span>
                        <span class="item-price">$<?= number_format($precio, 2) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Totales -->
            <div class="totals-section">
                <div class="total-line">
                    <span>Subtotal:</span>
                    <span>$<?= number_format($subtotal_show, 2) ?></span>
                </div>
                <?php if($propina_show > 0): ?>
                <div class="total-line">
                    <span>Propina:</span>
                    <span style="color: #28a745;">$<?= number_format($propina_show, 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="total-final">
                    TOTAL: $<strong><?= number_format($total_show, 2) ?></strong> <?= strtoupper($moneda) ?>
                </div>
            </div>

            <!-- Método pago -->
            <div class="metodo-pago">
                <i class="fas fa-credit-card"></i>
                <?= htmlspecialchars($datosTicket['metodoPago'] ?? 'Efectivo') ?>
            </div>
        </div>

        <!-- FOOTER -->
        <footer class="ticket-footer">
            <div style="font-size: 1.1rem; font-weight: 700; color: #8B7355; margin-bottom: 1rem;">
                ¡Gracias por tu compra! ✨
            </div>
            <div style="font-size: 0.9rem; opacity: 0.8;">
                Guarda esta pantalla o imprime tu ticket<br>
                Presenta este ticket al recoger tu pedido
            </div>
        </footer>

        <!-- ACCIONES (no se imprime) -->
        <div class="no-print" style="padding: 2rem 1.5rem; text-align: center; background: #f8f9fa;">
            <button onclick="imprimirTicket()" class="btn-print">
                <i class="fas fa-print me-2"></i>Imprimir Ticket
            </button>
            <button onclick="descargarPDF()" class="btn-print btn-download">
                <i class="fas fa-download me-2"></i>Guardar PDF
            </button>
            <br><small style="margin-top: 1rem; display: block; color: #666;">
                <a href="carrito.php" style="color: #8B7355;">← Nuevo pedido</a>
            </small>
        </div>
    </div>

    <script>
        function imprimirTicket() {
            window.print();
        }

        function descargarPDF() {
            // Crear canvas del ticket
            html2canvas(document.querySelector('.ticket-container'), {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'ticket-<?= ($datosTicket['ticketId'] ?? 'bistro') ?>.png';
                link.href = canvas.toDataURL();
                link.click();
            }).catch(err => {
                alert('Usa imprimir → Guardar como PDF');
                console.error('Error PDF:', err);
            });
        }

        // Auto-print si viene de móvil
        if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            setTimeout(() => {
                if (confirm('🖨️ ¿Imprimir tu ticket ahora?')) {
                    window.print();
                }
            }, 1500);
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</body>
</html>