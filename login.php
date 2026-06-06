<?php
// ═══════════════════════════════════════════════════════════════
// login.php - AUTENTICACIÓN DE USUARIOS
// ═══════════════════════════════════════════════════════════════

header('Content-Type: application/json');
session_start();

// Habilitar errores para debug (comentar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ═══════════════════════════════════════════════════════════════
// 1. VALIDAR QUE SEA POST
// ═══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// 2. CONECTAR A BD
// ═══════════════════════════════════════════════════════════════
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

// ═══════════════════════════════════════════════════════════════
// 3. RECIBIR DATOS DEL FORMULARIO
// ═══════════════════════════════════════════════════════════════
$correo = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// ═══════════════════════════════════════════════════════════════
// 4. VALIDAR INPUTS
// ═══════════════════════════════════════════════════════════════
if (empty($correo) || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email y contraseña son obligatorios"]);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email inválido"]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// 5. BUSCAR USUARIO EN BD (SENTENCIA PREPARADA)
// ═══════════════════════════════════════════════════════════════
$sql = "SELECT ug.idUsg, ug.nombre, ug.correo, ug.contrasenia, u.idUsuario, u.cel 
        FROM usuariog ug
        LEFT JOIN usuario u ON ug.idUsg = u.idUsG
        WHERE ug.correo = ? LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error SQL: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

// ═══════════════════════════════════════════════════════════════
// 6. VALIDAR CREDENCIALES
// ═══════════════════════════════════════════════════════════════
if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Email o contraseña incorrectos"]);
    $stmt->close();
    $conn->close();
    exit;
}

$usuario = $result->fetch_assoc();

// Comparación de contraseña (texto plano para desarrollo)
// IMPORTANTE: En producción usar password_verify($password, $usuario['contrasenia'])
if ($usuario['contrasenia'] !== $password) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Email o contraseña incorrectos"]);
    $stmt->close();
    $conn->close();
    exit;
}

// ═══════════════════════════════════════════════════════════════
// 7. CREAR SESIÓN
// ═══════════════════════════════════════════════════════════════
$_SESSION['idUsuario'] = $usuario['idUsuario'] ?? $usuario['idUsg'];
$_SESSION['idUsg'] = $usuario['idUsg'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['correo'] = $usuario['correo'];

// ═══════════════════════════════════════════════════════════════
// 8. RESPONDER CON ÉXITO
// ═══════════════════════════════════════════════════════════════
http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "Login exitoso",
    "redirect" => "menu.html",
    "usuario" => [
        "nombre" => $usuario['nombre'],
        "correo" => $usuario['correo']
    ]
]);

$stmt->close();
$conn->close();
?>