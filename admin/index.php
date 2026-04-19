<?php 
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!usuarioLogueado()) {
    header('Location: ../pages/login.php');
    exit;
}

$usuario = getUsuarioActual($conn);
if (!$usuario || $usuario['rol_nombre'] !== 'Administrador') {
    die('❌ Acceso solo para Administradores');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Bistro & Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: var(--bg-section-light);">
    
    <!-- SIDEBAR -->
    <aside class="sidebar" style="position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: linear-gradient(180deg, var(--jet-black) 0%, var(--black) 100%); padding: 2rem 0; z-index: 1000; box-shadow: 4px 0 20px var(--shadow-heavy);">
        <div class="sidebar-header" style="padding: 0 2rem 2rem; border-bottom: 1px solid rgba(169,146,125,0.2);">
            <h2 style="color: var(--white-smoke); font-size: 1.6rem; font-weight: 800;">Admin Panel</h2>
            <div style="color: var(--dusty-taupe); font-size: 0.9rem; margin-top: 0.5rem;">
                <?= htmlspecialchars($usuario['Nombre']) ?>
            </div>
        </div>
        
        <nav class="sidebar-nav" style="padding: 2rem 0;">
            <a href="index.php" class="nav-item active" style="display: block; color: var(--white-smoke); padding: 1rem 2rem; text-decoration: none; border-left: 4px solid transparent; transition: all 0.3s;">
                <i class="fas fa-tachometer-alt" style="width: 20px; margin-right: 1rem;"></i> Dashboard
            </a>
            <a href="menu-gestion.php" class="nav-item" style="display: block; color: var(--white-smoke); padding: 1rem 2rem; text-decoration: none; border-left: 4px solid transparent; transition: all 0.3s;">
                <i class="fas fa-utensils" style="width: 20px; margin-right: 1rem;"></i> Gestión Menú
            </a>
            <a href="reservas-admin.php" class="nav-item" style="display: block; color: var(--white-smoke); padding: 1rem 2rem; text-decoration: none; border-left: 4px solid transparent; transition: all 0.3s;">
                <i class="fas fa-calendar-check" style="width: 20px; margin-right: 1rem;"></i> Reservas
            </a>
            <a href="reportes.php" class="nav-item" style="display: block; color: var(--white-smoke); padding: 1rem 2rem; text-decoration: none; border-left: 4px solid transparent; transition: all 0.3s;">
                <i class="fas fa-chart-bar" style="width: 20px; margin-right: 1rem;"></i> Reportes
            </a>
            <a href="../pages/logout.php" class="nav-item" style="display: block; color: var(--white-smoke); padding: 1rem 2rem; text-decoration: none; border-left: 4px solid transparent; transition: all 0.3s; margin-top: auto;">
                <i class="fas fa-sign-out-alt" style="width: 20px; margin-right: 1rem;"></i> Cerrar Sesión
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-main" style="margin-left: 280px; padding: 2rem; min-height: 100vh;">
        <header class="admin-header" style="margin-bottom: 3rem; padding-bottom: 1.5rem; border-bottom: 2px solid rgba(169,146,125,0.2);">
            <h1 style="color: var(--text-primary); font-size: 2.5rem; margin: 0;">
                <i class="fas fa-tachometer-alt" style="color: var(--dusty-taupe); margin-right: 1rem;"></i>
                Dashboard Administrativo
            </h1>
            <div style="color: var(--text-secondary); margin-top: 0.5rem;">
                Panel de control • <?= date('d/m/Y H:i') ?>
            </div>
        </header>

        <!-- STATS CARDS -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
            
            <?php
            // Stats reales de tu DB
            $stats = [
                'productos' => db_fetch_one($conn, "SELECT COUNT(*) as total FROM Productos")['total'],
                'reservas' => db_fetch_one($conn, "SELECT COUNT(*) as total FROM Reservas WHERE Estado = 'Pendiente'")['total'] ?? 0,
                'usuarios' => db_fetch_one($conn, "SELECT COUNT(*) as total FROM Usuarios WHERE Estado = 1")['total']
            ];
            ?>

            <div class="stat-card card">
                <div class="stat-icon" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1rem;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-number" style="font-size: 2.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem;">
                    <?= $stats['productos'] ?>
                </div>
                <div class="stat-label" style="color: var(--text-secondary);">Productos en Menú</div>
            </div>

            <div class="stat-card card">
                <div class="stat-icon" style="font-size: 3rem; color: var(--stone-brown); margin-bottom: 1rem;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number" style="font-size: 2.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem;">
                    <?= $stats['reservas'] ?>
                </div>
                <div class="stat-label" style="color: var(--text-secondary);">Reservas Pendientes</div>
            </div>

            <div class="stat-card card">
                <div class="stat-icon" style="font-size: 3rem; color: var(--dusty-taupe); margin-bottom: 1rem;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" style="font-size: 2.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem;">
                    <?= $stats['usuarios'] ?>
                </div>
                <div class="stat-label" style="color: var(--text-secondary);">Usuarios Activos</div>
            </div>

        </div>

        <!-- RECIENTES -->
        <div class="recent-section">
            <h3 style="color: var(--text-primary); margin-bottom: 2rem;">Reservas Recientes</h3>
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px var(--shadow-light);">
                    <thead>
                        <tr style="background: var(--dusty-taupe); color: var(--white-smoke);">
                            <th style="padding: 1.5rem 1rem; text-align: left;">Cliente</th>
                            <th style="padding: 1.5rem 1rem; text-align: left;">Fecha/Hora</th>
                            <th style="padding: 1.5rem 1rem; text-align: left;">Personas</th>
                            <th style="padding: 1.5rem 1rem; text-align: left;">Estado</th>
                            <th style="padding: 1.5rem 1rem; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <!-- TABLA RESERVAS CORREGIDA -->
<tbody>
    <?php if (empty($reservas)): ?>
        <tr>
            <td colspan="5" style="padding: 3rem; color: var(--text-light); text-align: center;">
                No hay reservas recientes
            </td>
        </tr>
    <?php else: 
        foreach ($reservas as $reserva): 
            // ✅ FIX TERNARIO CON PARÉNTESIS
            $estado_style = '';
            if ($reserva['Estado'] == 'Pendiente') {
                $estado_style = 'background: #fff3cd; color: #856404;';
            } elseif ($reserva['Estado'] == 'Confirmada') {
                $estado_style = 'background: #d1ecf1; color: #0c5460;';
            } else {
                $estado_style = 'background: #f8d7da; color: #721c24;';
            }
        ?>
        <tr style="border-bottom: 1px solid rgba(169,146,125,0.1);">
            <td style="padding: 1.5rem 1rem; font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($reserva['Nombre']) ?></td>
            <td style="padding: 1.5rem 1rem; color: var(--text-secondary);"><?= date('d/m H:i', strtotime($reserva['Fecha'] . ' ' . $reserva['Hora'])) ?></td>
            <td style="padding: 1.5rem 1rem; font-weight: 600; color: var(--dusty-taupe);"><?= $reserva['Personas'] ?>p</td>
            <td style="padding: 1.5rem 1rem;">
                <span style="padding: 0.5rem 1.2rem; border-radius: 25px; font-size: 0.85rem; font-weight: 600; <?= $estado_style ?>">
                    <?= htmlspecialchars($reserva['Estado']) ?>
                </span>
            </td>
            <td style="padding: 1.5rem 1rem; text-align: right;">
                <a href="#" style="color: var(--dusty-taupe); margin-right: 1rem; padding: 0.5rem; border-radius: 6px; transition: all 0.3s;" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="#" style="color: #dc3545; padding: 0.5rem; border-radius: 6px; transition: all 0.3s;" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </a>
            </td>
        </tr>
    <?php endforeach; endif; ?>
</tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    // Sidebar hover
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.background = 'rgba(169,146,125,0.15)';
            this.style.borderLeftColor = 'var(--dusty-taupe)';
            this.style.transform = 'translateX(8px)';
        });
        item.addEventListener('mouseleave', function() {
            this.style.background = '';
            this.style.borderLeftColor = 'transparent';
            this.style.transform = '';
        });
    });
    </script>

    <style>
    .nav-item.active {
        background: rgba(169,146,125,0.2);
        border-left-color: var(--dusty-taupe);
    }
    .stat-card:hover {
        transform: translateY(-6px);
    }
    @media (max-width: 1024px) {
        .sidebar { transform: translateX(-100%); }
        .admin-main { margin-left: 0; }
    }
    </style>
</body>
</html>