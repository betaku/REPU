<?php
date_default_timezone_set('America/Mexico_City');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['idUsuario'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesión no detectada."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$nombre_bd = "bd_repu";

$conn = new mysqli($servidor, $usuario_db, $password_db, $nombre_bd);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error de BD: " . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

$idUsuario = $_SESSION['idUsuario'];
$idCategoria = isset($_POST['idCategoria']) ? intval($_POST['idCategoria']) : NULL;
$latitud = isset($_POST['latitud']) ? trim($_POST['latitud']) : NULL;
$longitud = isset($_POST['longitud']) ? trim($_POST['longitud']) : NULL;
$descrip = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : 'Sin descripcion';

$ubicacion_completa = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';
$partes_ubicacion = explode(',', $ubicacion_completa);
$calle = isset($partes_ubicacion[0]) ? trim($partes_ubicacion[0]) : 'Sin calle';
$municipio = isset($partes_ubicacion[1]) ? trim($partes_ubicacion[1]) : 'Sin municipio';

$colonia = NULL;
$fecha = date("Y-m-d");
$hora = date("H:i:s");
$estatus = "Pendiente";

if (!$idCategoria || !$latitud || !$longitud) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios."]);
    exit;
}

$nombre_foto = "sin_foto.jpg";
$diagnostico = "Sin archivo";

if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
    $carpeta_destino = 'uploads/';
    if (!file_exists($carpeta_destino)) { 
        mkdir($carpeta_destino, 0777, true); 
    }
    
    $info_archivo = pathinfo($_FILES['evidencia']['name']);
    $extension = strtolower($info_archivo['extension']);
    $extensiones_permitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    if (in_array($extension, $extensiones_permitidas)) {
        $nombre_foto = "reporte_" . time() . "_" . uniqid() . "." . $extension;
        if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $carpeta_destino . $nombre_foto)) {
            $diagnostico = "Imagen guardada";
        } else {
            $nombre_foto = "sin_foto.jpg";
            $diagnostico = "Fallo al guardar imagen";
        }
    } else {
        $diagnostico = "Formato no permitido";
    }
}

$sql = "INSERT INTO reporte (colonia, calle, municipio, latitud, longitud, descrip, fecha, hora, foto, estatus, idUsuario, idCategoria) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error SQL: " . $conn->error]);
    exit;
}

$stmt->bind_param("ssssssssssii", $colonia, $calle, $municipio, $latitud, $longitud, $descrip, $fecha, $hora, $nombre_foto, $estatus, $idUsuario, $idCategoria);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Reporte guardado correctamente",
        "diagnostico" => $diagnostico,
        "nombre_foto" => $nombre_foto
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al insertar: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
