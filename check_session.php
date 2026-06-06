<?php
// ═══════════════════════════════════════════════════════════════
// check_session.php - PROTEGER PÁGINAS QUE REQUIEREN LOGIN
// ═══════════════════════════════════════════════════════════════

session_start();

if (!isset($_SESSION['idUsuario'])) {
    // No hay sesión activa, redirigir a login
    header('Location: index.html');
    exit;
}

// Si llegó aquí, la sesión es válida
// Datos disponibles:
// $_SESSION['idUsuario']
// $_SESSION['nombre']
// $_SESSION['correo']
?>