<?php
session_start();
// Conexión a la base de datos bd_repu
$conexion = new mysqli("localhost", "root", "", "bd_repu");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener el rol del usuario actual (si no tiene, asumimos ciudadano por seguridad)
$tipo_usuario = $_SESSION['tipo'] ?? 'ciudadano';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $accion = $_POST['accion'];

// ACCIÓN: CAMBIAR ESTATUS (Admin y futura Institución)
    if ($accion === 'cambiar_estatus' && isset($_POST['estatus'])) {
        if ($tipo_usuario === 'admin' || $tipo_usuario === 'institucion') {
            $nuevo_estatus = $conexion->real_escape_string($_POST['estatus']);
            $query_extra = "";

            // Si se cambia a "Atendido", procesamos la imagen de evidencia
            if ($nuevo_estatus === 'Atendido' && isset($_FILES['evidencia'])) {
                if ($_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
                    $directorio_destino = 'uploads/';
                    // Generamos un nombre único para la evidencia
                    $nombre_archivo = time() . "_evidencia_" . basename($_FILES['evidencia']['name']);
                    $ruta_final = $directorio_destino . $nombre_archivo;

                    if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $ruta_final)) {
                        $query_extra = ", foto_evidencia = '$nombre_archivo'";
                    }
                }
            }

            // Actualizamos el estatus (y la foto si se subió)
            $query = "UPDATE reporte SET estatus = '$nuevo_estatus' $query_extra WHERE idReporte = $id";
            $conexion->query($query);
        }
    }

    // ACCIÓN: CAMBIAR ESTATUS (Solo Administrador)
    if ($accion === 'cambiar_estatus' && isset($_POST['estatus'])) {
        if ($tipo_usuario === 'admin') {
            $nuevo_estatus = $conexion->real_escape_string($_POST['estatus']);
            $query = "UPDATE reporte SET estatus = '$nuevo_estatus' WHERE idReporte = $id";
            $conexion->query($query);
        }
    }

    // ACCIÓN: ELIMINAR REPORTE (Solo Administrador)
    if ($accion === 'eliminar') {
        if ($tipo_usuario === 'admin') {
            $query = "DELETE FROM reporte WHERE idReporte = $id";
            $conexion->query($query);
        }
    }
}

// Redireccionar de vuelta a la página desde la que se hizo la petición
$referer = $_SERVER['HTTP_REFERER'] ?? 'menu.php';
header("Location: $referer");
exit();
?>