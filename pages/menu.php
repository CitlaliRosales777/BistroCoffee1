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
    <section class="hero-menu" style="background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); color: white; padding: 4rem 2rem; text-align: center;">
        <div class="container">
            <h1 style="font-size: 3.5rem; margin-bottom: 1rem; color: var(--stone-brown);"><i class="fas fa-utensils"></i> Nuestro Menú</h1>
            <p style="font-size: 1.3rem; max-width: 600px; margin: 0 auto; color: var(--stone-brown);">Explora nuestras delicias recién preparadas</p>
        </div>
    </section>

    <!-- BUSCADOR -->
    <section class="buscador-section" style="padding: 3rem 2rem; background: var(--logo-cream);">
        <div class="container">
            <form method="GET" style="max-width: 500px; margin: 0 auto;">
                <div class="input-group" style="position: relative;">
                    <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>"placeholder="🔍 Busca tu platillo favorito..." style="width: 100%; padding: 1.2rem 1rem 1.2rem 4rem; border: 3px solid #eee; border-radius: 50px; font-size: 1.1rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <i class="fas fa-search" style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
                </div>
            </form>
        </div>
    </section>

    <!-- PRODUCTOS -->
    <section class="productos" style="padding: 4rem 2rem;">
        <div class="container">
            <?php if (empty($productos)): ?>
                <div class="text-center py-8" style="color: #666;">
                    <i class="fas fa-search" style="font-size: 5rem; margin-bottom: 2rem; opacity: 0.5;"></i>
                    <h2>No se encontraron productos</h2>
                    <p>Prueba con otra palabra clave</p>
                </div>
            <?php else: ?>
                <div style="text-align: right; margin-bottom: 2rem; color: #666;">
                    <?= count($productos) ?> platillo<?= count($productos) == 1 ? '' : 's' ?> disponible<?= count($productos) == 1 ? '' : 's' ?>
                </div>
                
                <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 2rem;">
                    <?php foreach ($productos as $producto): ?>
                        <article class="card producto" style="overflow: hidden;">
                            <!-- Imagen placeholder -->
                            <div class="imagen" style="height: 240px; background: linear-gradient(135deg, #f8ede3, #d4a574); display: flex; align-items: center; justify-content: center; font-size: 4rem; position: relative;">
                                🍽️
                                <div class="quick-add" style="position: absolute; bottom: 1rem; right: 1rem; width: 55px; height: 55px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(0,0,0,0.2); cursor: pointer; transition: all 0.3s;">
                                    <i class="fas fa-plus" style="color: var(--primary);"></i>
                                </div>
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
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
    // Buscador live
    document.querySelector('input[name="buscar"]').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });

    // Botones agregar (simulado) PUEDE Q YA NO ESTE SIMULADO PQ NO ME ACUERDO SI HICE CAMBIOS AQUI JSJSJS
    // AÑADIR AL CARRITO REAL
     document.querySelectorAll('.btn-add-cart, .quick-add').forEach(btn => {
       btn.addEventListener('click', function() {
        const id = this.closest('.producto-card, .producto')?.querySelector('[data-id]')?.dataset.id || 
                   this.closest('.producto')?.querySelector('.btn-agregar')?.dataset.id;
        
        fetch('../pages/carrito.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=add&id=${id}&cantidad=1`
        }).then(() => {
            // Badge carrito en header
            let badge = document.querySelector('.cart-badge');
            if (!badge) {
                const nav = document.querySelector('nav ul');
                const li = document.createElement('li');
                li.innerHTML = '<a href="../pages/carrito.php" class="cart-link"><i class="fas fa-shopping-cart"></i> <span class="cart-badge">1</span></a>';
                nav.appendChild(li);
            }
            alert('Añadido al carrito!');
        });
    });
});

    // Hover efectos
    document.querySelectorAll('.producto').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
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
    .quick-add {
        background: white;
        border: 2px solid var(--dusty-taupe);
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s;
    }
    .quick-add:hover {
        background: var(--dusty-taupe);
    }
    .quick-add:hover i {
        color: white !important;
    }
    .producto { transition: all 0.4s ease; }
    </style>
</html>