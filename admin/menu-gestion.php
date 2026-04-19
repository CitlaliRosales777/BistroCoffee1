<?php 
require_once '../config/database.php';
require_once '../includes/auth.php';

$usuario = requiereRol($conn, ['Administrador']);

// Inicializar variables
$mensaje = '';
$edit_id = null;
$edit_producto = null;

// **NUEVO: Crear carpetas para imágenes**
$upload_dir = '../uploads/productos/';
$public_dir = '../assets/images/productos/';
if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
if (!file_exists($public_dir)) mkdir($public_dir, 0777, true);

// **NUEVO: Manejar subida de imagen**
$nueva_imagen = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
    $file_name = uniqid() . '_' . $_FILES['imagen']['name'];
    $target_file = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target_file)) {
        copy($target_file, $public_dir . $file_name);
        $nueva_imagen = $file_name;
    }
}

// **COMPLETO: Manejar todas las acciones**
if ($_POST) {
    if (isset($_POST['agregar'])) {
        // AGREGAR: Requiere imagen nueva
        if ($nueva_imagen) {
            $sql = "INSERT INTO Productos (Nombre, Descripcion, Precio_Venta, Imagen) VALUES (?, ?, ?, ?)";
            if (db_query($conn, $sql, [$_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $nueva_imagen])) {
                $mensaje = '✅ Producto agregado con imagen al menú';
            } else {
                $mensaje = '❌ Error al agregar producto';
            }
        } else {
            $mensaje = '❌ Debe subir una imagen para el producto';
        }
        
    } elseif (isset($_POST['editar'])) {
        // EDITAR: Mantiene imagen actual si no suben nueva
        $imagen_actual = db_fetch_one($conn, "SELECT Imagen FROM Productos WHERE Id_Producto = ?", [$_POST['id']]);
        $imagen_final = $nueva_imagen ?: $imagen_actual['Imagen'];
        
        $sql = "UPDATE Productos SET Nombre = ?, Descripcion = ?, Precio_Venta = ?, Imagen = ? WHERE Id_Producto = ?";
        if (db_query($conn, $sql, [$_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $imagen_final, $_POST['id']])) {
            $mensaje = '✅ Producto actualizado' . ($nueva_imagen ? ' con nueva imagen' : '');
        } else {
            $mensaje = '❌ Error al actualizar producto';
        }
        
    } elseif (isset($_POST['eliminar'])) {
        // ELIMINAR: Borrar imagen también
        $producto = db_fetch_one($conn, "SELECT Imagen FROM Productos WHERE Id_Producto = ?", [$_POST['id']]);
        if ($producto && $producto['Imagen']) {
            @unlink($upload_dir . $producto['Imagen']);
            @unlink($public_dir . $producto['Imagen']);
        }
        $sql = "DELETE FROM Productos WHERE Id_Producto = ?";
        if (db_query($conn, $sql, [$_POST['id']])) {
            $mensaje = 'Producto y imagen eliminados del menú';
        } else {
            $mensaje = 'Error al eliminar producto';
        }
    }
    
    // Redirigir para evitar reenvío
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Obtener productos
$productos = db_fetch_all($conn, "SELECT * FROM Productos ORDER BY Id_Producto DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Menú - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: var(--bg-section-light);">
    
    <!-- SIDEBAR -->
    <?php include 'index.php'; ?>

    <main class="admin-main" style="margin-left: 280px; padding: 2rem; min-height: 100vh;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
            <h1 style="color: var(--text-primary); font-size: 2.2rem; margin: 0;">
                <i class="fas fa-utensils" style="color: var(--dusty-taupe); margin-right: 1rem;"></i>
                Gestión del Menú
            </h1>
            <div style="display: flex; gap: 1rem;">
                <a href="index.php" class="btn" style="background: var(--jet-black); padding: 1rem 2rem;">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- MENSAJE -->
        <?php if ($mensaje): ?>
            <div class="alert" style="background: #d4edda; color: #155724; padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 2rem; border-left: 5px solid #28a745; font-weight: 500;">
                <?= $mensaje ?>
                <button onclick="this.parentElement.style.display='none'" style="float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer;">×</button>
            </div>
        <?php endif; ?>

        <!-- FORM AGREGAR/EDITAR PRODUCTO -->
        <div class="form-section card" style="margin-bottom: 3rem; padding: 2.5rem;">
            <h3 style="color: var(--text-primary); margin-bottom: 2rem; font-size: 1.5rem;">
                ➕ Agregar Nuevo Producto
            </h3>
            <form method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; max-width: 1200px;">
                <div>
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-primary);">Nombre del Producto</label>
                    <input type="text" name="nombre" required 
                           placeholder="Ej: Pancakes Clásicos" 
                           style="width: 100%; padding: 1.2rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 12px; font-size: 1rem;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-primary);">Precio ($)</label>
                    <input type="number" name="precio" step="0.01" min="0" required 
                           placeholder="85.00" 
                           style="width: 100%; padding: 1.2rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 12px; font-size: 1rem;">
                </div>
                
                <div style="grid-column: span 3;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-primary);">Descripción</label>
                    <textarea name="descripcion" rows="3" 
                              placeholder="Descripción detallada del producto..." 
                              style="width: 100%; padding: 1.2rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 12px; font-size: 1rem; resize: vertical; font-family: inherit;"></textarea>
                </div>

                <!-- **NUEVO: Campo de imagen** -->
                <div style="grid-column: span 3;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-primary);">Imagen del Producto <span style="color: #dc3545;">*</span></label>
                    <input type="file" name="imagen" accept="image/*" required 
                           style="width: 100%; padding: 1.2rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 12px; font-size: 1rem;">
                    <small style="color: var(--text-secondary); font-size: 0.9rem;">Formatos: JPG, PNG, WebP (máx 5MB)</small>
                </div>
                
                <div style="grid-column: span 3;">
                    <button type="submit" name="agregar" class="btn" style="width: 220px; padding: 1.2rem 2.5rem; font-size: 1.1rem; font-weight: 700;">
                        <i class="fas fa-plus-circle"></i> Agregar al Menú
                    </button>
                </div>
            </form>
        </div>

        <!-- TABLA PRODUCTOS -->
        <div class="table-section card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="color: var(--text-primary); margin: 0; font-size: 1.5rem;">
                    📋 Productos del Menú (<?= count($productos) ?>)
                </h3>
            </div>
            
            <?php if (empty($productos)): ?>
                <div style="text-align: center; padding: 4rem 2rem; color: var(--text-light);">
                    <i class="fas fa-utensils" style="font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.5;"></i>
                    <h4>No hay productos en el menú</h4>
                    <p>Agrega el primer producto usando el formulario de arriba</p>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, var(--dusty-taupe), var(--stone-brown)); color: var(--white-smoke);">
                                <th style="padding: 1.5rem 1.5rem 1.5rem 2rem; text-align: left; font-weight: 600;">Imagen</th>
                                <th style="padding: 1.5rem; text-align: left; font-weight: 600;">Producto</th>
                                <th style="padding: 1.5rem; text-align: left; font-weight: 600;">Descripción</th>
                                <th style="padding: 1.5rem; text-align: right; font-weight: 600;">Precio</th>
                                <th style="padding: 1.5rem 1rem; text-align: right; font-weight: 600;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr style="border-bottom: 1px solid rgba(169,146,125,0.1); transition: all 0.3s;">
                                <td style="padding: 1.5rem 1.5rem 1.5rem 2rem; background: rgba(169,146,125,0.05);">
                                    <?php if ($producto['Imagen']): ?>
                                        <img src="../assets/images/productos/<?= htmlspecialchars($producto['Imagen']) ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 12px; border: 3px solid rgba(169,146,125,0.2);">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: rgba(169,146,125,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-light);">
                                            <i class="fas fa-image" style="font-size: 1.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1.5rem; font-weight: 600; color: var(--text-primary);">
                                    <?= htmlspecialchars($producto['Nombre']) ?>
                                </td>
                                <td style="padding: 1.5rem; color: var(--text-secondary); max-width: 300px;">
                                    <?= htmlspecialchars($producto['Descripcion'] ? substr($producto['Descripcion'], 0, 80) . '...' : 'Sin descripción') ?>
                                </td>
                                <td style="padding: 1.5rem; text-align: right; font-weight: 700; color: var(--dusty-taupe); font-size: 1.2rem;">
                                    $<?= number_format($producto['Precio_Venta'], 2) ?>
                                </td>
                                <td style="padding: 1.5rem 1rem; text-align: right; white-space: nowrap;">
                                    <button onclick="editarProducto(<?= $producto['Id_Producto'] ?>, '<?= addslashes($producto['Nombre']) ?>', '<?= addslashes($producto['Descripcion'] ?? '') ?>', <?= $producto['Precio_Venta'] ?>, '<?= $producto['Imagen'] ?? '' ?>')" 
                                            class="btn-edit" style="background: #28a745; color: white; border: none; padding: 0.7rem 1.2rem; border-radius: 8px; margin-right: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar <?= htmlspecialchars($producto['Nombre']) ?> y su imagen?')">
                                        <input type="hidden" name="id" value="<?= $producto['Id_Producto'] ?>">
                                        <button type="submit" name="eliminar" class="btn-delete" style="background: #dc3545; color: white; border: none; padding: 0.7rem 1.2rem; border-radius: 8px; cursor: pointer; font-size: 0.9rem;">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- **MEJORADO: MODAL EDITAR con imagen** -->
    <div id="modal-editar" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div class="modal-content card" style="width: 90%; max-width: 700px; border-radius: 16px; padding: 2.5rem; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="color: var(--text-primary); margin: 0;">✏️ Editar Producto</h3>
                <button onclick="cerrarModal()" style="background: none; border: none; font-size: 2rem; cursor: pointer; color: var(--text-light);">×</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit-id">
                <input type="hidden" name="imagen_actual" id="edit-imagen-actual">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">Nombre</label>
                        <input type="text" name="nombre" id="edit-nombre" required style="width: 100%; padding: 1rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 10px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">Precio ($)</label>
                        <input type="number" name="precio" id="edit-precio" step="0.01" required style="width: 100%; padding: 1rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 10px;">
                    </div>
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">Descripción</label>
                    <textarea name="descripcion" id="edit-descripcion" rows="4" style="width: 100%; padding: 1rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 10px; resize: vertical;"></textarea>
                </div>

                <!-- **NUEVO: Sección de imagen en modal** -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">Nueva Imagen (opcional)</label>
                    <input type="file" name="imagen" accept="image/*" style="width: 100%; padding: 1rem; border: 2px solid rgba(169,146,125,0.3); border-radius: 10px;">
                                        <div id="imagen-actual" style="margin-top: 1rem; display: none;">
                        <small style="color: var(--text-secondary);">Imagen actual:</small><br>
                        <img id="img-preview" src="" style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 3px solid rgba(169,146,125,0.2); margin-top: 0.5rem;">
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModal()" style="padding: 1rem 2rem; border: 2px solid rgba(169,146,125,0.3); background: white; border-radius: 10px; cursor: pointer;">Cancelar</button>
                    <button type="submit" name="editar" class="btn" style="padding: 1rem 2rem; background: var(--dusty-taupe); color: white;">
                        <i class="fas fa-save"></i> Actualizar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarProducto(id, nombre, descripcion, precio, imagen_actual) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nombre').value = nombre;
        document.getElementById('edit-descripcion').value = descripcion;
        document.getElementById('edit-precio').value = precio;
        document.getElementById('edit-imagen-actual').value = imagen_actual;
        
        // Mostrar imagen actual si existe
        const imgActual = document.getElementById('imagen-actual');
        const imgPreview = document.getElementById('img-preview');
        if (imagen_actual) {
            imgPreview.src = '../assets/images/productos/' + imagen_actual;
            imgActual.style.display = 'block';
        } else {
            imgActual.style.display = 'none';
        }
        
        document.getElementById('modal-editar').style.display = 'flex';
    }
    
    function cerrarModal() {
        document.getElementById('modal-editar').style.display = 'none';
    }
    
    // Cerrar modal clic fuera
    document.getElementById('modal-editar').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });
    </script>

    <style>
    tr:hover {
        background: rgba(169,146,125,0.05);
        transform: scale(1.01);
    }
    .btn-edit:hover { 
        background: #218838 !important; 
        transform: scale(1.05); 
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    .btn-delete:hover { 
        background: #c82333 !important; 
        transform: scale(1.05); 
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }
    .modal-content {
        animation: modalSlideIn 0.3s ease-out;
    }
    @keyframes modalSlideIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>
</body>
</html>