<?php 
require_once '../config/database.php';
require_once '../includes/menu-functions.php';

// Insertar datos de prueba automáticamente
insertarProductosPrueba($conn);

// Filtros
$busqueda = $_GET['buscar'] ?? '';
$productos = getProductos($conn, $busqueda);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Digital - Bistro & Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=42">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- HERO MENÚ -->
<section class="hero-menu" style="background: linear-gradient(135deg, var(--primary) 10%, var(--accent) 100%); color: white; padding: 0rem 0rem 0rem; text-align: center;">
    <div class="container">
        <h1 style="font-size: 4.5rem; margin-bottom: 1rem; color: var(--stone-brown);"><i class="fas fa-utensils"></i> Nuestro Menú</h1>
        <p style="font-size: 1.5rem; max-width: 1000px; margin: 0 auto; color: var(--stone-brown);">Explora nuestras delicias recién preparadas</p>
    </div>
</section>

<!-- BUSCADOR -->
<section class="buscador-section" style="padding: 2rem 2rem; background: var(--logo-cream);">
    <div class="container">
        <form method="GET" style="max-width: 600px; margin: 0 auto;">
            <div class="input-group" style="position: relative;">
                <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Busca tu platillo favorito..." style="width: 100%; padding: 1.2rem 1rem 1.2rem 4rem; border: 3px solid #eee; border-radius: 50px; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <i class="fas fa-search" style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
            </div>
        </form>
    </div>
</section>

    <!-- PRODUCTOS - SIEMPRE VISIBLE -->
    <section class="productos" style="padding: 4rem 2rem;">
        <div class="container">
            <!-- CONTADOR SIEMPRE VISIBLE -->
            <div style="text-align: right; margin-bottom: 2rem; color: #666;">
                <?= count($productos) ?> platillo<?= count($productos) == 1 ? '' : 's' ?> disponible<?= count($productos) == 1 ? '' : 's' ?>
            </div>
            
            <!-- GRILLA SIEMPRE VISIBLE -->
            <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 2rem; min-height: 600px;">
                
                <?php if (empty($productos)): ?>
                    <!-- PLACEHOLDER CUANDO NO HAY PRODUCTOS -->
                    <div class="empty-results card" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 6rem 2rem; text-align: center; min-height: 500px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 24px; border: 2px dashed rgba(169,146,125,0.3);">
                        <i class="fas fa-search" style="font-size: 6rem; margin-bottom: 2rem; color: var(--dusty-taupe); opacity: 0.5;"></i>
                        <h2 style="color: var(--jet-black); margin-bottom: 1rem; font-size: 2.5rem;">No se encontraron productos</h2>
                        <p style="color: var(--dusty-taupe); font-size: 1.3rem; margin-bottom: 2rem; max-width: 500px;">
                            <?= $busqueda ? 'No hay resultados para "' . htmlspecialchars($busqueda) . '"' : 'No hay productos disponibles' ?>
                        </p>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                            <a href="?buscar=" class="btn" style="background: var(--stone-brown); padding: 1rem 2rem;">Limpiar búsqueda</a>
                            <a href="../pages/carrito.php" class="btn" style="background: var(--dusty-taupe); padding: 1rem 2rem;">
                                <i class="fas fa-shopping-cart"></i> Ver Carrito
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- PRODUCTOS CON IMÁGENES REALES -->
                    <?php foreach ($productos as $producto): ?>
                        <article class="card producto" style="overflow: hidden;">
                            <!-- IMAGEN REAL DE LA BASE DE DATOS -->
                            <div class="imagen" style="height: 240px; background: #f8f9fa; position: relative; overflow: hidden;">
    <?php if (!empty($producto['Imagen']) && file_exists("../assets/images/productos/" . $producto['Imagen'])): ?>
        <img src="../assets/images/productos/<?= htmlspecialchars($producto['Imagen']) ?>" 
             alt="<?= htmlspecialchars($producto['Nombre']) ?>" 
             style="width: 100%; height: 100%; object-fit: cover; object-position: center;"
             loading="lazy">
    <?php else: ?>
        
    <?php endif; ?>
</div>
                            
                            <div class="contenido" style="padding: 1.5rem;">
                                <h3 style="margin-bottom: 0.5rem; font-size: 1.4rem;"><?= htmlspecialchars($producto['Nombre']) ?></h3>
                                <p style="color: #666; margin-bottom: 1.5rem; line-height: 1.6;">
                                    <?= htmlspecialchars(substr($producto['Descripcion'], 0, 120)) ?>...
                                </p>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span class="precio" style="font-size: 1.8rem; font-weight: 700; color: var(--primary);">
                                        $<span><?= number_format($producto['precio'], 2) ?></span>
                                    </span>
                                    <button class="btn-agregar" data-id="<?= $producto['id'] ?>">
                                        <i class="fas fa-cart-plus"></i> Añadir
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.querySelectorAll('.btn-agregar').forEach(btn => {
     btn.addEventListener('click', function() {
        const id = this.dataset.id;
        
        fetch('../pages/carrito.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=add&id=${id}&cantidad=1`
        }).then(r => r.json())
        .then(data => {
            // Actualizar TODOS los badges
            document.querySelectorAll('.cart-badge').forEach(badge => {
                badge.textContent = data.total_items > 99 ? '99+' : data.total_items;
                badge.dataset.count = data.total_items;
                badge.style.display = 'flex';
            });
            
            // Efecto éxito
            const original = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> ¡Listo!';
            this.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
            this.style.transform = 'scale(0.98)';
            
            setTimeout(() => {
                this.innerHTML = original;
                this.style.background = '';
                this.style.transform = '';
            }, 1200);
            
            // Sonido opcional (si quieres)
            // new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAo').play();
        }).catch(e => console.error('Carrito:', e));
    });
});
</script>

    <style>
    .btn-agregar { 
        background: linear-gradient(135deg, var(--dusty-taupe), var(--stone-brown));
        color: white; border: none; 
        padding: 0.8rem 1.5rem; border-radius: 25px; 
        font-weight: 600; cursor: pointer; transition: all 0.3s;
    }
    .btn-agregar:hover { 
        transform: scale(1.05); 
        box-shadow: 0 10px 25px rgba(94, 80, 63, 0.4);
    }
    .producto { transition: all 0.4s ease; }
    .empty-results {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
    </style>
</html>
