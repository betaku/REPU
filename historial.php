<?php 
include 'check_session.php'; // Protege la página

// Conexión a la base de datos bd_repu
$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$nombre_bd = "bd_repu";

$conn = new mysqli($servidor, $usuario_db, $password_db, $nombre_bd);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar caracteres en UTF-8
$conn->set_charset("utf8mb4");

// ===================================================
// LÓGICA 1: ELIMINAR REPORTE DIRECTO EN LA BASE DE DATOS
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $idEliminar = intval($_POST['id']);
    
    // (Opcional) Aquí podrías hacer un SELECT antes de eliminar para borrar también el archivo físico de la imagen
    
    $stmtDel = $conn->prepare("DELETE FROM reporte WHERE idReporte = ?");
    $stmtDel->bind_param("i", $idEliminar);
    $stmtDel->execute();
    $stmtDel->close();
    
    header("Location: historial.php");
    exit;
}

// ===================================================
// LÓGICA 2: ACTUALIZAR REPORTE DIRECTO EN LA BASE DE DATOS
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    $idEditar = intval($_POST['id']);
    $descrip = $_POST['descrip'];
    $calle = $_POST['calle'];
    $municipio = $_POST['municipio'];

    // Lógica para actualizar la imagen si se subió una nueva
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $directorioUploads = 'uploads/'; // Carpeta donde se guardan las imágenes
        
        // Crear el directorio si no existe
        if (!file_exists($directorioUploads)) {
            mkdir($directorioUploads, 0777, true);
        }

        $nombreArchivo = basename($_FILES['imagen']['name']);
        // Generar un nombre único para evitar que las imágenes se sobreescriban
        $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        $nombreUnico = uniqid('rep_', true) . '.' . $extension;
        $rutaDestino = $directorioUploads . $nombreUnico;

        // Validar que sea una imagen real (opcional pero recomendado)
        $check = getimagesize($_FILES['imagen']['tmp_name']);
        if($check !== false) {
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                // Actualizar incluyendo la nueva ruta de la imagen
                $stmtUpd = $conn->prepare("UPDATE reporte SET descrip = ?, calle = ?, municipio = ?, foto = ? WHERE idReporte = ?");
                $stmtUpd->bind_param("ssssi", $descrip, $calle, $municipio, $rutaDestino, $idEditar);
                $stmtUpd->execute();
                $stmtUpd->close();
            }
        }
    } else {
        // Si no se subió imagen, actualizar solo los datos de texto
        $stmtUpd = $conn->prepare("UPDATE reporte SET descrip = ?, calle = ?, municipio = ? WHERE idReporte = ?");
        $stmtUpd->bind_param("sssi", $descrip, $calle, $municipio, $idEditar);
        $stmtUpd->execute();
        $stmtUpd->close();
    }
    
    header("Location: historial.php");
    exit;
}

