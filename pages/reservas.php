<?php 
require_once '../config/database.php';
require_once '../includes/reservas-functions.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$disponibilidades = getDisponibilidades($conn, $fecha);
$mensaje = '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - Bistro & Coffee</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- HERO RESERVAS -->
    <section class="hero-reservas" style="background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%); color: white; padding: 5rem 2rem; text-align: center;">
        <div class="container">
            <h1 style="font-size: 3.5rem; margin-bottom: 1rem; color: var(--stone-brown);"><i class="fas fa-calendar-check"></i> Reserva tu Mesa</h1>
            <p style="font-size: 1.3rem; color: var(--stone-brown);">Disponibilidad en tiempo real • Confirmación inmediata</p>
        </div>
    </section>

    <!-- CALENDARIO + DISPONIBILIDAD -->
    <section class="disponibilidad" style="padding: 4rem 2rem; background: var(--light);">
        <div class="container">
            <div class="calendario-selector" style="max-width: 400px; margin: 0 auto 3rem;">
                <input type="date" id="fecha-selector" value="<?= $fecha ?>" style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 15px; font-size: 1.1rem; text-align: center;">
            </div>

            <!-- ESTADO DISPONIBILIDAD -->
            <div class="estado-disponibilidad text-center mb-5">
                <div class="fecha-info" style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; color: var(--stone-brown);">
                    📅 <?= date('d/m/Y', strtotime($fecha)) ?>
                </div>
                <div class="status" style="font-size: 1.2rem; padding: 1rem 2rem; border-radius: 50px; display: inline-block;
                    <?= count($disponibilidades) == 0 ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;' ?>">
                    <?= count($disponibilidades) == 0 ? '✅ Todas las mesas disponibles' : '⚠️ ' . count($disponibilidades) . ' reserva' . (count($disponibilidades) > 1 ? 's' : '') . ' registrada' . (count($disponibilidades) > 1 ? 's' : '') ?>
                </div>
            </div>
        </div>
    </section>

    <!-- FORMULARIO RESERVA -->
    <section class="formulario-reserva" style="padding: 4rem 2rem;">
        <div class="container">
            <div class="form-container card" style="max-width: 600px; margin: 0 auto;">
                <h2 style="text-align: center; margin-bottom: 2rem; color: var(--dark);">
                    <i class="fas fa-chair"></i> Completa tu reserva
                </h2>
                
                <form action="reservas-procesar.php" method="POST" id="form-reserva">
                    <input type="hidden" name="fecha" value="<?= $fecha ?>">
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Nombre completo</label>
                            <input type="text" name="nombre" required  style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Teléfono</label>
                            <input type="tel" name="telefono" required style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;">
                        </div>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Correo (opcional)</label>
                            <input type="email" name="correo" style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">👥 Nº Personas</label>
                            <select name="personas" required style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;">
                                <option value="1">1 persona</option>
                                <option value="2">2 personas</option>
                                <option value="3">3 personas</option>
                                <option value="4">4 personas</option>
                                <option value="5">5+ personas</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">🕒 Hora</label>
                            <select name="hora" required style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;">
                                <option value="09:00">09:00</option>
                                <option value="10:00">10:00</option>
                                <option value="11:00">11:00</option>
                                <option value="12:00">12:00</option>
                                <option value="13:00">13:00</option>
                                <option value="14:00">14:00</option>
                                <option value="15:00">15:00</option>
                                <option value="16:00">16:00</option>
                                <option value="17:00">17:00</option>
                                <option value="18:00">18:00</option>
                                <option value="19:00">19:00</option>
                                <option value="20:00">20:00</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">⏱️ Duración aprox.</label>
                            <select name="duracion" style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;">
                                <option value="60">1 hora</option>
                                <option value="90" selected>1.5 horas</option>
                                <option value="120">2 horas</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Notas especiales</label>
                        <textarea name="notas" rows="3" style="width: 100%; padding: 1rem; border: 2px solid var(--dusty-taupe); border-radius: 10px; font-size: 1rem; resize: vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-reservar">
                        <i class="fas fa-check"></i> ¡Reservar Mesa Ahora!
                    </button>
                </form>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
    // Selector fecha
    document.getElementById('fecha-selector').addEventListener('change', function() {
        const fecha = this.value;
        if (fecha) {
            window.location.href = `?fecha=${fecha}`;
        }
    });

    // Validación form
    document.getElementById('form-reserva').addEventListener('submit', function(e) {
        const personas = document.querySelector('select[name="personas"]').value;
        if (personas > 5) {
            alert('Para más de 5 personas, contáctanos directamente');
            e.preventDefault();
            return false;
        }
    });
    </script>
</body>
</html>