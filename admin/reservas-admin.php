<?php 
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/reservas-functions.php';

$usuario = requiereRol($conn, ['Administrador']);

// ✅ INICIALIZAR variables (evita warnings)
$mensaje = '';
$filtros = [];

// Filtros GET
$filtros['fecha'] = $_GET['fecha'] ?? '';
$filtros['estado'] = $_GET['estado'] ?? '';
$filtros['busqueda'] = $_GET['buscar'] ?? '';

?>


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - Admin | Bistro Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #f4a261;
            --secondary: #e76f51;
            --dark: #264653;
        }
        .stats-card { transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-5px); }
        .status-badge { font-size: 0.8rem; }
        .table-actions .btn { margin: 0 1px; }
        body { background: #f8f9fa; }
    </style>
</head>
<body>
    <?php include 'index.php'; // Tu sidebar ?>

    <main class="container-fluid px-4 py-4" style="margin-left: 280px;">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-calendar-check text-primary me-2"></i>
                    Gestión de Reservas
                </h1>
                <small class="text-muted">Controla todas las reservas del restaurante</small>
            </div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-house me-2"></i>Volver Dashboard
            </a>
        </div>

        <!-- ALERTA -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card border-0 shadow h-100 text-white bg-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-3x mb-3 opacity-75"></i>
                        <h2 class="display-5 fw-bold"><?= $stats['total'] ?? 0 ?></h2>
                        <p class="mb-0">Total Reservas</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card border-0 shadow h-100 text-dark bg-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3 opacity-75"></i>
                        <h2 class="display-5 fw-bold"><?= $stats['pendientes'] ?? 0 ?></h2>
                        <p class="mb-0">Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card border-0 shadow h-100 text-white bg-success">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-day fa-3x mb-3 opacity-75"></i>
                        <h2 class="display-5 fw-bold"><?= $stats['confirmadas'] ?? 0 ?></h2>
                        <p class="mb-0">Confirmadas</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card border-0 shadow h-100 text-white bg-info">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-day fa-3x mb-3 opacity-75"></i>
                        <h2 class="display-5 fw-bold"><?= $stats['hoy'] ?? 0 ?></h2>
                        <p class="mb-0">Hoy</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="card shadow mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="fecha_filtro" value="<?= htmlspecialchars($filtros['fecha']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="estado_filtro">
                            <option value="">Todos</option>
                            <option value="Pendiente" <?= $filtros['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="Confirmada" <?= $filtros['estado'] == 'Confirmada' ? 'selected' : '' ?>>Confirmada</option>
                            <option value="Cancelada" <?= $filtros['estado'] == 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                            <option value="Completada" <?= $filtros['estado'] == 'Completada' ? 'selected' : '' ?>>Completada</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Buscar (Nombre/Tel)</label>
                        <input type="text" class="form-control" id="buscar_filtro" value="<?= htmlspecialchars($filtros['busqueda']) ?>" placeholder="Juan Pérez...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="filtrar()">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Reservas (<?= count($reservas ?? []) ?>)
                </h5>
                <a href="?limpiar=1" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-refresh"></i> Limpiar filtros
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reservas)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-times fa-4x mb-4 opacity-50"></i>
                        <h4>No hay reservas</h4>
                        <p class="lead"><?= !empty($filtros['fecha']) ? 'para ' . date('d/m/Y', strtotime($filtros['fecha'])) : 'que coincidan con filtros' ?></p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Personas</th>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Notas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas as $reserva): ?>
                            <tr>
                                <td><strong><?= $reserva['FechaFmt'] ?></strong></td>
                                <td><strong class="text-primary"><?= $reserva['Hora'] ?></strong></td>
                                <td>
                                    <span class="badge bg-info fs-6"><?= $reserva['Personas'] ?>p</span>
                                </td>
                                <td><?= htmlspecialchars($reserva['Nombre']) ?></td>
                                <td>
                                    <a href="tel:<?= htmlspecialchars($reserva['Telefono']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($reserva['Telefono']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php 
                                    $badgeClass = match($reserva['Estado']) {
                                        'Confirmada' => 'bg-success',
                                        'Pendiente' => 'bg-warning text-dark',
                                        'Completada' => 'bg-secondary',
                                        'Cancelada' => 'bg-danger',
                                        default => 'bg-light'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?> status-badge px-3 py-2">
                                        <?= $reserva['Estado'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($reserva['Notas'])): ?>
                                        <span class="text-muted small" title="<?= htmlspecialchars($reserva['Notas']) ?>">
                                            <i class="fas fa-note-sticky me-1"></i><?= strlen($reserva['Notas']) > 20 ? substr($reserva['Notas'], 0, 20) . '...' : $reserva['Notas'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <?php if ($reserva['Estado'] == 'Pendiente'): ?>
                                        <form method="POST" class="d-inline me-1">
                                            <input type="hidden" name="id" value="<?= $reserva['Id_Reserva'] ?>">
                                            <button type="submit" name="confirmar" class="btn btn-sm btn-success" title="Confirmar">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline me-1">
                                            <input type="hidden" name="id" value="<?= $reserva['Id_Reserva'] ?>">
                                            <button type="submit" name="cancelar" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Cancelar reserva?')" title="Cancelar">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($reserva['Estado'] == 'Confirmada'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $reserva['Id_Reserva'] ?>">
                                            <button type="submit" name="completar" class="btn btn-sm btn-outline-secondary" title="Marcar completada">
                                                <i class="fas fa-flag-checkered"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $reserva['Id_Reserva'] ?>">
                                        <button type="submit" name="eliminar" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar permanentemente?')" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
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
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function filtrar() {
        const fecha = document.getElementById('fecha_filtro').value;
        const estado = document.getElementById('estado_filtro').value;
        const buscar = document.getElementById('buscar_filtro').value;
        
        let params = new URLSearchParams();
        if (fecha) params.set('fecha', fecha);
        if (estado) params.set('estado', estado);
        if (buscar) params.set('buscar', buscar);
        
        window.location.href = 'reservas-admin.php?' + params.toString();
    }

    // Enter en búsqueda
    document.getElementById('buscar_filtro').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') filtrar();
    });
    </script>
</body>
</html>