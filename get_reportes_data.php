<?php
// get_reportes_data.php
header('Content-Type: application/json');
require 'check_session.php'; // Asegura que el usuario esté logueado

$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$nombre_bd = "bd_repu";

$conn = new mysqli($servidor, $usuario_db, $password_db, $nombre_bd);

// 1. Obtener contadores (Totales)
$stats = ["Pendiente" => 0, "En Revisión" => 0, "Atendido" => 0];
$res = $conn->query("SELECT estatus, COUNT(*) as total FROM reporte GROUP BY estatus");
while ($row = $res->fetch_assoc()) {
    $stats[$row['estatus']] = $row['total'];
}

// 2. Obtener lista de reportes para el mapa y tabla
$reportes = [];
$res = $conn->query("SELECT idReporte, calle, municipio, latitud, longitud, estatus, foto, descrip FROM reporte ORDER BY idReporte DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    $reportes[] = $row;
}

echo json_encode(["stats" => $stats, "reportes" => $reportes]);
$conn->close();



?>