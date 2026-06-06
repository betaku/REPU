<?php
// ═══════════════════════════════════════════════════════════════
// logout.php - CERRAR SESIÓN
// ═══════════════════════════════════════════════════════════════

session_start();
session_destroy();
header('Location: index.html');
exit;
?>