<?php 
require_once '../config/database.php';
require_once '../includes/auth.php';

// Crear usuarios de prueba
crearUsuariosPrueba($conn);

if (usuarioLogueado()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_POST) {
    $usuario = login($conn, $_POST['correo'], $_POST['password']);
    if ($usuario) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Credenciales incorrectas';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bistro & Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, var(--jet-black) 0%, var(--black) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    
    <div class="login-container" style="max-width: 420px; width: 100%; padding: 2rem;">
        <div class="login-card card" style="padding: 3rem; text-align: center;">
            <div class="logo-large" style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, var(--dusty-taupe), var(--stone-brown)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 2rem;">
                Bistro & Coffee
            </div>
            
            <h2 style="color: var(--text-primary); margin-bottom: 1.5rem;">Acceso al Sistema</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2.5rem;">Inicia sesión con tu cuenta</p>
            
            <?php if ($error): ?>
                <div class="alert-error" style="background: #fee; color: #c33; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #f66;">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert-success" style="background: #efe; color: #060; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #6c6;">
                    Sesión cerrada correctamente
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-primary);">Correo</label>
                    <input type="email" name="correo" required 
                           style="width: 100%; padding: 1.25rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 12px; font-size: 1.1rem; transition: all 0.3s;"
                           placeholder="ejemplo@bistro.com">
                </div>
                
                <div style="margin-bottom: 2.5rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-primary);">Contraseña</label>
                    <input type="password" name="password" required 
                           style="width: 100%; padding: 1.25rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 12px; font-size: 1.1rem; transition: all 0.3s;"
                           placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn" style="width: 100%; padding: 1.25rem; font-size: 1.1rem; font-weight: 700;">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <div style="margin-top: 2.5rem; padding-top: 2rem; border-top: 1px solid rgba(169,146,125,0.2);">
                <div class="demo-accounts" style="color: var(--text-light); font-size: 0.9rem;">
                    <strong>Cuentas Demo:</strong><br>
                    <span>gerente@bistro.com / admin123</span><br>
                    <span>cajero@bistro.com / cajero123</span><br>
                    <span>chef@bistro.com / chef123</span>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Focus en email
    document.querySelector('input[name="correo"]').focus();
    
    // Enter en password = submit
    document.querySelector('input[name="password"]').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') this.form.submit();
    });
    </script>
</body>
</html>