// Obtener los reportes ordenados del más reciente al más antiguo
$query = "SELECT idReporte, calle, municipio, estatus, descrip FROM reporte ORDER BY idReporte DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPU - Historial de Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { height: 100vh; width: 250px; position: fixed; top: 0; left: 0; background-color: #0d47a1; padding-top: 20px; color: white; }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 1.1rem; color: #d1d1d1; display: block; }
        .sidebar a:hover { color: white; background-color: #1565c0; }
        .sidebar a.active { background-color: #1976d2; color: white; border-left: 5px solid #bbdefb; }
        .main-content { margin-left: 250px; padding: 40px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background-color: white; }
    </style>
</head>
<body>

    <div class="sidebar shadow">
        <div class="px-4 mb-5 text-center">
            <h3 class="fw-bold">REPU</h3>
            <p class="small opacity-75">Gestión Urbana</p>
        </div>
        <a href="menu.php"><i class="fa-solid fa-house me-2"></i> Inicio</a>
        <a href="reportar.html"><i class="fa-solid fa-file-pen me-2"></i> Crear Reporte</a>
        <a href="historial.php" class="active"><i class="fa-solid fa-list-check me-2"></i> Historial</a>
        <a href="ayuda.html"><i class="fa-solid fa-circle-question me-2"></i> Ayuda</a>
        <hr class="mx-3 opacity-25">
        <a href="logout.php" class="text-danger">
            <i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión
        </a>
    </div>

    <div class="main-content">
        <div class="mb-4">
            <h2 class="fw-bold">Historial de Reportes</h2>
            <p class="text-muted">Consulta, edita y da seguimiento al estado de tus solicitudes.</p>
        </div>

        <div class="card card-custom p-4">
            <h4 class="fw-bold text-dark mb-4"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Mis Reportes Ciudadanos</h4>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Folio</th>
                            <th scope="col">Tipo de Problema</th>
                            <th scope="col">Ubicación</th>
                            <th scope="col">Municipio</th>
                            <th scope="col">Estatus</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $badgeColor = 'bg-secondary';
                                if ($row['estatus'] === 'Pendiente') { $badgeColor = 'bg-danger'; }
                                elseif ($row['estatus'] === 'En Revisión') { $badgeColor = 'bg-warning text-dark'; }
                                elseif ($row['estatus'] === 'Atendido') { $badgeColor = 'bg-success'; }
                                ?>
                                <tr>
                                    <td class="fw-bold text-primary">#RE-<?php echo str_pad($row['idReporte'], 4, "0", STR_PAD_LEFT); ?></td>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($row['descrip'] ?? 'No especificado'); ?></td>
                                    <td><?php echo htmlspecialchars($row['calle']); ?></td>
                                    <td><?php echo htmlspecialchars($row['municipio'] ?? 'No especificado'); ?></td>
                                    <td><span class="badge <?php echo $badgeColor; ?> px-3 py-2 rounded-pill"><?php echo htmlspecialchars($row['estatus']); ?></span></td>
                                    
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditar"
                                                    data-id="<?php echo $row['idReporte']; ?>"
                                                    data-descrip="<?php echo htmlspecialchars($row['descrip'] ?? ''); ?>"
                                                    data-calle="<?php echo htmlspecialchars($row['calle'] ?? ''); ?>"
                                                    data-municipio="<?php echo htmlspecialchars($row['municipio'] ?? ''); ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            
                                            <form action="historial.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este reporte permanentemente de la base de datos?');" style="margin:0;">
                                                <input type="hidden" name="id" value="<?php echo $row['idReporte']; ?>">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center text-muted py-4">No se encontraron reportes registrados.</td></tr>';
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalEditarLabel">Editar Reporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="historial.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <input type="hidden" name="accion" value="actualizar">

                        <div class="mb-3">
                            <label for="edit-descrip" class="form-label fw-semibold">Tipo de Problema</label>
                            <input type="text" class="form-control" name="descrip" id="edit-descrip" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-calle" class="form-label fw-semibold">Ubicación / Calle</label>
                            <input type="text" class="form-control" name="calle" id="edit-calle" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-municipio" class="form-label fw-semibold">Municipio</label>
                            <input type="text" class="form-control" name="municipio" id="edit-municipio" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit-imagen" class="form-label fw-semibold">Actualizar Imagen (Opcional)</label>
                            <input type="file" class="form-control" name="imagen" id="edit-imagen" accept="image/*">
                            <small class="text-muted">Si no seleccionas un archivo, se mantendrá la imagen actual.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const modalEditar = document.getElementById('modalEditar');
        if (modalEditar) {
            modalEditar.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                
                const id = button.getAttribute('data-id');
                const descrip = button.getAttribute('data-descrip');
                const calle = button.getAttribute('data-calle');
                const municipio = button.getAttribute('data-municipio');

                modalEditar.querySelector('#edit-id').value = id;
                modalEditar.querySelector('#edit-descrip').value = descrip;
                modalEditar.querySelector('#edit-calle').value = calle;
                modalEditar.querySelector('#edit-municipio').value = municipio;
                
                // Limpiar el campo de archivo por si había algo seleccionado de una edición anterior
                modalEditar.querySelector('#edit-imagen').value = '';
                
                modalEditar.querySelector('#modalEditarLabel').textContent = 'Editar Reporte #RE-' + id.padStart(4, '0');
            });
        }
    </script>
</body>
</html>