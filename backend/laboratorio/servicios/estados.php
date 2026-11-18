<?php
// Servicio de estados independiente: php -S 127.0.0.1:8002 laboratorio/servicios/estados.php
header('Content-Type: application/json');
if (preg_match('#^/estados/(\d+)$#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $m)) {
    echo json_encode(['solicitud_id' => (int) $m[1], 'estado' => 'pendiente']);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'no encontrado']);
}
