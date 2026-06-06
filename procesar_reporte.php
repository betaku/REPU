<?php
// ═══════════════════════════════════════════════════════════════
// procesar_reporte.php - INSERTAR REPORTES EN BD
// ═══════════════════════════════════════════════════════════════

date_default_timezone_set('America/Mexico_City');

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

if (!isset($_SESSION['idUsuario'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Sesión no detectada."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método HTTP no permitido."]);
    exit;
}

$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$nombre_bd = "bd_repu";

$conn = new mysqli($servidor, $usuario_db, $password_db, $nombre_bd);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error BD: " . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

// Capturar datos y arreglar dirección
$idUsuario = $_SESSION['idUsuario'];
$idCategoria = isset($_POST['idCategoria']) ? intval($_POST['idCategoria']) : NULL;
$latitud = isset($_POST['latitud']) ? trim($_POST['latitud']) : NULL;
$longitud = isset($_POST['longitud']) ? trim($_POST['longitud']) : NULL;
$descrip = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : 'Sin descripción';

$ubicacion_completa = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';
$partes_ubicacion = explode(',', $ubicacion_completa);
$calle = isset($partes_ubicacion[0]) ? trim($partes_ubicacion[0]) : NULL;
$municipio = isset($partes_ubicacion[1]) ? trim($partes_ubicacion[1]) : NULL;

$colonia = NULL;
$fecha = date("Y-m-d");
$hora = date("H:i:s");
$estatus = "Pendiente";

if (!$idCategoria || !$latitud || !$longitud) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios."]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// PROCESAR IMAGEN (AHORA CON DIAGNÓSTICO)
// ═══════════════════════════════════════════════════════════════
$nombre_foto = "sin_foto.jpg";
$diagnostico = "No se detectó el campo 'evidencia' en el formulario.";

if (isset($_FILES['evidencia'])) {
    $error_foto = $_FILES['evidencia']['error'];

    if ($error_foto === UPLOAD_ERR_OK) {
        $carpeta_destino = 'uploads/';
        if (!file_exists($carpeta_destino)) { mkdir($carpeta_destino, 0777, true); }
        
        $info_archivo = pathinfo($_FILES['evidencia']['name']);
        $extension = strtolower($info_archivo['extension']);
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($extension, $extensiones_permitidas)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Formato no permitido"]);
            exit;
        }
        
        $nombre_foto = "reporte_" . time() . "_" . uniqid() . "." . $extension;
        
        if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $carpeta_destino . $nombre_foto)) {
            $diagnostico = "¡Éxito! Imagen guardada en servidor.";
        } else {
            $diagnostico = "Fallo al mover la imagen a la carpeta uploads.";
            $nombre_foto = "sin_foto.jpg"; // Revertimos si falla
        }
    } elseif ($error_foto === UPLOAD_ERR_NO_FILE) {
        $diagnostico = "Ningún archivo fue seleccionado o adjuntado.";
    } elseif ($error_foto === UPLOAD_ERR_INI_SIZE || $error_foto === UPLOAD_ERR_FORM_SIZE) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "La imagen es demasiado pesada. Límite 2MB."]);
        exit;
    } else {
        $diagnostico = "Error de subida en PHP. Código: " . $error_foto;
    }
}

// ═══════════════════════════════════════════════════════════════
// INSERTAR EN BD
// ═══════════════════════════════════════════════════════════════
$sql = "INSERT INTO reporte (colonia, calle, municipio, latitud, longitud, descrip, fecha, hora, foto, estatus, idUsuario, idCategoria) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssssii", $colonia, $calle, $municipio, $latitud, $longitud, $descrip, $fecha, $hora, $nombre_foto, $estatus, $idUsuario, $idCategoria);

if ($stmt->execute()) {
    http_response_code(201);
    // AQUÍ ENVIAMOS EL CHISME DE REGRESO
    echo json_encode([
        "status" => "success",
        "message" => "¡Reporte guardado exitosamente!",
        "diagnostico" => $diagnostico,
        "nombre_final" => $nombre_foto
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error BD: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>