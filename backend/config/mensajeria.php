<?php

return [
    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'cola_notificaciones' => env('RABBITMQ_COLA_NOTIFICACIONES', 'notificaciones.pedido'),
    // Segundos que tarda el envio simulado de la notificacion (proceso lento)
    'segundos_envio' => (int) env('NOTIFICACION_SEGUNDOS_ENVIO', 3),
];
