<?php include 'check_session.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPU - Menú Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { height: 100vh; width: 250px; position: fixed; top: 0; left: 0; background-color: #0d47a1; padding-top: 20px; color: white; z-index: 1000; }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 1.1rem; color: #d1d1d1; display: block; }
        .sidebar a:hover { color: white; background-color: #1565c0; }
        .sidebar a.active { background-color: #1976d2; color: white; border-left: 5px solid #bbdefb; }
        .main-content { margin-left: 250px; padding: 40px; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background-color: white; }
        .feed-horizontal { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 15px; scroll-behavior: smooth; }
        
        /* -------------------------------------------------------------------------
           AQUÍ ESTÁ EL TAMAÑO FIJO QUE PEDISTE PARA QUE NO SE ALARGUEN NI ENSANCHEN
           ------------------------------------------------------------------------- */
        .report-post { 
            min-width: 310px; width: 310px; height: 390px; 
            border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); background: white; 
            display: flex; flex-direction: column; overflow: hidden; 
        }
        .report-post.sin-imagen { padding: 15px; justify-content: space-between; }
        .report-post.con-imagen { padding: 0; }
        .report-post.con-imagen img { width: 100%; height: 130px; object-fit: cover; border-radius: 16px 16px 0 0; }
        .report-post.con-imagen .card-body { padding: 12px; display: flex; flex-direction: column; height: calc(100% - 130px); justify-content: space-between; }
        
        /* Ajuste para que el texto largo se corte a 2 líneas automáticamente */
        .descrip-text { font-size: 0.85rem; color: #6c757d; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; margin-bottom: 5px; flex-grow: 1; }
        
        .report-post .badge { display: inline-block; width: auto; text-align: center; padding: 4px 10px; font-size: 0.75rem; }
        .badge-categoria { font-size: 0.75rem; color: #0d47a1; background-color: #e3f2fd; padding: 4px 8px; border-radius: 6px; font-weight: bold; border: 1px solid #bbdefb; }
        .report-post .title { font-weight: bold; margin-bottom: 4px; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        #mapa-general { height: 320px; border-radius: 15px; }
    </style>
</head>
<body>

    <div class="sidebar shadow">
        <div class="px-4 mb-5 text-center"><h3 class="fw-bold">REPU</h3></div>
        
        <a href="menu.php" class="active"><i class="fa-solid fa-house me-2"></i> Inicio</a>
        <a href="reportar.html"><i class="fa-solid fa-file-pen me-2"></i> Crear Reporte</a>
        <a href="historial.php"><i class="fa-solid fa-list-check me-2"></i> Historial</a>
        
        <a href="ayuda.html"><i class="fa-solid fa-circle-question me-2"></i> Ayuda</a>
        
        <hr class="mx-3 opacity-25">
        <a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión</a>
    </div>
    
    <div class="main-content">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-custom p-3 d-flex flex-row align-items-center">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3"><i class="fa-solid fa-bullhorn fs-4"></i></div>
                    <div><h6 class="text-muted small mb-0">Pendientes</h6><h3 id="count-pendientes" class="fw-bold mb-0">0</h3></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-3 d-flex flex-row align-items-center">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 me-3"><i class="fa-solid fa-check-double fs-4"></i></div>
                    <div><h6 class="text-muted small mb-0">Atendidos</h6><h3 id="count-atendidos" class="fw-bold mb-0">0</h3></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-3 d-flex flex-row align-items-center">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 me-3"><i class="fa-solid fa-triangle-exclamation fs-4"></i></div>
                    <div><h6 class="text-muted small mb-0">En Revisión</h6><h3 id="count-revision" class="fw-bold mb-0">0</h3></div>
                </div>
            </div>
        </div>

        <div class="mb-5">
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-users text-primary me-2"></i>Reportes Recientes</h4>
            <div id="contenedor-reportes" class="feed-horizontal">
                </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 p-4 shadow-sm h-100" style="background: linear-gradient(135deg, #0d47a1, #1976d2); color: white; border-radius: 15px;">
                    <h5>¿Detectaste un problema urbano?</h5>
                    <p>Reporta incidencias en tu ciudad y ayuda a mejorar la gestión urbana.</p>
                    <a href="reportar.html" class="btn btn-light btn-sm mt-3">Crear Reporte</a>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card card-custom p-3">
                    <h5>Ubicación Geográfica</h5>
                    <div id="mapa-general"></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalEvidencia" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-check me-2"></i>Evidencia de Reparación</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-4">
                        <img id="imagen-evidencia" src="" alt="Evidencia" class="img-fluid rounded shadow" style="max-height: 70vh;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    
    <script>

        
        
        const categorias = {
            1: "Bache / Socavón",
            2: "Falla de Luminaria",
            3: "Fuga de Agua",
            4: "Acumulación de Basura"
        };
        const iconosCategorias = {
            1: "fa-solid fa-triangle-exclamation",
            2: "fa-solid fa-lightbulb",             
            3: "fa-solid fa-droplet",               
            4: "fa-solid fa-trash-can"              
        };

        var map = L.map('mapa-general').setView([23.7369, -99.1411], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

        let tipoUsuarioActual = 'ciudadano';

        // Funciones para Evidencia
        window.toggleEvidencia = function(selectElement, idReporte) {
            let contenedor = document.getElementById('caja-evidencia-' + idReporte);
            let input = document.getElementById('input-evidencia-' + idReporte);
            if (selectElement.value === 'Atendido') {
                contenedor.style.display = 'block';
                input.required = true;
            } else {
                contenedor.style.display = 'none';
                input.required = false;
            }
        };

        window.mostrarEvidencia = function(urlImagen) {
            document.getElementById('imagen-evidencia').src = urlImagen;
            new bootstrap.Modal(document.getElementById('modalEvidencia')).show();
        };

        // Identificar rol y cargar reportes
        fetch('get_user_session.php')
            .then(res => res.json())
            .then(userData => {
                if(userData.status === 'success') {
                    tipoUsuarioActual = userData.tipo;
                }
                cargarReportes();
            })
            .catch(() => cargarReportes()); // En caso de error, carga como ciudadano

        function cargarReportes() {
            fetch('get_reportes_data.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('count-pendientes').textContent = data.stats['Pendiente'] || 0;
                document.getElementById('count-revision').textContent = data.stats['En Revisión'] || 0;
                document.getElementById('count-atendidos').textContent = data.stats['Atendido'] || 0;

                const container = document.getElementById('contenedor-reportes');
                container.innerHTML = '';

                data.reportes.forEach(rep => {
                    let tieneImagen = rep.foto && rep.foto !== 'sin_foto.jpg';
                    let nombreCategoria = categorias[rep.idCategoria] || 'Sin categoría';
                    let iconoClase = iconosCategorias[rep.idCategoria] || "fa-solid fa-circle-info";
                    
                    // Colores del estatus
                    let badgeColor = 'bg-primary'; 
                    if (rep.estatus === 'En Revisión') badgeColor = 'bg-warning text-dark'; 
                    else if (rep.estatus === 'Atendido') badgeColor = 'bg-success'; 

                    // Controles solo para Admin o Institución
                    let controlesAdmin = '';
                    if (tipoUsuarioActual === 'admin' || tipoUsuarioActual === 'institucion') {
                        let botonEliminar = '';
                        if (tipoUsuarioActual === 'admin') {
                            botonEliminar = `
                                <form action="acciones_reporte.php" method="POST" onsubmit="return confirm('¿Eliminar este reporte?');" class="mt-1">
                                    <input type="hidden" name="id" value="${rep.idReporte}">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 py-0" style="font-size: 0.75rem;"><i class="fa-solid fa-trash"></i> Eliminar</button>
                                </form>
                            `;
                        }

                        controlesAdmin = `
                            <div class="mt-2 pt-2 border-top">
                                <form action="acciones_reporte.php" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-1">
                                    <input type="hidden" name="id" value="${rep.idReporte}">
                                    <input type="hidden" name="accion" value="cambiar_estatus">
                                    <div class="d-flex align-items-center gap-1">
                                        <select name="estatus" class="form-select form-select-sm" style="font-size: 0.8rem; padding: 2px 4px;" onchange="toggleEvidencia(this, ${rep.idReporte})">
                                            <option value="Pendiente" ${rep.estatus === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                                            <option value="En Revisión" ${rep.estatus === 'En Revisión' ? 'selected' : ''}>En Revisión</option>
                                            <option value="Atendido" ${rep.estatus === 'Atendido' ? 'selected' : ''}>Atendido</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary" style="font-size: 0.8rem; padding: 2px 6px;">Ok</button>
                                    </div>
                                    <div id="caja-evidencia-${rep.idReporte}" style="display: none; background: #f8f9fa; padding: 5px; border-radius: 5px; border: 1px dashed #ccc;">
                                        <label class="form-label mb-0" style="font-size: 0.7rem; color: #198754; font-weight: bold;"><i class="fa-solid fa-camera"></i> Subir evidencia:</label>
                                        <input type="file" name="evidencia" id="input-evidencia-${rep.idReporte}" class="form-control form-control-sm" style="font-size: 0.7rem;" accept="image/*">
                                    </div>
                                </form>
                                ${botonEliminar}
                            </div>
                        `;
                    }

                    // Botón para visualizar la evidencia
                    let botonEvidencia = '';
                    if (rep.estatus === 'Atendido' && rep.foto_evidencia) {
                        botonEvidencia = `
                            <button class="btn btn-sm btn-outline-success w-100 mb-2 py-1 fw-bold" style="font-size: 0.8rem;" onclick="mostrarEvidencia('uploads/${rep.foto_evidencia}')">
                                <i class="fa-solid fa-camera me-1"></i>Ver Evidencia
                            </button>
                        `;
                    }

                    // Estructura interna de la tarjeta
                    let contenidoTarjeta = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge ${badgeColor} m-0">${rep.estatus}</span>
                            <span class="badge-categoria m-0"><i class="${iconoClase} me-1"></i>${nombreCategoria}</span>
                        </div>
                        <div class="title" title="${rep.calle}"><i class="fa-solid fa-map-pin text-danger me-2"></i>${rep.calle || 'Ubicación desconocida'}</div>
                        <p class="descrip-text">${rep.descrip || 'Sin descripción'}</p>
                        ${botonEvidencia}
                        ${controlesAdmin}
                    `;

                    // Renderizar con o sin imagen
                    if (tieneImagen) {
                        container.innerHTML += `
                            <div class="report-post con-imagen">
                                <img src="uploads/${rep.foto}" alt="Reporte" onerror="this.style.display='none'">
                                <div class="card-body">
                                    ${contenidoTarjeta}
                                </div>
                            </div>`;
                    } else {
                        container.innerHTML += `
                            <div class="report-post sin-imagen">
                                ${contenidoTarjeta}
                            </div>`;
                    }

                    // Agregar pines al mapa
                    if(rep.latitud && rep.longitud) {
                        L.marker([parseFloat(rep.latitud), parseFloat(rep.longitud)])
                        .addTo(map)
                        .bindPopup("<b>ID:</b> " + rep.idReporte + "<br>Categoría: " + nombreCategoria + "<br>Estatus: " + rep.estatus);
                    }
                });
            })
            .catch(error => console.error('Error al cargar reportes:', error));
        }
    </script>
</body>
</html>