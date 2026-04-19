<?php 
if (file_exists('auth.php')) {
    require_once 'auth.php';
}
function usuarioLogueado() { return false; } // Fallback
?>
<header>
    <nav class="nav-container">
        <link rel="stylesheet" href="../assets/css/style.css?v=42">
        <a href="/" class="logo">
        <img src="/assets/images/logo-oficial.jpg" alt="Bistro Coffee" class="logo-img">
        </a>
        <a href="index.php" class="logo">Bistro & <span>Coffee</span></a>
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="pages/menu.php">Menú</a></li>
            <li><a href="pages/reservas.php"><i class="fas fa-calendar-check"></i> Reservas</a></li>
            <li><a href="pages/pedidos.php">Pedidos</a></li>
            <li>
    <?php if (usuarioLogueado()): ?>
        <a href="pages/dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
    <?php else: ?>
        <a href="pages/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
    <?php endif; ?>
</li>
        </ul>
    </nav>
</header>