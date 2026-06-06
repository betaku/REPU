<?php
// ═══════════════════════════════════════════════════════════════
// register.php - REGISTRO DE NUEVOS USUARIOS
// ═══════════════════════════════════════════════════════════════

header('Content-Type: application/json');

// Habilitar errores para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ═══════════════════════════════════════════════════════════════
// 1. VALIDAR MÉTODO POST
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
// 3. RECIBIR DATOS
// ═══════════════════════════════════════════════════════════════
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$aPat = isset($_POST['aPat']) ? trim($_POST['aPat']) : '';
$aMat = isset($_POST['aMat']) ? trim($_POST['aMat']) : '';
$correo = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
$cel = isset($_POST['cel']) ? trim($_POST['cel']) : '';

// ═══════════════════════════════════════════════════════════════
// 4. VALIDACIONES
// ═══════════════════════════════════════════════════════════════
$errores = [];

if (empty($nombre)) $errores[] = "El nombre es obligatorio";
if (empty($aPat)) $errores[] = "El apellido paterno es obligatorio";
if (empty($aMat)) $errores[] = "El apellido materno es obligatorio";
if (empty($correo)) $errores[] = "El email es obligatorio";
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "Email inválido";
if (empty($password)) $errores[] = "La contraseña es obligatoria";
if (strlen($password) < 6) $errores[] = "La contraseña debe tener al menos 6 caracteres";
if ($password !== $password_confirm) $errores[] = "Las contraseñas no coinciden";

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => implode(", ", $errores)]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// 5. VERIFICAR SI EMAIL YA EXISTE
// ═══════════════════════════════════════════════════════════════
$sql_check = "SELECT idUsg FROM usuariog WHERE correo = ? LIMIT 1";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $correo);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "El email ya está registrado"]);
    $stmt_check->close();
    $conn->close();
    exit;
}
$stmt_check->close();

// ═══════════════════════════════════════════════════════════════
// 6. INSERTAR NUEVO USUARIO EN usuariog
// ═══════════════════════════════════════════════════════════════
$tipo = "ciudadano"; // Tipo por defecto

$sql_insert = "INSERT INTO usuariog (nombre, aPat, aMat, correo, contrasenia, tipo) 
               VALUES (?, ?, ?, ?, ?, ?)";

$stmt_insert = $conn->prepare($sql_insert);
if (!$stmt_insert) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error SQL: " . $conn->error]);
    exit;
}

$stmt_insert->bind_param("ssssss", $nombre, $aPat, $aMat, $correo, $password, $tipo);

if (!$stmt_insert->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al registrar usuario: " . $stmt_insert->error]);
    $stmt_insert->close();
    $conn->close();
    exit;
}

$idUsg = $stmt_insert->insert_id;
$stmt_insert->close();

// ═══════════════════════════════════════════════════════════════
// 7. INSERTAR EN tabla usuario (datos adicionales)
// ═══════════════════════════════════════════════════════════════
$sql_usuario = "INSERT INTO usuario (cel, idUsG) VALUES (?, ?)";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("si", $cel, $idUsg);
$stmt_usuario->execute();
$stmt_usuario->close();

// ═══════════════════════════════════════════════════════════════
// 8. RESPONDER CON ÉXITO
// ═══════════════════════════════════════════════════════════════
http_response_code(201);
echo json_encode([
    "status" => "success",
    "message" => "Usuario registrado exitosamente. Puedes iniciar sesión ahora.",
    "redirect" => "index.html"
]);

$conn->close();
?>