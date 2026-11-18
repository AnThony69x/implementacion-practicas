<?php
// Servicio de usuarios independiente: php -S 127.0.0.1:8001 laboratorio/servicios/usuarios.php
header('Content-Type: application/json');
if (preg_match('#^/usuarios/(\d+)$#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $m)) {
    echo json_encode(['id' => (int) $m[1], 'nombre' => 'Ana']);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'no encontrado']);
}
