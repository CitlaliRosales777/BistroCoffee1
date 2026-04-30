<?php 
require_once '../config/database.php';
require_once '../includes/reservas-functions.php';
session_start();

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$hoy = date('Y-m-d');
$maxDiasFuturo = date('Y-m-d', strtotime('+30 days'));

if (strtotime($fecha) < strtotime($hoy) || strtotime($fecha) > strtotime($maxDiasFuturo)) {
    $fecha = $hoy;
}

$horasLaborables = ['12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];
$disponibilidades = getDisponibilidades($conn, $fecha);

$totalOcupadas = 0;
$horasDisponibles = [];
foreach($horasLaborables as $hora) {
    if (contarReservasPorHora($disponibilidades, $hora) > 0) $totalOcupadas++;
    else $horasDisponibles[] = $hora;
}
$totalDisponibles = count($horasLaborables) - $totalOcupadas;

debugReservas($conn, $fecha);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - Bistro & Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    

    <section class="hero-reservas" style="background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%); color: white; padding: 5rem 2rem; text-align: center;">
        <div class="container">
            <h1 style="font-size: 3.5rem; margin-bottom: 1rem; color: var(--stone-brown);"><i class="fas fa-calendar-check"></i> Reserva tu Mesa</h1>
            <p style="font-size: 1.3rem; color: var(--stone-brown);">🕒 Máximo 1 mesa por horario • Real-time</p>
        </div>
    </section>

    <section class="disponibilidad" style="padding: 4rem 2rem; background: var(--light);">
        <div class="container">
            <div class="calendario-selector" style="max-width: 400px; margin: 0 auto 3rem;">
                <input type="date" id="fecha-selector" value="<?= $fecha ?>" min="<?= $hoy ?>" max="<?= $maxDiasFuturo ?>"
                       style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 15px; font-size: 1.1rem; text-align: center;">
            </div>

            <div class="estado-disponibilidad text-center mb-5">
                <div style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; color: var(--stone-brown);">
                    📅 <?= date('d/m/Y', strtotime($fecha)) ?> 
                    <?php if ($fecha == $hoy): ?><span style="font-size: 1rem; background: #ffeb3b; padding: 0.25rem 0.75rem; border-radius: 20px;">🎯 HOY</span><?php endif; ?>
                </div>
                <div style="font-size: 1.2rem; padding: 1rem 2rem; border-radius: 50px; display: inline-block;
                    <?= $totalDisponibles > 0 ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;' ?>">
                    <?= $totalDisponibles > 0 ? '✅ ' . $totalDisponibles . '/' . count($horasLaborables) . ' disponibles' : '❌ Completó' ?>
                </div>
            </div>

            <div class="grid-horarios" style="max-width: 800px; margin: 0 auto 3rem; padding: 2rem; background: white; border-radius: 25px; box-shadow: 0 15px 40px rgba(0,0,0,0.1);">
                <h3 style="text-align: center; margin-bottom: 2rem; color: var(--dark);">🕒 Horarios disponibles</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1.2rem; justify-items: center;">
                    <?php foreach($horasLaborables as $hora): 
                        $reservasEnHora = contarReservasPorHora($disponibilidades, $hora);
                        $disponible = $reservasEnHora == 0;
                    ?>
                    <div style="
                        padding: 1.2rem 0.8rem; border-radius: 20px; text-align: center; font-weight: 600; min-width: 120px;
                        background: <?= $disponible ? '#d4edda' : '#f8d7da' ?>; 
                        color: <?= $disponible ? '#155724' : '#721c24' ?>;
                        box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: all 0.3s;
                        cursor: <?= $disponible ? 'pointer' : 'not-allowed' ?>; 
                        <?= $disponible ? 'border: 3px solid #28a745; transform: scale(1.05);' : 'opacity: 0.7' ?>
                    " onclick="<?= $disponible ? "selectHora('$hora')" : '' ?>"
                    title="<?= $disponible ? 'Disponible' : 'Ocupado (' . $reservasEnHora . '/1)' ?>">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= $disponible ? '✅' : '❌' ?></div>
                        <div style="font-size: 1.3rem; font-weight: 700;"><?= $hora ?></div>
                        <div style="font-size: 0.85rem;">
                            <?= $disponible ? 'Libre' : 'Ocupada' ?><br>
                            <small>(<?= $reservasEnHora ?>/1)</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="formulario-reserva" style="padding: 4rem 2rem;">
        <div class="container">
            <div class="form-container" style="max-width: 600px; margin: 0 auto; padding: 2.5rem; border-radius: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); background: white;">
                <h2 style="text-align: center; margin-bottom: 2rem; color: var(--dark);">
                    <i class="fas fa-chair"></i> Completa tu reserva
                </h2>

                <?php if (isset($_SESSION['mensaje'])): 
                    $tipo = $_SESSION['mensaje']['tipo'] ?? 'info';
                    $texto = $_SESSION['mensaje']['texto'] ?? $_SESSION['mensaje'];
                ?>
                <div style="
                    background: <?= $tipo == 'success' ? '#d4edda' : '#f8d7da' ?>;
                    color: <?= $tipo == 'success' ? '#155724' : '#721c24' ?>;
                    padding: 1.5rem; border-radius: 15px; margin-bottom: 2rem; 
                    border-left: 6px solid <?= $tipo == 'success' ? '#28a745' : '#dc3545' ?>;">
                    <i class="fas fa-<?= $tipo == 'success' ? 'check-circle' : 'exclamation-triangle' ?>" style="margin-right: 0.5rem;"></i>
                    <?= $texto ?>
                </div>
                <?php unset($_SESSION['mensaje']); endif; ?>

                <?php if ($totalDisponibles == 0): ?>
                    <div style="text-align: center; padding: 3rem; background: #f8d7da; border-radius: 15px; color: #721c24;">
                        <i class="fas fa-calendar-times" style="font-size: 4rem; margin-bottom: 1rem; color: #dc3545;"></i>
                        <h3 style="margin-bottom: 1rem;">Sin horarios disponibles</h3>
                        <p>Para <?= date('d/m/Y', strtotime($fecha)) ?></p>
                        <a href="?fecha=<?= date('Y-m-d', strtotime('+1 day')) ?>" style="background: var(--primary); color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 10px;">
                            📅 Probar mañana
                        </a>
                    </div>
                <?php else: ?>
                <form action="reservas-procesar.php" method="POST" id="form-reserva">
                    <input type="hidden" name="fecha" value="<?= $fecha ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div><label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">👤 Nombre *</label>
                            <input type="text" name="nombre" required style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 12px;">
                        </div>
                        <div><label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">📱 Teléfono *</label>
                            <input type="tel" name="telefono" required style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 12px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                        <div><label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">✉️ Correo</label>
                            <input type="email" name="correo" style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 12px;">
                        </div>
                        <div><label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">👥 Personas *</label>
                            <select name="personas" required style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 12px;">
                                <option value="2">2 personas</option><option value="3">3 personas</option><option value="4">4 personas</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 1.1rem;">🕒 Horario (<?= $totalDisponibles ?> disponibles)</label>
                        <select name="hora" id="hora" required style="width: 100%; padding: 1.3rem; border: 3px solid var(--dusty-taupe); border-radius: 15px; font-size: 1.1rem; font-weight: 600;">
                            <option value="">Selecciona horario</option>
                            <?php foreach($horasDisponibles as $hora): ?>
                            <option value="<?= $hora ?>"><?= $hora ?> ✅</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <textarea name="notas" rows="3" placeholder="Alergias, preferencias..." style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 12px; margin-bottom: 2rem; resize: vertical;"></textarea>
                    
                    <button type="submit" style="width: 100%; padding: 1.4rem; font-size: 1.2rem; font-weight: 700; border-radius: 15px; background: var(--primary); color: white; border: none; cursor: pointer;">
                        <i class="fas fa-calendar-check"></i> ¡Reservar Ahora!
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
    function selectHora(hora) {
        document.getElementById('hora').value = hora;
    }
    document.getElementById('fecha-selector').addEventListener('change', e => location.href = `?fecha=${e.target.value}`);
    </script>
</body>
</html>