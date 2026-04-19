<?php
// Helpers globales
require_once 'includes/functions.php';
function safe_array_get($array, $key, $default = '') {
    return $array[$key] ?? $default;
}

function debug_sql($conn, $sql, $params = []) {
    if (isset($_GET['debug'])) {
        echo "<pre style='background: #f5f5f5; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
        echo "SQL: " . $sql . "<br>";
        echo "Params: " . print_r($params, true);
        echo "</pre>";
    }
}

// Helpers para ternarios y estilos
function getEstadoStyle($estado) {
    switch ($estado) {
        case 'Pendiente': 
            return 'background: #fff3cd; color: #856404;';
        case 'Confirmada': 
            return 'background: #d1ecf1; color: #0c5460;';
        case 'Cancelada': 
            return 'background: #f8d7da; color: #721c24;';
        case 'Completada':
            return 'background: #d4edda; color: #155724;';
        default:
            return 'background: #e2e3e5; color: #495057;';
    }
}

function formatPrecio($precio) {
    return '$' . number_format($precio, 2);
}

function formatFecha($fecha, $formato = 'd/m/Y H:i') {
    return date($formato, strtotime($fecha));
}
?>

