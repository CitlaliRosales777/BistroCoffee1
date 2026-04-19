
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Bistro & Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=42">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
        
    <!-- HERO SECTION -->
    <section class="hero-menu" style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; padding: 4rem 2rem; text-align: center;">
        <div class="container">
            <h1 style="font-size: 3.5rem; margin-bottom: 1rem;"><i class="fas fa-utensils"></i> REALIZA TU PEDIDO</h1>
        </div>
    </section>

        <!-- RESUMEN DEL PEDIDO -->
    <section style="padding: 1rem 2rem;">
        <div class="form-container card" style="max-width: 400px; margin: 0 auto;">
            <h2>Resumen de tu pedido</h2>
            
            <!-- Aquí irán los datos del carrito -->

            <div style="margin-top: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    <i class="fas fa-pen"></i> Observaciones
                </label>
                <textarea name="observaciones" rows="3" placeholder="Ej: latte con leche deslactosada, pan integral..." 
                        style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;"></textarea>
            </div>
        </div>
        <section style="padding: 1rem 2rem;">
            <div class="form-container card" style="max-width: 400px; margin: 0 auto;">
                <label style="font-weight: 800;">Método de pago</label>
                <div class="metodo-pago-grid">
                    <label class="metodo-card">
                        <input type="radio" name="metodo_pago" value="efectivo" required>
                        <span style="font-weight: 600;">Efectivo</span>
                    </label>
                    <label class="metodo-card">
                        <input type="radio" name="metodo_pago" value="tarjeta">
                        <span style="font-weight: 600;">Tarjeta</span>
                        </label>
                </div>
            </div>
        </section>
    <!-- DATOS DEL CLIENTE -->
    <section style="padding: 1rem 2rem;">
            <div class="form-container card" style="max-width: 400px; margin: 0 auto;">
                    <h2>Datos del cliente</h2>

                    <form action="procesar_pedido.php" method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div class="form-group" >
                                <label style= "font-weight: 600;">Nombre</label>
                                <input type="text" name="nombre" required style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;">
                            </div>

                            <div class="form-group">
                                <label style= "font-weight: 600;">Teléfono</label>
                                <input type="text" name="telefono" required style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;">
                            </div>

                            <button type="submit" class="btn" style="margin-top: 1rem;">
                                Confirmar pedido
                            </button>
                        </div>
                    </form>
            </div>
</section>