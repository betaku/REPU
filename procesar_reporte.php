<?php
date_default_timezone_set('America/Mexico_City');
session_start();

// Evitamos que PHP imprima errores HTML que rompan el formato JSON
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// Obliga a MySQL a reportar errores exactos para poder atraparlos
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 

try {
    if (!isset($_SESSION['idUsuario'])) {
        throw new Exception("Sesión no detectada.");
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido.");
    }

    $conn = new mysqli("localhost", "root", "", "bd_repu");
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
        throw new Exception("Faltan datos obligatorios del formulario.");
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
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $nombre_foto = "reporte_" . time() . "_" . uniqid() . "." . $extension;
            if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $carpeta_destino . $nombre_foto)) {
                $diagnostico = "Imagen guardada";
            } else {
                $nombre_foto = "sin_foto.jpg";
            }
        }
    }

    $sql = "INSERT INTO reporte (colonia, calle, municipio, latitud, longitud, descrip, fecha, hora, foto, estatus, idUsuario, idCategoria) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssii", $colonia, $calle, $municipio, $latitud, $longitud, $descrip, $fecha, $hora, $nombre_foto, $estatus, $idUsuario, $idCategoria);
    
    $stmt->execute();

    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Reporte guardado correctamente",
        "diagnostico" => $diagnostico
    ]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Si algo falla (como una llave foránea), se atrapa aquí y se envía como JSON.
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Error interno de BD: " . $e->getMessage()
    ]);
}
?>