<?php
session_start();
require_once '../config/database.php';

define('ADMIN_PATH', '../admin/');

// Verificar si usuario está logueado
function usuarioLogueado() {
    return isset($_SESSION['usuario_id']);
}

function getUsuarioActual($conn) {
    if (!usuarioLogueado()) return null;
    
    $sql = "SELECT u.*, r.Nombre as rol_nombre 
            FROM Usuarios u 
            INNER JOIN Roles r ON u.Id_Rol = r.Id_Rol 
            WHERE u.Id_Usuario = ? AND u.Estado = 1";
    return db_fetch_one($conn, $sql, [$_SESSION['usuario_id']]);
}

function requiereLogin() {
    if (!usuarioLogueado()) {
        header('Location: ../pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requiereRol($conn, $roles_permitidos) {
    requiereLogin(); // Verifica login primero
    
    $usuario = getUsuarioActual($conn);
    if (!$usuario) {
        header('Location: login.php?error=session');
        exit;
    }
    
    if (!in_array($usuario['rol_nombre'], (array)$roles_permitidos)) {
        die('❌ Acceso denegado. Requiere rol: ' . implode(', ', $roles_permitidos));
    }
    
    return $usuario;
}
// Login
function login($conn, $correo, $password) {
    $sql = "SELECT * FROM Usuarios WHERE Correo = ? AND Estado = 1";
    $usuario = db_fetch_one($conn, $sql, [$correo]);
    
    if ($usuario && password_verify($password, $usuario['Contrasena'])) {
        $_SESSION['usuario_id'] = $usuario['Id_Usuario'];
        $_SESSION['rol'] = $usuario['Id_Rol'];
        return $usuario;
    }
    return false;
}

// Logout
function logout() {
    session_destroy();
    header('Location: ../pages/login.php?logout=1');
    exit;
}

// ✅ FIXED: Crear usuarios de prueba (SQL Server compatible)
function crearUsuariosPrueba($conn) {
    try {
        // Roles
        $roles = ['Administrador', 'Cajero', 'Chef', 'Cliente'];
        foreach ($roles as $rol) {
            $sql = "IF NOT EXISTS (SELECT 1 FROM Roles WHERE Nombre = ?)
                    INSERT INTO Roles (Nombre) VALUES (?)";
            db_query($conn, $sql, [$rol, $rol]);
        }
        
        // Usuarios demo
        $usuarios = [
            ['Gerente Bistro', 'gerente@bistro.com', 'admin123', 'Administrador'],
            ['Cajero Ana', 'cajero@bistro.com', 'cajero123', 'Cajero'],
            ['Chef Luis', 'chef@bistro.com', 'chef123', 'Chef'],
            ['Cliente María', 'cliente@bistro.com', 'cliente123', 'Cliente']
        ];
        
        foreach ($usuarios as $u) {
            $hash = password_verify($u[2], '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') ?: password_hash($u[2], PASSWORD_DEFAULT);
            
            // Verificar si rol existe
            $rol_id_sql = "SELECT Id_Rol FROM Roles WHERE Nombre = ?";
            $rol_id = db_fetch_one($conn, $rol_id_sql, [$u[3]])['Id_Rol'];
            
            if ($rol_id) {
                $sql = "IF NOT EXISTS (SELECT 1 FROM Usuarios WHERE Correo = ?)
                        INSERT INTO Usuarios (Nombre, Correo, Contrasena, Id_Rol, Estado) 
                        VALUES (?, ?, ?, 1)";
                db_query($conn, $sql, [$u[1], $u[0], $hash, $rol_id]);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error creando usuarios: " . $e->getMessage());
        return false;
    }
}
?>