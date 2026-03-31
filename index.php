<?php 
require_once 'config/database.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bistro & Coffee - Menú Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- HEADER -->
    <?php include 'includes/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-content">
            <h1><i class="fas fa-coffee"></i> Bistro & Coffee</h1>
            <p class="hero-subtitle">AQUI VA EL SLOGAN Q NO ME ACUERDO COMO VA JSJSJSJ</p>
            <div class="hero-buttons">
                <a href="pages/menu.php" class="btn btn-primary"><i class="fas fa-utensils"></i> Ver Menú</a>
                <a href="pages/reservas.php" class="btn btn-secondary"><i class="fas fa-calendar-check"></i> Reservar</a>
            </div>
        </div>
    </section>

    <!-- SERVICIOS PRINCIPALES -->
    <section class="services-section" style="padding: 6rem 2rem; background: var(--light);">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 style="font-size: 3rem; color: var(--dark); margin-bottom: 1rem;">Nuestros Servicios</h2>
                <p style="font-size: 1.2rem; color: #666; max-width: 600px; margin: 0 auto;">Todo lo que necesitas para una experiencia perfecta</p>
            </div>
            
            <div class="services-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div class="service-card card">
                    <div class="service-icon" style="font-size: 4rem; color: var(--primary); margin-bottom: 1.5rem;">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3>Menú Digital</h3>
                    <p>Explora nuestro menú interactivo con fotos HD, descripciones detalladas y precios actualizados en tiempo real.</p>
                </div>
                
                <div class="service-card card">
                    <div class="service-icon" style="font-size: 4rem; color: var(--primary); margin-bottom: 1.5rem;">
                        <i class="fas fa-chair"></i>
                    </div>
                    <h3>Reservas Online</h3>
                    <p>Reserva tu mesa favorita en segundos. Ve disponibilidad en tiempo real y confirma al instante.</p>
                </div>
                
                <div class="service-card card">
                    <div class="service-icon" style="font-size: 4rem; color: var(--primary); margin-bottom: 1.5rem;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Pedidos Online</h3>
                    <p>Para llevar o consumir en el lugar. Carrito inteligente con cálculo automático de totales y tal vez propinas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- MENÚ DESTACADO (Simulado pq la base de datos usada no tiene datos insertados aun jsjsjs) -->
    <section class="menu-preview" style="padding: 6rem 2rem; background: white;">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 style="font-size: 3rem; color: var(--dark); margin-bottom: 1rem;">Menú Destacado</h2>
                <p style="font-size: 1.2rem; color: #666;">Nuestras especialidades del día</p>
            </div>
            
            <div class="menu-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
                <!-- Plato 1 -->
                <div class="menu-item card">
                    <div class="menu-image" style="height: 200px; background: linear-gradient(45deg, #f5a623, #ff8c42); border-radius: 20px 20px 0 0; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: white;">
                        🥞
                    </div>
                    <div class="p-4">
                        <h4>Pancakes Clásicos</h4>
                        <p class="menu-desc" style="color: #666; margin-bottom: 1rem;">Pancakes esponjosos con maple y frutas frescas</p>
                        <div class="menu-price" style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">$85</div>
                    </div>
                </div>

                <!-- Plato 2 -->
                <div class="menu-item card">
                    <div class="menu-image" style="height: 200px; background: linear-gradient(45deg, #2c5530, #4a7c59); border-radius: 20px 20px 0 0; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: white;">
                        ☕
                    </div>
                    <div class="p-4">
                        <h4>Café Especial Casa</h4>
                        <p class="menu-desc" style="color: #666; margin-bottom: 1rem;">100% Arábica tostado artesanalmente</p>
                        <div class="menu-price" style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">$45</div>
                    </div>
                </div>

                <!-- Plato 3 -->
                <div class="menu-item card">
                    <div class="menu-image" style="height: 200px; background: linear-gradient(45deg, #8b4513, #a0522d); border-radius: 20px 20px 0 0; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: white;">
                        🥩
                    </div>
                    <div class="p-4">
                        <h4>Milanesa</h4>
                        <p class="menu-desc" style="color: #666; margin-bottom: 1rem;">Corte premium empanizado y papas (no hay nada de premiun en esto jsjsj) </p>
                        <div class="menu-price" style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">$285</div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="pages/menu.php" class="btn btn-large">Ver Menú Completo</a>
            </div>
        </div>
    </section>

    <!-- TESTIMONIOS FAKEEE-->
    <section class="testimonials" style="padding: 6rem 2rem; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white;">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 style="font-size: 3rem; margin-bottom: 1rem;">Lo que dicen nuestros clientes</h2>
                <div style="width: 80px; height: 4px; background: white; margin: 0 auto;"></div>
            </div>
            
            <div class="testimonials-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div class="testimonial-card card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                    <div class="stars" style="color: #ffd700; margin-bottom: 1rem;">
                        ⭐⭐⭐⭐⭐
                    </div>
                    <p>"¡El mejor café de la ciudad! El sistema de pedidos es súper intuitivo."</p>
                    <div class="author" style="margin-top: 1.5rem; font-weight: 600;">
                        María G. <span style="font-size: 0.9rem; opacity: 0.8;">• Hace 2 días</span>
                    </div>
                </div>
                
                <div class="testimonial-card card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                    <div class="stars" style="color: #ffd700; margin-bottom: 1rem;">
                        ⭐⭐⭐⭐⭐
                    </div>
                    <p>"Reservé mi mesa en 30 segundos. Experiencia 10/10."</p>
                    <div class="author" style="margin-top: 1.5rem; font-weight: 600;">
                        Carlos R. <span style="font-size: 0.9rem; opacity: 0.8;">• Hace 1 día</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- UNA ATRAPA CLIENTES JSJSJJS-->
    <section class="cta-section" style="padding: 6rem 2rem; background: var(--dark); color: white; text-align: center;">
        <div class="container">
            <h2 style="font-size: 3rem; margin-bottom: 1rem;">¿Listo para disfrutar?</h2>
            <p style="font-size: 1.3rem; margin-bottom: 2.5rem; opacity: 0.9;">Haz tu pedido o reserva ahora mismo</p>
            <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
                <a href="pages/menu.php" class="btn btn-large" style="background: var(--primary); padding: 1.2rem 3rem; font-size: 1.1rem;">
                    <i class="fas fa-shopping-cart"></i> Hacer Pedido
                </a>
                <a href="pages/reservas.php" class="btn btn-large" style="background: transparent; border: 2px solid white; color: white; padding: 1.2rem 3rem; font-size: 1.1rem;">
                    <i class="fas fa-calendar"></i> Reservar Mesa
                </a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>