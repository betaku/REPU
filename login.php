<?php
// login.php - AUTENTICACIÓN DE USUARIOS
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
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

$correo = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($correo) || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email y contraseña son obligatorios"]);
    exit;
}

// AQUÍ ESTÁ EL CAMBIO: Agregamos ug.tipo a la consulta
$sql = "SELECT ug.idUsg, ug.nombre, ug.correo, ug.contrasenia, ug.tipo, u.idUsuario, u.cel 
        FROM usuariog ug
        LEFT JOIN usuario u ON ug.idUsg = u.idUsG
        WHERE ug.correo = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Email o contraseña incorrectos"]);
    exit;
}

$usuario = $result->fetch_assoc();

if ($usuario['contrasenia'] !== $password) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Email o contraseña incorrectos"]);
    exit;
}

// AQUÍ ESTÁ EL CAMBIO: Guardamos el tipo en la sesión
$_SESSION['idUsuario'] = $usuario['idUsuario'] ?? $usuario['idUsg'];
$_SESSION['idUsg'] = $usuario['idUsg'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['correo'] = $usuario['correo'];
$_SESSION['tipo'] = $usuario['tipo']; 

http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "Login exitoso",
    "redirect" => "menu.php", // Asegúrate de que apunte a .php
    "usuario" => [
        "nombre" => $usuario['nombre'],
        "correo" => $usuario['correo'],
        "tipo" => $usuario['tipo']
    ]
]);

$stmt->close();
$conn->close();
?>