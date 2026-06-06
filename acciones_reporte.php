<?php
// Conexión a la base de datos bd_repu
$conexion = new mysqli("localhost", "root", "", "bd_repu");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Validar que la petición venga mediante el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $accion = $_POST['accion'];

    // ACCIÓN: ACTUALIZAR DESCRIPCIÓN
    if ($accion === 'actualizar' && isset($_POST['descripcion'])) {
        $nueva_descripcion = $conexion->real_escape_string($_POST['descripcion']);
        
        $query = "UPDATE reporte SET descripcion = '$nueva_descripcion' WHERE id = $id";
        $conexion->query($query);
    } 
    
    // ACCIÓN: ELIMINAR REPORTE COMPLETAMENTE
    if ($accion === 'eliminar') {
        $query = "DELETE FROM reporte WHERE id = $id";
        $conexion->query($query);
    }
}

// Redireccionar automáticamente de vuelta a la ventana del historial
header("Location: historial.php");
exit();
?>