<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Administrador']);

// Obtener datos
$productos = db_fetch_all($conn, "SELECT * FROM Productos ORDER BY Nombre ASC");
$total_productos = count($productos);

// Totales adicionales
$total_ventas = db_fetch_one($conn, "SELECT COUNT(*) as total FROM Ventas")['total'] ?? 0;
$total_ingresos = db_fetch_one($conn, "SELECT COALESCE(SUM(Total), 0) as total FROM Ventas WHERE estado = 'Completada'")['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📄 Reporte Menú - <?= date('d/m/Y') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 210mm; 
            margin: 0 auto; 
            padding: 20mm; 
            background: white;
            color: #2d1b14;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 3px solid #e76f51; 
            padding-bottom: 20px;
        }
        .header h1 { 
            color: #264653; 
            font-size: 28px; 
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .header p { 
            color: #8d5524; 
            font-size: 16px; 
            font-weight: 500;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        th { 
            background: linear-gradient(135deg, #f4a261, #e76f51); 
            color: white; 
            padding: 15px 12px; 
            text-align: left; 
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        td { 
            padding: 12px; 
            border-bottom: 1px solid #eee;
        }
        tr:nth-child(even) { background: #fdf8f3; }
        tr:hover { background: #fff3e6; }
        .precio { text-align: right; font-weight: 700; color: #d00000; }
        .imagen { width: 60px; height: 60px; object-fit: cover; border-radius: 12px; border: 2px solid #f4a261; }
        .total-row { 
            background: linear-gradient(135deg, #264653, #2a9d8f) !important; 
            color: white !important; 
            font-size: 16px; 
            font-weight: 700;
        }
        .total-row td { padding: 18px 12px; }
        .footer { 
            text-align: center; 
            margin-top: 40px; 
            padding-top: 20px; 
            border-top: 2px solid #e76f51;
            color: #8d5524;
            font-size: 14px;
        }
        @media print {
            body { padding: 10mm; }
            .no-print { display: none; }
        }
        @page { margin: 15mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍽️ BISTRO COFFEE</h1>
        <p>REPORTE COMPLETO DEL MENÚ</p>
        <p><strong>Generado:</strong> <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>PRODUCTO</th>
                <th>DESCRIPCIÓN</th>
                <th>PRECIO</th>
                <th>IMAGEN</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($productos as $producto): ?>
            <tr>
                <td style="font-weight: 600; color: #264653;">#<?= $producto['Id_Producto'] ?></td>
                <td><strong><?= htmlspecialchars($producto['Nombre']) ?></strong></td>
                <td><?= htmlspecialchars($producto['Descripcion'] ?: '—') ?></td>
                <td class="precio">$<?= number_format($producto['Precio_Venta'], 2) ?></td>
                <td>
                    <?php if($producto['Imagen']): ?>
                        <img src="../assets/images/productos/<?= htmlspecialchars($producto['Imagen']) ?>" 
                             alt="<?= htmlspecialchars($producto['Nombre']) ?>" class="imagen" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <span style="display: none; color: #666; font-size: 12px;">Sin vista previa</span>
                    <?php else: ?>
                        <span style="color: #999; font-style: italic;">Sin imagen</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">
                    <strong>TOTAL PRODUCTOS:</strong>
                </td>
                <td style="text-align: right; font-size: 18px;"><?= $total_productos ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>📊 <strong>Estadísticas:</strong> <?= $total_ventas ?> ventas | $<?= number_format($total_ingresos, 2) ?> ingresos</p>
        <p>Generado por el sistema administrativo • Bistro Coffee <?= date('Y') ?></p>
    </div>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px; background: #007bff; color: white; padding: 10px 20px; border-radius: 25px; font-weight: 600; cursor: pointer;" onclick="window.print()">
        🖨️ IMPRIMIR PDF
    </div>

    <script>
        // Auto-imprimir después de 2 segundos
        setTimeout(() => {
            window.print();
        }, 2000);
        
        // Cerrar ventana después de imprimir
        window.onafterprint = () => {
            setTimeout(() => window.close(), 500);
        }
    </script>
</body>
</html>