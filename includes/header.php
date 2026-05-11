<?php 
if (file_exists('auth.php')) {
    require_once 'auth.php';
}
function usuarioLogueado() { return false; }

// 🛒 INICIAR SESIÓN Y CONTAR CARRITO (SIN WARNINGS)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$total_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $cantidad) {
        if (is_numeric($cantidad)) {
            $total_carrito += (int)$cantidad;
        }
    }
}
?>
<header>
    <link rel="stylesheet" href="../assets/css/style.css?v=45">
    <nav class="nav-container">
        <a href="/" class="logo">
         <img src="/assets/images/logo.png" alt="Bistro Coffee Logo" class="logo-img">
        </a>
        <a href="../index.php" class="logo">Bistro & <span>Coffee</span></a>
        
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="../pages/menu.php">Menú</a></li>
            <li><a href="../pages/reservas.php"><i class="fas fa-calendar-check"></i> Reservas</a></li>
            
            <li class="cart-item">
                <a href="../pages/carrito.php" class="cart-link" title="Carrito (<?= $total_carrito ?> items)">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($total_carrito > 0): ?>
                        <span class="cart-badge" data-count="<?= $total_carrito ?>">
                            <?= $total_carrito > 99 ? '99+' : $total_carrito ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li>
                <?php if (usuarioLogueado()): ?>
                    <a href="../pages/dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
                <?php else: ?>
                    <a href="../pages/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
</header>

<script>
window.actualizarCarrito = function(total) {
    const badges = document.querySelectorAll('.cart-badge');
    badges.forEach(badge => {
        badge.textContent = total > 99 ? '99+' : total;
        badge.dataset.count = total;
        badge.style.display = total > 0 ? 'flex' : 'none';
        badge.style.animation = 'none';
        setTimeout(() => badge.style.animation = 'cartBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)');
    });
    
    if (total > 0 && badges.length === 0) {
        const cartLink = document.querySelector('.cart-link');
        if (cartLink && !cartLink.querySelector('.cart-badge')) {
            const badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.dataset.count = total;
            badge.textContent = total > 99 ? '99+' : total;
            cartLink.appendChild(badge);
        }
    }
};

// Actualizar carrito al cargar la página
if (typeof fetch !== 'undefined') {
    fetch('pages/carrito.php?action=count')
        .then(r => r.json())
        .then(data => window.actualizarCarrito(data.total_items || 0))
        .catch(() => {}); // Ignorar errores de fetch
}
</script>