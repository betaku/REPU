<?php
header('Content-Type: application/json');
session_start();

if (isset($_SESSION['idUsuario'])) {
    echo json_encode([
        "status" => "success",
        "idUsuario" => $_SESSION['idUsuario'],
        "nombre" => $_SESSION['nombre'] ?? 'Usuario',
        "correo" => $_SESSION['correo'] ?? '',
        "tipo" => $_SESSION['tipo'] ?? 'ciudadano' // Enviamos el rol al frontend
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No hay sesión activa"
    ]);
}
?>